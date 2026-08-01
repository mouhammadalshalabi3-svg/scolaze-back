<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\StaffAttendance;

class StaffAttendanceController extends Controller
{
    // POST /staff/attendance — الأدمن يسجل حضور الطاقم ليوم معين (مسار أدمن فقط)
    public function store(Request $request)
    {
        $request->validate([
            'date'      => 'required|date',
            'records'   => 'required|array',
            'records.*' => 'in:present,absent,late',
        ]);

        $admin = Admin::where('user_id', auth()->id())->first();

        $created = [];
        foreach ($request->records as $staffUserId => $status) {
            $record = StaffAttendance::updateOrCreate(
                [
                    'user_id'         => $staffUserId,
                    'attendance_date' => $request->date,
                ],
                [
                    'status'               => $status,
                    'recorded_by_admin_id' => $admin?->id,
                ]
            );
            $created[] = $record->id;
        }

        return response()->json([
            'message' => 'تم تسجيل حضور الطاقم بنجاح',
            'count'   => count($created),
        ]);
    }

    // GET /staff/attendance?date=YYYY-MM-DD
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $records = StaffAttendance::where('attendance_date', $request->date)
            ->get()
            ->map(fn($r) => [
                'user_id' => $r->user_id,
                'status'  => $r->status,
            ]);

        return response()->json([
            'date'    => $request->date,
            'records' => $records,
        ]);
    }
}
