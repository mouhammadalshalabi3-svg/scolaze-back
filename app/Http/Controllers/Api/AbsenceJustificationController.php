<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AbsenceJustification;
use App\Models\Student;
use App\Services\AccessControlService;
use App\Services\NotificationService;

class AbsenceJustificationController extends Controller
{
    // GET /students/{student_id}/absences
    public function indexForStudent($student_id)
    {
        $student = Student::findOrFail($student_id);

        if (!AccessControlService::canAccessStudent(auth()->user(), $student)) {
            return response()->json(['message' => 'غير مصرح لك بالاطلاع على بيانات هاد الطالب'], 403);
        }

        $records = Attendance::where('student_id', $student_id)
            ->whereIn('status', ['absent', 'late'])
            ->with('justification')
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(fn($a) => [
                'attendance_id' => $a->id,
                'date'          => $a->attendance_date,
                'status'        => $a->status,
                'justification' => $a->justification ? [
                    'id'     => $a->justification->id,
                    'reason' => $a->justification->reason,
                    'status' => $a->justification->status,
                ] : null,
            ]);

        return response()->json(['absences' => $records]);
    }

    // POST /attendance/{attendance_id}/justification — ولي الأمر يقدم تبرير غياب
    public function store(Request $request, $attendance_id)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $user   = auth()->user();
        $parent = $user->parentInfo;

        if (!$parent) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $attendance = Attendance::with('student')->findOrFail($attendance_id);

        if ($attendance->student->parent_id !== $parent->id) {
            return response()->json(['message' => 'غير مصرح لك بتبرير هاد الغياب'], 403);
        }

        if (!in_array($attendance->status, ['absent', 'late'])) {
            return response()->json(['message' => 'ما في داعي تبرير — الطالب مو غايب أو متأخر بهاد اليوم'], 422);
        }

        if ($attendance->justification) {
            return response()->json(['message' => 'تم تقديم تبرير لهاد الغياب مسبقاً'], 422);
        }

        $justification = AbsenceJustification::create([
            'reason'        => $request->reason,
            'status'        => 'pending',
            'attendance_id' => $attendance_id,
            'parent_id'     => $parent->id,
        ]);

        $studentName = trim($attendance->student->first_name . ' ' . $attendance->student->last_name);
        Admin::with('user')->get()->each(function ($admin) use ($studentName) {
            if ($admin->user_id) {
                NotificationService::send(
                    $admin->user_id,
                    'تبرير غياب جديد',
                    'تم تقديم تبرير غياب جديد للطالب ' . $studentName . ' بانتظار الموافقة',
                    'absence_justification'
                );
            }
        });

        return response()->json([
            'message'      => 'تم إرسال التبرير بنجاح، بانتظار موافقة الإدارة',
            'justification' => $justification,
        ], 201);
    }

    // PATCH /justifications/{id} — الأدمن يوافق/يرفض (مسار أدمن فقط)
    public function resolve(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $justification = AbsenceJustification::with('attendance.student.parent')->findOrFail($id);

        $justification->update(['status' => $request->status]);

        $parentUserId = $justification->attendance->student->parent?->user_id;
        if ($parentUserId) {
            $statusText = $request->status === 'approved' ? 'تمت الموافقة على' : 'تم رفض';
            NotificationService::send(
                $parentUserId,
                'تبرير غياب',
                $statusText . ' تبرير الغياب المقدم',
                'absence_justification'
            );
        }

        return response()->json([
            'message'       => 'تم تحديث حالة التبرير',
            'justification' => $justification,
        ]);
    }
}
