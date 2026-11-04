<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Illuminate\Http\Request;
use Soranoiseki\Core\Controllers\Controller;

class TaskPlanController extends Controller
{   public function index(Request $request)
    {
        return view('book-group::task-plan.index');
    }
}
