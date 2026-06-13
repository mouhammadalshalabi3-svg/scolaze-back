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
            'year'               => 'required|integer',
            'month'              => 'required|integer|min:1|max:12',
            'total_amount'       => 'required|numeric',
            'paid_status'        => 'required|in:pending,paid',
            'completed_sessions' => 'required|integer',
            'price_per_session'  => 'required|numeric',
            'working_days'       => 'required|integer',
        ]);

        $teacher = Teacher::findOrFail($teacher_id);

        $salary = Salary::create([
            'year'               => $request->year,
            'month'              => $request->month,
            'total_amount'       => $request->total_amount,
            'paid_status'        => $request->paid_status,
            'completed_sessions' => $request->completed_sessions,
            'price_per_session'  => $request->price_per_session,
            'working_days'       => $request->working_days,
            'user_id'            => $teacher->user_id,
        ]);

        return response()->json([
            'message' => 'تم تسجيل الراتب بنجاح',
            'salary'  => $salary,
        ], 201);
    }
}