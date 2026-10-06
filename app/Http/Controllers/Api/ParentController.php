<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\AdminMessage;
use App\Models\Attendance;

class ParentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'father_name' => 'required|string',
            'mother_name' => 'nullable|string',
            'phone'       => 'required|string',
            'email'       => 'nullable|email|unique:parents,email',
        ]);

        $student = Student::findOrFail($request->student_id);

        // لازم الباسورد يكون فريد بما إنو هوّ الوحيد يلي بيميّز حساب الأهل عند تسجيل الدخول
        do {
            $password = Str::random(8);
        } while (
            User::where('role', 'parent')
                ->get()
                ->contains(fn ($candidate) => Hash::check($password, $candidate->password))
        );

        $user = User::create([
            'username' => null,
            'password' => bcrypt($password),
            'role'     => 'parent',
            'status'   => true,
        ]);

        $parent = ParentModel::create([
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'user_id'     => $user->id,
        ]);

        $student->update(['parent_id' => $parent->id]);

        return response()->json([
            'message' => 'تم إضافة الأهل بنجاح',
            'parent'  => $parent,
            'credentials' => [
                'password' => $password,
            ],
        ], 201);
    }

    // GET /parent/children — أولاد ولي الأمر الحالي
    public function children()
    {
        $parent = auth()->user()->parentInfo;

        if (!$parent) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $children = $parent->students()->with('section')->get()->map(fn($s) => [
            'student_id'      => $s->id,
            'name'            => trim($s->first_name . ' ' . $s->last_name),
            'photo'           => $s->photo,
            'academic_number' => $s->academic_number,
            'section'         => $s->section?->name,
        ]);

        return response()->json(['children' => $children]);
    }

    // GET /parent/messages — الرسائل المرسلة لولي الأمر من الإدارة أو الأساتذة
    public function messages()
    {
        $parent = auth()->user()->parentInfo;

        if (!$parent) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $messages = AdminMessage::where('parent_id', $parent->id)
            ->with('teacher')
            ->orderBy('sent_at', 'desc')
            ->get()
            ->map(fn($m) => [
                'message_id'  => $m->id,
                'title'       => $m->title,
                'message'     => $m->message,
                'is_read'     => $m->is_read,
                'sent_at'     => $m->sent_at,
                'sender_type' => $m->sender_role,
                'sender_name' => $m->teacher
                    ? trim($m->teacher->first_name . ' ' . $m->teacher->last_name)
                    : 'الإدارة',
            ]);

        return response()->json(['messages' => $messages]);
    }

    // POST /parent/messages/{id}/read
    public function markMessageRead($id)
    {
        $parent = auth()->user()->parentInfo;

        $message = AdminMessage::where('id', $id)
            ->where('parent_id', $parent?->id)
            ->firstOrFail();

        $message->update(['is_read' => true]);

        return response()->json(['message' => 'تم تعليم الرسالة كمقروءة']);
    }

    // POST /grades/{grade_id}/acknowledge — ولي الأمر يأكد إطلاعه على علامة ابنه
    public function acknowledgeGrade($grade_id)
    {
        $parent = auth()->user()->parentInfo;

        $grade = StudentGrade::with('student')->findOrFail($grade_id);

        if (!$parent || $grade->student->parent_id !== $parent->id) {
            return response()->json(['message' => 'غير مصرح لك بهاي العلامة'], 403);
        }

        $grade->update(['parent_viewed' => true]);

        return response()->json(['message' => 'تم تأكيد الاطلاع على العلامة']);
    }

    // POST /parent-verification — الطالب (بتوكنو هو) بيتحقق من كلمة سر ولي أمرو
    // لحتى يفتح "وضع الأهل" جوا تطبيقو، وبيرجع علامات وحضور الطالب نفسو
    public function verifyParentAccess(Request $request)
    {
        $request->validate([
            'parent_password' => 'required|string',
        ]);

        $user = auth()->user();

        if ($user->role !== 'student' || !$user->student) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $student = $user->student;
        $parent  = $student->parent;

        if (!$parent || !$parent->user) {
            return response()->json(['message' => 'ما في ولي أمر مرتبط بهاد الطالب'], 422);
        }

        if (!Hash::check($request->parent_password, $parent->user->password)) {
            return response()->json(['message' => 'كلمة سر ولي الأمر غير صحيحة'], 401);
        }

        $marks = StudentGrade::where('student_id', $student->id)
            ->with('subject', 'exam.subject')
            ->get()
            ->map(fn($g) => [
                'grade_id'      => $g->id,
                'subject'       => $g->exam->subject->name ?? $g->subject->name ?? null,
                'type'          => $g->type ?? 'main',
                'exam_type'     => $g->exam->exam_type ?? null,
                'exam_date'     => $g->exam->exam_date ?? null,
                'mark'          => $g->mark,
                'total_mark'    => $g->exam->total_mark ?? 100,
                'parent_viewed' => $g->parent_viewed,
            ]);

        $records = Attendance::where('student_id', $student->id)->get();
        $total   = $records->count();
        $present = $records->where('status', 'present')->count();
        $absent  = $records->where('status', 'absent')->count();
        $late    = $records->where('status', 'late')->count();

        return response()->json([
            'marks'      => $marks,
            'attendance' => [
                'total'      => $total,
                'present'    => $present,
                'absent'     => $absent,
                'late'       => $late,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 2) . '%' : '0%',
            ],
        ]);
    }
}