<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PeriodAttendance;
use App\Models\Period;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Services\AccessControlService;
use Carbon\Carbon;

class PeriodAttendanceController extends Controller
{
    // GET /sections/{section_id}/period-attendance?date=YYYY-MM-DD
    // أسماء الأساتذة يلي إلهم حصص بهاد اليوم بالشعبة + حالة حضورهم (لو تسجلت)
    public function sectionAttendance(Request $request, $section_id)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $section = Section::findOrFail($section_id);

        if (!AccessControlService::canAccessSection(auth()->user(), $section)) {
            return response()->json(['message' => 'غير مصرح لك بالاطلاع على بيانات هاي الشعبة'], 403);
        }

        $dayOfWeek = strtolower(Carbon::parse($request->date)->format('l'));

        $assignmentIds = TeachingAssignment::where('section_id', $section_id)->pluck('id');

        $periods = Period::whereIn('assignment_id', $assignmentIds)
            ->where('day_of_week', $dayOfWeek)
            ->with([
                'assignment.teacher',
                'assignment.subject',
                'periodAttendances' => fn($q) => $q->where('session_date', $request->date),
            ])
            ->orderBy('period_number')
            ->get()
            ->map(fn($p) => [
                'period_id'     => $p->id,
                'period_number' => $p->period_number,
                'start_time'    => $p->start_time,
                'end_time'      => $p->end_time,
                'subject'       => $p->assignment->subject->name,
                'teacher_id'    => $p->assignment->teacher->id,
                'teacher'       => trim($p->assignment->teacher->first_name . ' ' . $p->assignment->teacher->last_name),
                'status'        => $p->periodAttendances->first()?->status,
            ]);

        return response()->json([
            'section'     => $section->name,
            'date'        => $request->date,
            'day_of_week' => $dayOfWeek,
            'periods'     => $periods,
        ]);
    }

    // POST /periods/{period_id}/attendance
    // الموجه يسجل حضور الأستاذ لحصة معينة بتاريخ معين
    public function store(Request $request, $period_id)
    {
        $request->validate([
            'session_date'  => 'required|date',
            'status'        => 'required|in:completed,absent,cancelled',
            'supervisor_id' => 'required|exists:supervisors,id',
        ]);

        $user = auth()->user();
        if ($user->role !== 'admin' && $user->role !== 'supervisor') {
            return response()->json(['message' => 'غير مصرح لك بتسجيل حضور الأستاذ'], 403);
        }
        if ($user->role === 'supervisor' && $user->supervisor?->id !== (int) $request->supervisor_id) {
            return response()->json(['message' => 'لا يمكنك التسجيل باسم موجه آخر'], 403);
        }

        $period = Period::with('assignment.teacher')->findOrFail($period_id);

        $record = PeriodAttendance::updateOrCreate(
            [
                'period_id'    => $period_id,
                'session_date' => $request->session_date,
            ],
            [
                'status'        => $request->status,
                'supervisor_id' => $request->supervisor_id,
            ]
        );

        return response()->json([
            'message' => 'تم تسجيل حضور الأستاذ بنجاح',
            'record'  => [
                'period_id'    => $period_id,
                'teacher'      => $period->assignment->teacher->first_name . ' ' . $period->assignment->teacher->last_name,
                'session_date' => $record->session_date,
                'status'       => $record->status,
            ],
        ], 201);
    }

    // GET /teachers/{teacher_id}/sessions-summary?year=2026&month=6
    // الأدمن يشوف ملخص حصص الأستاذ قبل ما يسجل الراتب
    public function sessionsSummary(Request $request, $teacher_id)
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $teacher = Teacher::with('assignments.periods.periodAttendances')->findOrFail($teacher_id);

        $periodIds = $teacher->assignments->flatMap(fn($a) => $a->periods->pluck('id'));

        $records = PeriodAttendance::whereIn('period_id', $periodIds)
            ->whereYear('session_date', $request->year)
            ->whereMonth('session_date', $request->month)
            ->get();

        $completedSessions = $records->where('status', 'completed')->count();
        $absentSessions    = $records->where('status', 'absent')->count();
        $cancelledSessions = $records->where('status', 'cancelled')->count();
        $workingDays       = $records->where('status', 'completed')
                                ->pluck('session_date')
                                ->unique()
                                ->count();

        return response()->json([
            'teacher'           => $teacher->first_name . ' ' . $teacher->last_name,
            'salary_type'       => $teacher->salary_type,
            'year'              => $request->year,
            'month'             => $request->month,
            'completed_sessions'=> $completedSessions,
            'absent_sessions'   => $absentSessions,
            'cancelled_sessions'=> $cancelledSessions,
            'working_days'      => $workingDays,
        ]);
    }
}