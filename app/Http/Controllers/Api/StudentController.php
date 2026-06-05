<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
class StudentController extends Controller
{
    public function store(Request $request)
{
    $user = User::create([
        'username' => $request->username,
        'password' => bcrypt($request->password),
        'role' => 'student',
        'status' => true
    ]);

    $student = Student::create([
        'academic_number' => $request->academic_number,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'birth_date' => $request->birth_date,
        'gender' => $request->gender,
        'address' => $request->address,
        'photo' => $request->photo,
        'user_id' => $user->id,
        'parent_id' => $request->parent_id,
        'section_id' => $request->section_id
    ]);

    return response()->json([
        'user' => $user,
        'student' => $student
    ]);
}
}
