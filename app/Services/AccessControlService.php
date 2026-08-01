<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\User;

class AccessControlService
{
    public static function canAccessSection(User $user, Section $section): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'student') {
            return $user->student?->section_id === $section->id;
        }

        if ($user->role === 'parent') {
            return $user->parentInfo
                ?->students()
                ->where('section_id', $section->id)
                ->exists() ?? false;
        }

        if ($user->role === 'teacher') {
            return $user->teacher
                ?->assignments()
                ->where('section_id', $section->id)
                ->exists() ?? false;
        }

        if ($user->role === 'supervisor') {
            return $user->supervisor?->grade_id !== null
                && $user->supervisor->grade_id === $section->grade_id;
        }

        return false;
    }

    public static function canAccessStudent(User $user, Student $student): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'student') {
            return $user->student?->id === $student->id;
        }

        if ($user->role === 'parent') {
            return $user->parentInfo?->id === $student->parent_id;
        }

        if ($user->role === 'teacher') {
            return $user->teacher
                ?->assignments()
                ->where('section_id', $student->section_id)
                ->exists() ?? false;
        }

        if ($user->role === 'supervisor') {
            return $user->supervisor?->grade_id !== null
                && $user->supervisor->grade_id === $student->section?->grade_id;
        }

        return false;
    }
}
