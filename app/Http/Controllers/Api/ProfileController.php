<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    // GET /me — بيانات البروفايل الكاملة حسب دور المستخدم
    public function show()
    {
        $user = auth()->user();

        return response()->json([
            'role'        => $user->role,
            'server_date' => now()->toDateString(),
            'profile'     => match ($user->role) {
                'admin'      => $this->adminProfile($user),
                'teacher'    => $this->teacherProfile($user),
                'student'    => $this->studentProfile($user),
                'parent'     => $this->parentProfile($user),
                'supervisor' => $this->supervisorProfile($user),
                default      => null,
            },
        ]);
    }

    private function adminProfile($user)
    {
        $admin = $user->admin;

        return [
            'id'       => $admin?->id,
            'fullname' => $admin?->fullname,
            'phone'    => $admin?->phone,
            'email'    => $admin?->email,
        ];
    }

    private function teacherProfile($user)
    {
        $teacher = $user->teacher()->with('subject')->first();

        return [
            'teacher_id'      => $teacher?->id,
            'name'            => trim(($teacher?->first_name ?? '') . ' ' . ($teacher?->last_name ?? '')),
            'phone'           => $teacher?->phone,
            'email'           => $teacher?->email,
            'photo'           => $teacher?->photo,
            'salary_type'     => $teacher?->salary_type,
            'rate_per_lesson' => $teacher?->rate_per_lesson,
            'subject'         => $teacher?->subject?->name,
            'sections_count'  => $teacher?->assignments()->distinct('section_id')->count('section_id') ?? 0,
        ];
    }

    private function studentProfile($user)
    {
        $student = $user->student()->with('section.academicYear', 'parent')->first();

        return [
            'student_id'      => $student?->id,
            'academic_number' => $student?->academic_number,
            'name'            => trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
            'photo'           => $student?->photo,
            'birth_date'      => $student?->birth_date,
            'gender'          => $student?->gender,
            'address'         => $student?->address,
            'section'         => $student?->section?->name,
            'year'            => $student?->section?->academicYear?->name,
            'parent_phone'    => $student?->parent?->phone,
        ];
    }

    private function parentProfile($user)
    {
        $parent = $user->parentInfo()->with('students.section')->first();

        return [
            'parent_id'   => $parent?->id,
            'father_name' => $parent?->father_name,
            'mother_name' => $parent?->mother_name,
            'phone'       => $parent?->phone,
            'email'       => $parent?->email,
            'children'    => $parent?->students->map(fn($s) => [
                'student_id'      => $s->id,
                'name'            => trim($s->first_name . ' ' . $s->last_name),
                'photo'           => $s->photo,
                'academic_number' => $s->academic_number,
                'section'         => $s->section?->name,
            ]) ?? [],
        ];
    }

    private function supervisorProfile($user)
    {
        $supervisor = $user->supervisor()->with('grade')->first();

        return [
            'supervisor_id'  => $supervisor?->id,
            'name'           => trim(($supervisor?->first_name ?? '') . ' ' . ($supervisor?->last_name ?? '')),
            'phone'          => $supervisor?->phone,
            'email'          => $supervisor?->email,
            'photo'          => $supervisor?->photo,
            'monthly_salary' => $supervisor?->monthly_salary,
            'grade'          => $supervisor?->grade?->name,
        ];
    }
}
