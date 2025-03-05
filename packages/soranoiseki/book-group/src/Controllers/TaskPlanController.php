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



class TaskPlanController extends Controller
{
    protected $content = [
        'pray',
        'preach',
        'report',
        'scripture',
        'song',
        'worker',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('book-group::task-plan.index', []);
    }
    

}
