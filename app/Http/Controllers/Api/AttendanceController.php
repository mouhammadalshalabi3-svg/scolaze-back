<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Section;
use App\Services\AccessControlService;
use App\Services\NotificationService;
class AttendanceController extends Controller
{

    public function getSectionAttendance($section_id)
    {
        $section = Section::findOrFail($section_id);

        if (!AccessControlService::canAccessSection(auth()->user(), $section)) {
            return response()->json(['message' => 'غير مصرح لك بالاطلاع على بيانات هاي الشعبة'], 403);
        }

        $attendance = Attendance::whereHas('student', fn($q) =>
                $q->where('section_id', $section_id))->with('student')->orderBy('attendance_date', 'desc')->get()
            ->map(fn($a) => [
                'attendance_id'   => $a->id,
                'date'            => $a->attendance_date,
                'status'          => $a->status,
                'student_id'      => $a->student->id,
                'student_name'    => $a->student->first_name . ' ' . $a->student->last_name,
            ]);

        return response()->json([
            'section'    => $section->name,
            'attendance' => $attendance,
        ]);
    }


    public function store(Request $request, $section_id)
    {
        $request->validate([
            'date'        => 'required|date',
            'records'     => 'required|array',
            'records.*'   => 'in:present,absent,late',
            'supervisor_id' => 'required|exists:supervisors,id',
        ]);
        $user = auth()->user();
        if ($user->role !== 'admin' && $user->role !== 'supervisor') {
            return response()->json(['message' => 'غير مصرح لك بتسجيل الحضور'], 403);
        }
        if ($user->role === 'supervisor' && $user->supervisor?->id !== (int) $request->supervisor_id) {
            return response()->json(['message' => 'لا يمكنك تسجيل الحضور باسم موجه آخر'], 403);
        }
        Section::findOrFail($section_id);

        $created = [];
        foreach ($request->records as $student_id => $status) {
            $record = Attendance::updateOrCreate(
                [
                    'student_id'      => $student_id,
                    'attendance_date' => $request->date,
                ],
                [
                    'status'        => $status,
                    'supervisor_id' => $request->supervisor_id,
                ]
            );
            $created[] = $record->id;
            if ($status === 'absent' || $status === 'late') {
    $student = Student::with('parent')->find($student_id);
    if ($student?->parent?->user_id) {
        $statusText = $status === 'absent' ? 'غائب' : 'متأخر';
        NotificationService::send(
            $student->parent->user_id,
            'تسجيل غياب',
            'ابنك/ابنتك ' . $student->first_name . ' سُجل ' . $statusText . ' بتاريخ ' . $request->date,
            'attendance'
        );
    }
}
        }

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح',
            'count'   => count($created),
        ]);
    }

    
    public function getStudentAttendance($student_id)
    {
        $student = Student::findOrFail($student_id);

        if (!AccessControlService::canAccessStudent(auth()->user(), $student)) {
            return response()->json(['message' => 'غير مصرح لك بالاطلاع على بيانات هاد الطالب'], 403);
        }

        $records = Attendance::where('student_id', $student_id)->orderBy('attendance_date', 'desc')->get();
        $total   = $records->count();
        $present = $records->where('status', 'present')->count();
        $absent  = $records->where('status', 'absent')->count();
        $late    = $records->where('status', 'late')->count();

        return response()->json([
            'student' => $student->first_name . ' ' . $student->last_name,
            'stats'   => [
                'total'   => $total,
                'present' => $present,
                'absent'  => $absent,
                'late'    => $late,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 2) . '%' : '0%',
            ],
            'records' => $records->map(fn($r) => [
                'date'   => $r->attendance_date,
                'status' => $r->status,
            ]),
        ]);
    }
}