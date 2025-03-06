<?php

namespace Soranoiseki\BookGroup\Controllers;

use Illuminate\Http\Request;
use Soranoiseki\Core\Controllers\Controller;
use App\Http\Resources\RoleResource;

class TaskPlanController extends Controller
{   public function index(Request $request)
    {
        $roles = RoleResource::collection($request->user()->roles);
        return view('book-group::task-plan.index', [
            'roles' => $roles->toJson(),
        ]);
    }
}
