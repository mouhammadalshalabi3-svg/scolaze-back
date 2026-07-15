<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Section;

class GradeApiController extends Controller
{
    public function sections($grade_id)
    {
        $grade = Grade::findOrFail($grade_id);

        $sections = Section::where('grade_id', $grade_id)
            ->withCount('students')
            ->get()
            ->map(fn($s) => [
                'section_id'      => $s->id,
                'name'            => $s->name,
                'students_count'  => $s->students_count,
            ]);

        return response()->json([
            'grade'    => $grade->name,
            'sections' => $sections,
        ]);
    }
}