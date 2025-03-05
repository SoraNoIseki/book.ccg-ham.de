<?php

namespace Soranoiseki\BookGroup\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Soranoiseki\Core\Controllers\Controller;
use Soranoiseki\Core\Traits\ApiResponser;
use Soranoiseki\BookGroup\Http\Resources\TaskPlan\NameResource;
use Soranoiseki\BookGroup\Http\Resources\TaskPlan\TaskInfoResource;
use Soranoiseki\BookGroup\Http\Requests\CreateMemberRequest;
use Soranoiseki\BookGroup\Http\Requests\DeleteMemberRequest;
use Soranoiseki\BookGroup\Http\Requests\ToggleGroupRoleRequest;
use Soranoiseki\BookGroup\Http\Requests\UpdateTaskPlanRequest;
use Soranoiseki\BookGroup\Models\TaskPlan\{
    AfterLunchInfo,
    BabyInfo,
    BeforeLunchInfo,
    CleanupInfo,
    CookInfo,
    HosterInfo,
    InstrumentOperatorInfo,
    KidInfo,
    MainActionerInfo,
    NameInfo,
    PianoInfo,
    PptInfo,
    PreacherInfo,
    ReceptionInfo,
    SongLeaderInfo,
    TeenageInfo,
    TopicInfo,
};


class TaskPlanApiController extends Controller
{
    use ApiResponser;

    public function getMembers(Request $request)
    {
        $names = NameInfo::raw(function($collection) {
            return $collection->find([], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
        });

        return $this->respondSuccessWithResource(NameResource::collection($names));
    }

    public function getGroups(Request $request)
    {
        return $this->respondSuccess(config('book.groups', []));
    }

    public function createMember(CreateMemberRequest $request)
    {
        try {
            $data = $request->validated();

            $nameInfo = NameInfo::raw(function($collection) use ($data) {
                return $collection->findOne([
                    'role' => $data['role'],
                ], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
            });
            
            $members = explode('+', $nameInfo->name);

            // Cleanup: trim names
            $members = array_map(function($member) {
                return trim($member);
            }, $members);;

            // Search for the name and remove it
            $key = array_search($data['name'], $members);
            if ($key === false) {
                $members[] = $data['name'];
                $nameInfo->name = implode('+', $members);
                $nameInfo->save();
            }

            // Reload the names
            $names = NameInfo::raw(function($collection) {
                return $collection->find([], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
            });
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage());
        }
        
        return $this->respondSuccessWithResource(NameResource::collection($names));
    }

    public function deleteMember(DeleteMemberRequest $request)
    {
        try {
            $data = $request->validated();

            $names = NameInfo::raw(function($collection) {
                return $collection->find([], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
            });
            
            foreach ($names as $item) {
                $members = explode('+', $item->name);

                // Cleanup: trim names
                $members = array_map(function($member) {
                    return trim($member);
                }, $members);;

                // Search for the name and remove it
                $key = array_search($data['name'], $members);
                if ($key !== false) {
                    unset($members[$key]);
                    $item->name = implode('+', $members);
                    $item->save();
                }
            }
            
            // Reload the names
            $names = NameInfo::raw(function($collection) {
                return $collection->find([], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
            });
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage());
        }
        
        return $this->respondSuccessWithResource(NameResource::collection($names));
    }

    public function toggleGroupRole(ToggleGroupRoleRequest $request) {
        try {
            $data = $request->validated();
            $name = $data['name'];
            $role = $data['role'];

            // dd($data);

            $name = NameInfo::raw(function($collection) use ($name, $role) {
                return $collection->findOne(['role' => $role], ['sort' => ['role' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
            });

            if ($name) {
                $members = explode('+', $name->name);

                // Cleanup: trim names
                $members = array_map(function($member) {
                    return trim($member);
                }, $members);;

                // Search for the name and remove it
                $key = array_search($data['name'], $members);
                if ($key === false) {
                    $members[] = $data['name'];
                    $name->name = implode('+', $members);
                    $name->save();
                } else {
                    unset($members[$key]);
                    $name->name = implode('+', $members);
                    $name->save();
                }
            } else {
                $name = new NameInfo();
                $name->role = $role;
                $name->name = $data['name'];
                $name->save();
            }
            
            // Reload the names
            $names = NameInfo::raw(function($collection) {
                return $collection->find([], ['sort' => ['name' => 1], 'collation' => ['locale' => 'zh', 'strength' => 1]]);
            });
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage());
        }
        
        return $this->respondSuccessWithResource(NameResource::collection($names));
    }
    




    public function getTaskPlans(Request $request)
    {
        $date = Carbon::now();
        if ($request->has('year') && $request->has('month')) {
            $date = Carbon::createFromDate($request->year, $request->month, 1);
        }
        $groupId = date('Y_n', $date->timestamp);
       

        $taskPlans = [];

        $plans = TopicInfo::raw(function($collection) use ($groupId) {
            return $collection->findOne(['group_id' => $groupId]);
        });
        $taskPlans[] = [
            'role' => '主题',
            'plans' => $plans ? new TaskInfoResource($plans) : null,
        ];

        $groups = config('book.groups', []);
        foreach ($groups as $group) {
            foreach ($group['roles'] as $role) {

                $plans = null;
                switch ($role['role']) {
                    case '证道':
                        $plans = PreacherInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '司会':
                        $plans = HosterInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '司琴':
                        $plans = PianoInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '领唱':
                        $plans = SongLeaderInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '主执':
                        $plans = MainActionerInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '音响':
                        $plans = InstrumentOperatorInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '接待收拾':
                        $plans = ReceptionInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '幼儿班':
                        $plans = BabyInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '少儿班':
                        $plans = KidInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '少年班':
                        $plans = TeenageInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case 'PPT制作播放':
                        $plans = PptInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '管堂组':
                        $plans = CleanupInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '餐前准备':
                        $plans = BeforeLunchInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '主厨':
                        $plans = CookInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    case '餐后洗碗':
                        $plans = AfterLunchInfo::raw(function($collection) use ($groupId) {
                            return $collection->findOne(['group_id' => $groupId]);
                        });
                        break;
                    default:
                        break;
                }

                $taskPlans[] = [
                    'role' => $role['role'],
                    'plans' => $plans ? new TaskInfoResource($plans) : null,
                ];
            }
        }

        return $this->respondSuccess($taskPlans);
    }

    public function updateTaskPlan(UpdateTaskPlanRequest $request) {
        $data = $request->validated();
        $role = $data['role'];
        $members = $data['members'] ?? '';
        $date = Carbon::createFromFormat('Y-m-d', $data['date']);
        $groupId = date('Y_n', $date->timestamp);
        $weekOfMonth = $date->weekOfMonth;

        // dd($data, $weekOfMonth);

        switch ($role) {
            case '证道':
                $plans = PreacherInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new PreacherInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '司会':
                $plans = HosterInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new HosterInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '司琴':
                $plans = PianoInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new PianoInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '领唱':
                $plans = SongLeaderInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new SongLeaderInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '主执':
                $plans = MainActionerInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new MainActionerInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '音响':
                $plans = InstrumentOperatorInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new InstrumentOperatorInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '接待收拾':
                $plans = ReceptionInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new ReceptionInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '幼儿班':
                $plans = BabyInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new BabyInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '少儿班':
                $plans = KidInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new KidInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '少年班':
                $plans = TeenageInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new TeenageInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case 'PPT制作播放':
                $plans = PptInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new PptInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '管堂组':
                $plans = CleanupInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new CleanupInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '餐前准备':
                $plans = BeforeLunchInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new BeforeLunchInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '主厨':
                $plans = CookInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new CookInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            case '餐后洗碗':
                $plans = AfterLunchInfo::raw(function($collection) use ($groupId) {
                    return $collection->findOne(['group_id' => $groupId]);
                });
                if ($plans) {
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                } else {
                    $plans = new AfterLunchInfo();
                    $plans->group_id = $groupId;
                    $plans->initWeekAttributes();
                    $plans->setWeekAttribute($weekOfMonth, $members);
                    $plans->save();
                }
                break;
            default:
                break;
        }

        return $this->respondSuccess([
            'role' => $role,
            'plans' => new TaskInfoResource($plans),
        ]);

    }
   

}
