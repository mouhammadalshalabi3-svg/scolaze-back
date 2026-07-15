<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->status) {
            return response()->json([
                'message' => 'Account disabled'
            ], 403);
        }

        $token = $user->createToken('school-token')->plainTextToken;

      return response()->json([
    'token' => $token,
    'role'  => $user->role,
    'user'  => [
        'id'       => $user->id,
        'username' => $user->username,
        'role'     => $user->role,
    ]
]);
    }
    public function parentLogin(Request $request)
{
    $request->validate([
        'student_academic_number' => 'required|string',
        'password'                => 'required|string',
    ]);

    $student = \App\Models\Student::where('academic_number', $request->student_academic_number)->first();

    if (!$student || !$student->parent_id) {
        return response()->json(['message' => 'بيانات غير صحيحة'], 401);
    }

    $parent = $student->parent()->first();
    $user   = $parent?->user;

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'بيانات غير صحيحة'], 401);
    }

    if (!$user->status) {
        return response()->json(['message' => 'Account disabled'], 403);
    }

    $token = $user->createToken('school-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'role'  => $user->role,
        'user'  => [
            'id'   => $user->id,
            'role' => $user->role,
        ],
    ]);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

}