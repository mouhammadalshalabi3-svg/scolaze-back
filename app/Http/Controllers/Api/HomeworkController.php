<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Homework;
use App\Models\HomeworkGrade;
use App\Models\Section;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Services\AccessControlService;
use App\Services\NotificationService;
class HomeworkController extends Controller
{

    // GET /students/{student_id}/homeworks — واجبات شعبة الطالب، مع علامته هو إذا رصدت
    public function forStudent($student_id)
    {
        $student = Student::findOrFail($student_id);

        if (!AccessControlService::canAccessStudent(auth()->user(), $student)) {
            return response()->json(['message' => 'غير مصرح لك بالاطلاع على بيانات هاد الطالب'], 403);
        }

        $assignmentIds = TeachingAssignment::where('section_id', $student->section_id)->pluck('id');

        $homeworks = Homework::whereIn('assignment_id', $assignmentIds)
            ->with(['teachingAssignment.subject', 'teachingAssignment.teacher', 'grades' => fn($q) => $q->where('student_id', $student_id)])
            ->get()
            ->map(fn($h) => [
                'homework_id' => $h->id,
                'title'       => $h->title,
                'description' => $h->description,
                'due_date'    => $h->due_date,
                'priority'    => $h->priority,
                'subject'     => $h->teachingAssignment->subject->name,
                'teacher'     => $h->teachingAssignment->teacher->first_name . ' ' .
                                 $h->teachingAssignment->teacher->last_name,
                'my_mark'     => $h->grades->first()?->mark,
            ]);

        return response()->json(['homeworks' => $homeworks]);
    }

    // GET /teacher/homeworks — كل واجبات الأستاذ الحالي (توكنو) عبر كل شعبه ومواده
    // فرق عن GET /sections/{id}/homeworks إنو هاد بيرجع واجباتو هو بس، مش واجبات كل أساتذة الشعبة
    public function myHomeworks()
    {
        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $assignmentIds = $teacher->assignments()->pluck('id');

        $homeworks = Homework::whereIn('assignment_id', $assignmentIds)
            ->with('teachingAssignment.subject', 'teachingAssignment.section')
            ->orderByDesc('due_date')
            ->get()
            ->map(fn($h) => [
                'homework_id' => $h->id,
                'title'       => $h->title,
                'description' => $h->description,
                'due_date'    => $h->due_date,
                'priority'    => $h->priority,
                'subject'     => $h->teachingAssignment->subject->name,
                'section'     => $h->teachingAssignment->section->name,
            ]);

        return response()->json(['homeworks' => $homeworks]);
    }

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
    // ★ عدّل (2026-08-19): صار يطلب section_id + subject_id بدل assignment_id —
    // الفرونت ما لازم يعرف/يعرض assignment_id أصلاً، هو رقم داخلي بالباك اند بس.
    // الأستاذ بيختار "المادة" و"الشعبة" (نفس القيم يلي طالعتلو من GET /teachers/{id}/sections
    // أو GET /sections/{id}/subjects)، والباك اند هو يلي بيلاقي الـ assignment المطابق ويتحقق
    // إنو هاد الأستاذ فعلاً معيّن يدرّس هاي المادة بهاي الشعبة.
    public function store(Request $request)
    {
        $request->validate([
            'section_id'  => 'required|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'title'       => 'required|string',
            'description' => 'required|string',
            'due_date'    => 'required|date',
            'priority'    => 'nullable|in:normal,medium,urgent',
        ]);

        $assignment = TeachingAssignment::with('section.students.user')
            ->where('section_id', $request->section_id)
            ->where('subject_id', $request->subject_id)
            ->first();

        if (!$assignment) {
            return response()->json(['message' => 'ما في أستاذ معيّن لهاي المادة بهاي الشعبة'], 422);
        }

        $user = auth()->user();
        if ($user->role !== 'admin' && $assignment->teacher_id !== $user->teacher?->id) {
            return response()->json(['message' => 'غير مصرح لك بإضافة واجب لهاد المادة'], 403);
        }

        $homework = Homework::create([
            'assignment_id' => $assignment->id,
            'title'         => $request->title,
            'description'   => $request->description,
            'due_date'      => $request->due_date,
            'priority'      => $request->priority ?? 'normal',
        ]);

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