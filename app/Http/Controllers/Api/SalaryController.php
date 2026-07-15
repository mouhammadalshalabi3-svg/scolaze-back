<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Salary;
use App\Models\Teacher;

class SalaryController extends Controller
{
    // GET /salaries
    public function index()
    {
        $salaries = Salary::with('user')
            ->get()
            ->map(fn($s) => [
                'salary_id'          => $s->id,
                'year'               => $s->year,
                'month'              => $s->month,
                'total_amount'       => $s->total_amount,
                'paid_status'        => $s->paid_status,
                'completed_sessions' => $s->completed_sessions,
                'price_per_session'  => $s->price_per_session,
                'working_days'       => $s->working_days,
                'user'               => $s->user->username,
            ]);

        return response()->json(['salaries' => $salaries]);
    }

    // GET /teachers/{teacher_id}/salary
    public function getTeacherSalary($teacher_id)
    {
        $teacher = Teacher::findOrFail($teacher_id);

        $salaries = Salary::where('user_id', $teacher->user_id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(fn($s) => [
                'salary_id'    => $s->id,
                'year'         => $s->year,
                'month'        => $s->month,
                'total_amount' => $s->total_amount,
                'paid_status'  => $s->paid_status,
            ]);

        return response()->json([
            'teacher'  => $teacher->first_name . ' ' . $teacher->last_name,
            'salaries' => $salaries,
        ]);
    }

    // POST /teachers/{teacher_id}/salary
 public function store(Request $request, $teacher_id)
{
    $request->validate([
        'year'  => 'required|integer',
        'month' => 'required|integer|min:1|max:12',
    ]);

    $teacher = Teacher::with('assignments.periods')->findOrFail($teacher_id);

    $periodIds = $teacher->assignments->flatMap(fn($a) => $a->periods->pluck('id'));

    $records = \App\Models\PeriodAttendance::whereIn('period_id', $periodIds)
        ->whereYear('session_date', $request->year)
        ->whereMonth('session_date', $request->month)
        ->get();

    $completedSessions = $records->where('status', 'completed')->count();
    $workingDays = $records->where('status', 'completed')->pluck('session_date')->unique()->count();
    $ratePerSession = $teacher->rate_per_lesson ?? 0;
    $totalAmount = $completedSessions * $ratePerSession;

    $salary = Salary::updateOrCreate(
        ['user_id' => $teacher->user_id, 'year' => $request->year, 'month' => $request->month],
        [
            'total_amount'       => $totalAmount,
            'paid_status'        => 'pending',
            'completed_sessions' => $completedSessions,
            'price_per_session'  => $ratePerSession,
            'working_days'       => $workingDays,
        ]
    );

    return response()->json([
        'message' => 'تم احتساب الراتب تلقائياً',
        'salary'  => $salary,
    ], 201);
}

// PATCH /salaries/{salary_id}/mark-paid
public function markPaid($salary_id)
{
    $salary = Salary::findOrFail($salary_id);
    $salary->update(['paid_status' => 'paid']);

    return response()->json([
        'message' => 'تم تحديث حالة الدفع',
        'salary'  => $salary,
    ]);
}
}