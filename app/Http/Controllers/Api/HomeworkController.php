<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Homework;
use App\Models\HomeworkGrade;
use App\Models\TeachingAssignment;

class HomeworkController extends Controller
{

    public function index($section_id)
    {
        $assignmentIds = TeachingAssignment::where('section_id', $section_id)->pluck('id');
        $homeworks = Homework::whereIn('assignment_id', $assignmentIds)->with('teachingAssignment.subject', 'teachingAssignment.teacher')->get()
            ->map(fn($h) => [
                'homework_id' => $h->id,
                'title'       => $h->title,
                'description' => $h->description,
                'due_date'    => $h->due_date,
                'subject'     => $h->teachingAssignment->subject->name,
                'teacher'     => $h->teachingAssignment->teacher->first_name . ' ' .
                                 $h->teachingAssignment->teacher->last_name,
            ]);

        return response()->json(['homeworks' => $homeworks]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:teaching_assignments,id',
            'title'         => 'required|string',
            'description'   => 'required|string',
            'due_date'      => 'required|date',
        ]);
        $homework = Homework::create([
            'assignment_id' => $request->assignment_id,
            'title'         => $request->title,
            'description'   => $request->description,
            'due_date'      => $request->due_date,
        ]);

        return response()->json([
            'message'  => 'تم إضافة الواجب بنجاح',
            'homework' => [
                'homework_id' => $homework->id,
                'title'       => $homework->title,
                'due_date'    => $homework->due_date,
            ],
        ], 201);
    }

    public function storeGrades(Request $request, $homework_id)
    {
        $request->validate([
            'grades'   => 'required|array',
            'grades.*' => 'numeric|min:0',
        ]);

        Homework::findOrFail($homework_id);
        foreach ($request->grades as $student_id => $mark) {
            HomeworkGrade::updateOrCreate(
                [
                    'homework_id' => $homework_id,
                    'student_id'  => $student_id,
                ],
                ['mark' => $mark]
            );
        }

        return response()->json([
            'message' => 'تم حفظ علامات الواجب بنجاح',
            'count'   => count($request->grades),
        ]);
    }

    public function destroy($homework_id)
    {
        $homework = Homework::findOrFail($homework_id);
        $homework->delete();

        return response()->json(['message' => 'تم حذف الواجب بنجاح']);
    }
}