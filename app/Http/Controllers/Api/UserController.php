<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
class UserController extends Controller
{
    public function store(Request $request)
{
  $request->validate([
    'username' => 'required|unique:users',
    'password' => 'required|string|min:6',
    'role'     => 'required|in:admin,teacher,student,parent,supervisor',
]);

    $user = User::create([
        'username' => $request->username,
        'password' => bcrypt($request->password),
        'role' => $request->role,
        'status' => true
    ]);

    return response()->json($user, 201);
}
}
