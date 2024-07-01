<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Soranoiseki\Core\Traits\ApiResponser;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $users = User::with(['roles'])->get();
       
        return $this->respondSuccess($users->toArray());
    }

    public function toggleUserRole(Request $request, User $user, Role $role)
    {
        try {
            $user->roles()->toggle($role->id);
            return $this->respondSuccess();
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }
}
