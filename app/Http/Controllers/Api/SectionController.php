<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeachingAssignment;
use App\Models\Period;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SectionController extends Controller
{
  
    public function getStudents(Request $request, $section_id)
    {
        $section = Section::findOrFail($section_id);
        $sortBy  = $request->get('sort_by', 'name');

        $students = $section->students()->with('grades')->get()
            ->map(function ($s) {
                return [
                    'student_id' => $s->id,              
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

 
    public function getSubjects($section_id)
    {
        $section = Section::findOrFail($section_id);

        $assignments = $section->assignments()->with('subject', 'teacher')->get()
            ->map(fn($a) => [
                'subject_id'   => $a->subject->id,             
                'subject_name' => $a->subject->name,
                'teacher'      => [
                    'teacher_id' => $a->teacher->id,          
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

    public function getSchedule($section_id)
    {
        $section = Section::findOrFail($section_id);

        $assignmentIds = TeachingAssignment::where('section_id', $section_id)->pluck('id');
        $periods = Period::whereIn('assignment_id', $assignmentIds)
            ->with(['assignment.subject', 'assignment.teacher'])
            ->orderByRaw("FIELD(day_of_week, 'sunday','monday','tuesday','wednesday','thursday')")
            ->orderBy('period_number')
            ->get()
            ->map(fn($p) => [
                'period_id'     => $p->id,
                'day'           => $p->day_of_week,
                'period_number' => $p->period_number,
                'start_time'    => $p->start_time,
                'end_time'      => $p->end_time,
                'subject'       => $p->assignment->subject->name,
                'teacher'       => $p->assignment->teacher->first_name . ' ' . $p->assignment->teacher->last_name,
            ]);

        return response()->json([
            'section'  => $section->name,
            'schedule' => $periods,
        ]);
    }

    
    public function updateSchedule(Request $request, $section_id)
    {
        Section::findOrFail($section_id);

        $request->validate([
            'schedule'                  => 'required|array',
            'schedule.*.day'            => 'required|in:sunday,monday,tuesday,wednesday,thursday',
            'schedule.*.period_number'  => 'required|integer|min:1',
            'schedule.*.start_time'     => 'required|date_format:H:i',
            'schedule.*.end_time'       => 'required|date_format:H:i|after:schedule.*.start_time',
            'schedule.*.assignment_id'  => 'required|exists:teaching_assignments,id',
        ]);


        foreach ($request->schedule as $slot) {
            $assignment = TeachingAssignment::findOrFail($slot['assignment_id']);
            $teacherId  = $assignment->teacher_id;

            $conflict = Period::where('day_of_week', $slot['day'])
                ->where('period_number', $slot['period_number'])
                ->whereHas('assignment', function ($q) use ($teacherId, $section_id) {
                    $q->where('teacher_id', $teacherId)
                      ->where('section_id', '!=', $section_id); 
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'message' => 'تعارض: الأستاذ عندو حصة ثانية يوم ' . $slot['day'] . ' في الحصة ' . $slot['period_number'],
                ], 422);
            }
        }

        $oldAssignmentIds = TeachingAssignment::where('section_id', $section_id)->pluck('id');
        Period::whereIn('assignment_id', $oldAssignmentIds)->delete(); 
        foreach ($request->schedule as $slot) {
            Period::create([
                'day_of_week'   => $slot['day'],
                'period_number' => $slot['period_number'],
                'start_time'    => $slot['start_time'],
                'end_time'      => $slot['end_time'],
                'assignment_id' => $slot['assignment_id'],
            ]);
        }
        $userIds = Section::find($section_id)->students->pluck('user_id');
        NotificationService::sendToMany($userIds, 'تحديث جدول الدوام', 'تم تحديث جدول دوام الشعبة', 'schedule_update');

        return response()->json(['message' => 'تم تحديث الجدول بنجاح']);
    }


    public function storeMarks(Request $request, $section_id)
    {
        Section::findOrFail($section_id);

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
                    'mark'          => $mark,
                    'parent_viewed' => false,
                ]
            );

            $studentUserId = \App\Models\Student::find($student_id)?->user_id;
if ($studentUserId) {
    NotificationService::send($studentUserId, 'علامة امتحان جديدة', 'تم رصد علامتك: ' . $mark, 'exam_grade');
}
        }

        return response()->json([
            'message' => 'تم حفظ العلامات بنجاح',
            'count'   => count($request->marks),
        ]);
    }
    public function store(Request $request)
{
    $request->validate([
        'name'             => 'required|string|max:255',
        'academic_year_id' => 'required|exists:academic_years,id',
        'grade_id'         => 'required|exists:grades,id',
    ]);

    $section = Section::create([
        'name'             => $request->name,
        'academic_year_id' => $request->academic_year_id,
        'grade_id'         => $request->grade_id,
    ]);

    return response()->json([
        'message' => 'تم إنشاء الشعبة بنجاح',
        'section' => $section,
    ], 201);
}
}