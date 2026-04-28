<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDevice;

class AdminController extends Controller
{
   public function dashboard()
    {
        return view('admin.dashboard');
    }
    public function usersList()
{
    $users = User::where('role', 'user')
        ->orderBy('id', 'DESC')
        ->paginate(20); // or get()

    return view('admin.users.index', compact('users'));
}
public function viewUser($id)
{
    $user = User::findOrFail($id);
    $devices = UserDevice::where('user_id',$id)->get();
    return view('admin.users.view', compact('user', 'devices'));
}

}
