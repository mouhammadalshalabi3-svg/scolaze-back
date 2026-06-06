<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ParentModel;
class ParentController extends Controller
{
    public function store(Request $request)
{
    $user = User::create([
        'username' => $request->username,
        'password' => bcrypt($request->password),
        'role' => 'parent',
        'status' => true
    ]);

    $parent = ParentModel::create([
        'father_name' => $request->father_name,
        'mother_name' => $request->mother_name,
        'phone' => $request->phone,
        'email' => $request->email,
        'user_id' => $user->id
    ]);

    return response()->json([
        'user' => $user,
        'parent' => $parent
    ], 201);
}
}
