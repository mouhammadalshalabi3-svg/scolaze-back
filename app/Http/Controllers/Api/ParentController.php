<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ParentModel;
use App\Models\Student;

class ParentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'father_name' => 'required|string',
            'mother_name' => 'nullable|string',
            'phone'       => 'required|string',
            'email'       => 'nullable|email|unique:parents,email',
        ]);

        $student = Student::findOrFail($request->student_id);

        $password = Str::random(8);

        $user = User::create([
            'username' => null,
            'password' => bcrypt($password),
            'role'     => 'parent',
            'status'   => true,
        ]);

        $parent = ParentModel::create([
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'user_id'     => $user->id,
        ]);

        $student->update(['parent_id' => $parent->id]);

        return response()->json([
            'message' => 'تم إضافة الأهل بنجاح',
            'parent'  => $parent,
            'credentials' => [
                'student_academic_number' => $student->academic_number,
                'password'                => $password,
            ],
        ], 201);
    }
}