<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeachingAssignment;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\StudentGrade;
use Illuminate\Http\Request;


class SectionController extends Controller
{
    // GET /sections/{section_id}/students?sort_by=name|average
    public function getStudents(Request $request, $section_id)
    {
        $section = Section::findOrFail($section_id);
        $sortBy  = $request->get('sort_by', 'name'); // name أو average

        $students = $section->students()->with('grades')->get()
            ->map(function ($s) {
                return [
                    'student_id' => $s->student_id,
                    'name'       => $s->first_name . ' ' . $s->last_name,
                    'photo'      => $s->photo,
                    'average'    => round($s->grades->avg('mark') ?? 0, 2),
                ];
            });

        $sorted = $sortBy === 'average'
            ? $students->sortByDesc('average')->values()
            : $students->sortBy('name')->values();

        return response()->json([
            'section'  => $section->name,
            'students' => $sorted,
        ]);
    }

    // يرجع المواد مع أستاذها في هاد الصف
    public function getSubjects($section_id)
    {
        $section = Section::findOrFail($section_id);

      $assignments = $section->assignments()  ->with('subject', 'teacher')->get()
        ->map(fn($a) => [
        'subject_id'   => $a->subject->subject_id,
        'subject_name' => $a->subject->name,
        'teacher'      => [
            'teacher_id' => $a->teacher->teacher_id,
            'name'       => $a->teacher->first_name . ' ' . $a->teacher->last_name,
            'photo'      => $a->teacher->photo,
            'email'      => $a->teacher->email,
        ],
    ]);

        return response()->json([
            'section'  => $section->name,
            'subjects' => $assignments,
        ]);
    }

    // GET /sections/{section_id}/schedule
   public function getSchedule($section_id)
{
    $section = Section::findOrFail($section_id);

    $assignmentIds = \App\Models\TeachingAssignment::where('section_id', $section_id)->pluck('id');

    $schedules = Schedule::whereIn('assignment_id', $assignmentIds)
        ->with(['period', 'teachingAssignment.subject', 'teachingAssignment.teacher'])->get()
        ->map(fn($s) => [
            'day'     => $s->day_of_week,
            'period'  => [
                'number'     => $s->period->Period_number,
                'start_time' => $s->period->start_time,
                'end_time'   => $s->period->end_time,
            ],
            'subject' => $s->teachingAssignment->subject->name,
            'teacher' => $s->teachingAssignment->teacher->first_name,
        ]);

    return response()->json([
        'section'  => $section->name,
        'schedule' => $schedules,
    ]);
}

    // يحدث الجدول مع فحص التعارض
    public function updateSchedule(Request $request, $section_id)
    {
        $request->validate([
            'schedule'               => 'required|array',
            'schedule.*.day'         => 'required|string',
            'schedule.*.period_id'   => 'required|exists:periods,period_id',
            'schedule.*.assignment_id' => 'required|exists:teaching_assignments,assignment_id',
        ]);

        // فحص التعارض: نشوف إذا الأستاذ عندو حصة بنفس اليوم والفترة بصف ثاني
        foreach ($request->schedule as $slot) {
            $assignment = \App\Models\TeachingAssignment::findOrFail($slot['assignment_id']);
            $teacherId  = $assignment->teacher_id;

            $conflict = Schedule::whereHas('assignment_id', fn($q) =>
                    $q->where('teacher_id', $teacherId)->where('section_id', '!=', $section_id)
                )->where('day_of_week', $slot['day'])->where('Period_id', $slot['period_id'])->exists();

            if ($conflict) {
                return response()->json([
                    'message' => 'تعارض في جدول الأستاذ يوم ' . $slot['day'] . ' في هاد الفترة',
                ], 422);
            }
        }

        // حذف الجدول القديم وكتابة الجديد
        $assignmentIds = collect($request->schedule)->pluck('assignment_id');
        Schedule::whereIn('assignment_id', $assignmentIds)->delete();

        foreach ($request->schedule as $slot) {
            Schedule::create([
                'day_of_week'   => $slot['day'],
                'Period_id'     => $slot['period_id'],
                'assignment_id' => $slot['assignment_id'],
            ]);
        }

        return response()->json(['message' => 'تم تحديث الجدول بنجاح']);
    }

    /
    // يخزن علامات الطلاب، يستقبل: exam_id + {student_id: mark}
    public function storeMarks(Request $request, $section_id)
{
    $request->validate([
        'exam_id' => 'required|exists:exams,id',
        'marks'   => 'required|array',
        'marks.*' => 'numeric|min:0',
    ]);

    $examId = $request->input('exam_id');

    foreach ($request->marks as $student_id => $mark) {
        StudentGrade::updateOrCreate(
            [
                'student_id' => $student_id,
                'exam_id'    => $examId,
            ],
            [
                'mark'         => $mark,
                'parent_viewed' => false,
            ]
        );
    }

    return response()->json([
        'message' => 'تم حفظ العلامات بنجاح',
        'count'   => count($request->marks),
    ]);
}
}
