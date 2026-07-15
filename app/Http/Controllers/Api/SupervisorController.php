<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Supervisor;
use Illuminate\Support\Str;
class SupervisorController extends Controller
{
 public function store(Request $request)
{
    $request->validate([
        'full_name'      => 'required|string',
        'phone'          => 'required|string',
        'email'          => 'nullable|email|unique:supervisors,email',
        'monthly_salary' => 'required|numeric',
        'grade_id'       => 'required|exists:grades,id',
        'photo'          => 'nullable|image|max:2048',
    ]);

    [$firstName, $lastName] = array_pad(explode(' ', $request->full_name, 2), 2, '');

    do {
        $username = 'sup_' . Str::lower($firstName) . rand(100, 999);
    } while (User::where('username', $username)->exists());
    $password = Str::random(8);

    $user = User::create([
        'username' => $username,
        'password' => bcrypt($password),
        'role' => 'supervisor',
        'status' => true
    ]);

    $photoPath = null;
    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('supervisors', 'public');
    }

    $supervisor = Supervisor::create([
        'first_name'      => $firstName,
        'last_name'       => $lastName,
        'phone'           => $request->phone,
        'email'           => $request->email,
        'monthly_salary'  => $request->monthly_salary,
        'grade_id'        => $request->grade_id,
        'photo'           => $photoPath,
        'user_id'         => $user->id
    ]);

    return response()->json([
        'supervisor' => $supervisor,
        'credentials' => [
            'username' => $username,
            'password' => $password,
        ],
    ], 201);
}
}
