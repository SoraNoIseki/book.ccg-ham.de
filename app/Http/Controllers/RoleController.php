<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Soranoiseki\Core\Traits\ApiResponser;
use App\Models\Role;

class RoleController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $users = Role::all();
       
        return $this->respondSuccess($users->toArray());
    }
}
