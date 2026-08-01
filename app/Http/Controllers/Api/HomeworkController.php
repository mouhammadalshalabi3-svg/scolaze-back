<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Homework;
use App\Models\HomeworkGrade;
use App\Models\Section;
use App\Models\TeachingAssignment;
use App\Services\AccessControlService;
use App\Services\NotificationService;
class HomeworkController extends Controller
{

    public function index($section_id)
    {
        $section = Section::findOrFail($section_id);

        if (!AccessControlService::canAccessSection(auth()->user(), $section)) {
            return response()->json(['message' => 'غير مصرح لك بالاطلاع على بيانات هاي الشعبة'], 403);
        }

        $assignmentIds = TeachingAssignment::where('section_id', $section_id)->pluck('id');
        $homeworks = Homework::whereIn('assignment_id', $assignmentIds)->with('teachingAssignment.subject', 'teachingAssignment.teacher')->get()
            ->map(fn($h) => [
                'homework_id' => $h->id,
                'title'       => $h->title,
                'description' => $h->description,
                'due_date'    => $h->due_date,
                'priority'    => $h->priority,
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
            'priority'      => 'nullable|in:normal,medium,urgent',
        ]);
        $assignmentCheck = TeachingAssignment::findOrFail($request->assignment_id);
        $user = auth()->user();
        if ($user->role !== 'admin' && $assignmentCheck->teacher_id !== $user->teacher?->id) {
            return response()->json(['message' => 'غير مصرح لك بإضافة واجب لهاد المادة'], 403);
        }
        $homework = Homework::create([
            'assignment_id' => $request->assignment_id,
            'title'         => $request->title,
            'description'   => $request->description,
            'due_date'      => $request->due_date,
            'priority'      => $request->priority ?? 'normal',
        ]);

        $assignment = TeachingAssignment::with('section.students.user')->findOrFail($request->assignment_id);
        $userIds = $assignment->section->students->pluck('user_id');

        NotificationService::sendToMany($userIds, 'واجب جديد: ' . $homework->title, $homework->description, 'homework');
        return response()->json([
            'message'  => 'تم إضافة الواجب بنجاح',
            'homework' => [
                'homework_id' => $homework->id,
                'title'       => $homework->title,
                'due_date'    => $homework->due_date,
                'priority'    => $homework->priority,
            ],
        ], 201);
    }

    public function update(Request $request, $homework_id)
    {
        $request->validate([
            'title'       => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'due_date'    => 'sometimes|required|date',
            'priority'    => 'sometimes|required|in:normal,medium,urgent',
        ]);

        $homework = Homework::with('teachingAssignment')->findOrFail($homework_id);
        $user = auth()->user();
        if ($user->role !== 'admin' && $homework->teachingAssignment->teacher_id !== $user->teacher?->id) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هاد الواجب'], 403);
        }

        $homework->fill($request->only(['title', 'description', 'due_date', 'priority']));
        $homework->save();

        return response()->json([
            'message'  => 'تم تعديل الواجب بنجاح',
            'homework' => [
                'homework_id' => $homework->id,
                'title'       => $homework->title,
                'description' => $homework->description,
                'due_date'    => $homework->due_date,
                'priority'    => $homework->priority,
            ],
        ]);
    }

    public function storeGrades(Request $request, $homework_id)
    {
        $request->validate([
            'grades'   => 'required|array',
            'grades.*' => 'numeric|min:0',
        ]);

        $homework = Homework::with('teachingAssignment')->findOrFail($homework_id);
        $user = auth()->user();
        if ($user->role !== 'admin' && $homework->teachingAssignment->teacher_id !== $user->teacher?->id) {
            return response()->json(['message' => 'غير مصرح لك برصد علامات هاد الواجب'], 403);
        }
        foreach ($request->grades as $student_id => $mark) {
            HomeworkGrade::updateOrCreate(
                [
                    'homework_id' => $homework_id,
                    'student_id'  => $student_id,
                ],
                ['mark' => $mark]
            );
            $studentUserId = \App\Models\Student::find($student_id)?->user_id;
                     if ($studentUserId) {
                    NotificationService::send($studentUserId, 'علامة واجب جديدة', 'حصلت على علامة ' . $mark . ' بواجب جديد', 'homework_grade');
    }
                     }

        return response()->json([
            'message' => 'تم حفظ علامات الواجب بنجاح',
            'count'   => count($request->grades),
        ]);
    }

  public function destroy($homework_id)
    {
        $homework = Homework::with('teachingAssignment')->findOrFail($homework_id);
        $user = auth()->user();
        if ($user->role !== 'admin' && $homework->teachingAssignment->teacher_id !== $user->teacher?->id) {
            return response()->json(['message' => 'غير مصرح لك بحذف هاد الواجب'], 403);
        }
        $homework->delete();

        return response()->json(['message' => 'تم حذف الواجب بنجاح']);
    }
}