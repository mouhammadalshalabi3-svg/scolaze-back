<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;

class TeacherController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create([
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'role' => 'teacher',
            'status' => true
        ]);

        $teacher = Teacher::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'photo' => $request->photo,
            'salary_type' => $request->salary_type,
            'user_id' => $user->id
        ]);

        return response()->json([
            'user' => $user,
            'teacher' => $teacher
        ], 201);
    }
}