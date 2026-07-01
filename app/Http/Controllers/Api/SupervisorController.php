<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Supervisor;
class SupervisorController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
    'username'   => 'required|string|unique:users,username',
    'password'   => 'required|string|min:6',
    'first_name' => 'required|string',
    'last_name'  => 'required|string',
    'phone'      => 'required|string',
    'email'      => 'required|email|unique:supervisors,email',
]);
    $user = User::create([
        'username' => $request->username,
        'password' => bcrypt($request->password),
        'role' => 'supervisor',
        'status' => true
    ]);

    $supervisor = Supervisor::create([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'phone' => $request->phone,
        'email' => $request->email,
        'user_id' => $user->id
    ]);

    return response()->json([
        'user' => $user,
        'supervisor' => $supervisor
    ], 201);
}
}
