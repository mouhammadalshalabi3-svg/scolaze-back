<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\Models\AdminMessage;
use App\Models\Admin;
use App\Models\Period;
use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Support\Str;
class TeacherApiController extends Controller
{
     public function index()
    {
        $teachers = Teacher::with('user')->get()
            ->map(fn($t) => [
                'teacher_id'   => $t->id,
                'name'         => $t->first_name . ' ' . $t->last_name,
                'phone'        => $t->phone,
                'email'        => $t->email,
                'photo'        => $t->photo,
                'salary_type'  => $t->salary_type,
            ]);

        return response()->json(['teachers' => $teachers]);
    }
   
    // إضافة أستاذ جديد
    public function store(Request $request)
    {
$request->validate([
    'full_name'   => 'required|string',
    'phone'       => 'required|string',
    'email'       => 'nullable|email|unique:teachers,email',
    'rate_per_lesson' => 'required|numeric',
    'subject_id'  => 'nullable|exists:subjects,id',
    'photo'       => 'nullable|image|max:2048',
]);

[$firstName, $lastName] = array_pad(explode(' ', $request->full_name, 2), 2, '');
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('teachers', 'public');
        }
        do {
    $username = 'tch_' . Str::lower($request->first_name) . rand(100, 999);
} while (User::where('username', $username)->exists());
$password = Str::random(8);

       
        $user = User::create([
            'username' => $username,
            'password' => bcrypt($password),
            'role'     => 'teacher',
            'status'   => true,
        ]);

     $teacher = Teacher::create([
    'first_name'      => $firstName,
    'last_name'       => $lastName,
    'phone'           => $request->phone,
    'email'           => $request->email,
    'salary_type'     => 'per_session',
    'rate_per_lesson' => $request->rate_per_lesson,
    'subject_id'      => $request->subject_id,
    'photo'           => $photoPath,
    'user_id'         => $user->id,
]);
        return response()->json([
            'message' => 'تم إضافة الأستاذ بنجاح',
            'teacher' => [
                    'teacher' => [
                        'teacher_id' => $teacher->id,
                        'name'       => $teacher->first_name . ' ' . $teacher->last_name,
                    ],
                    'credentials' => [
                        'username' => $username,
                        'password' => $password,
                    ],
            ],
        ], 201);
    }

    // يرجع الصفوف اللي يدرسها الأستاذ
    public function getSections($teacher_id)
    {
        $teacher = Teacher::findOrFail($teacher_id);

        $sections = $teacher->assignments()->with('section.academicYear', 'subject')->get()
            ->map(fn($a) => [
                'section_id'   => $a->section->id,
                'section_name' => $a->section->name,
                'year'         => $a->section->academicYear->name,
                'subject'      => $a->subject->name,
            ]);

        return response()->json([
            'teacher'  => $teacher->first_name . ' ' . $teacher->last_name,
            'sections' => $sections,
        ]);
    }

    // جدول دوام الأستاذ الحالي
    public function mySchedule()
    {
        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $assignmentIds = $teacher->assignments()->pluck('id');

        $periods = Period::whereIn('assignment_id', $assignmentIds)
            ->with(['assignment.subject', 'assignment.section'])
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
                'section'       => $p->assignment->section->name,
            ]);

        return response()->json(['schedule' => $periods]);
    }

    // الأستاذ يراسل ولي أمر طالب من طلابه
    public function messageParent(Request $request, $student_id)
    {
        $request->validate([
            'head'    => 'required|string',
            'content' => 'required|string',
        ]);

        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $student = Student::with('parent')->findOrFail($student_id);

        $isMyStudent = $teacher->assignments()->where('section_id', $student->section_id)->exists();
        if (!$isMyStudent) {
            return response()->json(['message' => 'غير مصرح لك بمراسلة ولي أمر هاد الطالب'], 403);
        }

        if (!$student->parent) {
            return response()->json(['message' => 'ما في ولي أمر مرتبط بهاد الطالب'], 422);
        }

        AdminMessage::create([
            'title'       => $request->head,
            'message'     => $request->input('content'),
            'is_read'     => false,
            'admin_id'    => null,
            'teacher_id'  => $teacher->id,
            'parent_id'   => $student->parent->id,
            'sender_role' => 'teacher',
        ]);

        NotificationService::send($student->parent->user_id, $request->head, $request->input('content'), 'teacher_message');

        return response()->json(['message' => 'تم إرسال الرسالة لولي الأمر بنجاح']);
    }

    // GET /teacher/messages — رسائل الإدارة المرسلة لهاد الأستاذ
    public function messages()
    {
        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $messages = AdminMessage::where('teacher_id', $teacher->id)
            ->where('sender_role', 'admin')
            ->with('admin')
            ->orderBy('sent_at', 'desc')
            ->get()
            ->map(fn($m) => [
                'message_id' => $m->id,
                'title'      => $m->title,
                'message'    => $m->message,
                'is_read'    => $m->is_read,
                'sent_at'    => $m->sent_at,
            ]);

        return response()->json(['messages' => $messages]);
    }

    // POST /teacher/messages/{id}/read
    public function markMessageRead($id)
    {
        $teacher = auth()->user()->teacher;

        $message = AdminMessage::where('id', $id)
            ->where('teacher_id', $teacher?->id)
            ->where('sender_role', 'admin')
            ->firstOrFail();

        $message->update(['is_read' => true]);

        return response()->json(['message' => 'تم تعليم الرسالة كمقروءة']);
    }

    // POST /teacher/message-admin — الأستاذ يراسل الإدارة
    public function messageAdmin(Request $request)
    {
        $request->validate([
            'head'    => 'required|string',
            'content' => 'required|string',
        ]);

        $teacher = auth()->user()->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $admins = Admin::with('user')->get();

        foreach ($admins as $admin) {
            AdminMessage::create([
                'title'       => $request->head,
                'message'     => $request->input('content'),
                'is_read'     => false,
                'admin_id'    => $admin->id,
                'teacher_id'  => $teacher->id,
                'parent_id'   => null,
                'sender_role' => 'teacher',
            ]);

            if ($admin->user_id) {
                NotificationService::send($admin->user_id, $request->head, $request->input('content'), 'teacher_message');
            }
        }

        return response()->json(['message' => 'تم إرسال الرسالة للإدارة بنجاح']);
    }

    // GET /teacher-messages — الأدمن يشوف الرسائل يلي بعتها الأساتذة (أدمن فقط)
    public function adminInbox()
    {
        $admin = Admin::where('user_id', auth()->id())->firstOrFail();

        $messages = AdminMessage::where('admin_id', $admin->id)
            ->where('sender_role', 'teacher')
            ->with('teacher')
            ->orderBy('sent_at', 'desc')
            ->get()
            ->map(fn($m) => [
                'message_id'   => $m->id,
                'title'        => $m->title,
                'message'      => $m->message,
                'is_read'      => $m->is_read,
                'sent_at'      => $m->sent_at,
                'teacher_id'   => $m->teacher_id,
                'teacher_name' => $m->teacher ? trim($m->teacher->first_name . ' ' . $m->teacher->last_name) : null,
            ]);

        return response()->json(['messages' => $messages]);
    }


    // حذف أستاذ
    public function destroy($teacher_id)
    {
        $teacher = Teacher::findOrFail($teacher_id);
        $user    = User::findOrFail($teacher->user_id);

        $teacher->delete();
        $user->delete();

        return response()->json(['message' => 'تم حذف الأستاذ بنجاح']);
    }

    
    // مراسلة أستاذ
    public function content(Request $request, $teacher_id)
    {
    $request->validate([
        'head'    => 'required|string',
        'content' => 'required|string',
    ]);

    $teacher = Teacher::findOrFail($teacher_id);
    $admin   = Admin::where('user_id', auth()->id())->firstOrFail();

    AdminMessage::create([
        'title'       => $request->head,
        'message'     => $request->input('content'),
        'is_read'     => false,
        'admin_id'    => $admin->id,
        'teacher_id'  => $teacher->id,
        'parent_id'   => null,
        'sender_role' => 'admin',
    ]);
    NotificationService::send($teacher->user_id, $request->head, $request->input('content'), 'admin_message');

    return response()->json(['message' => 'تم إرسال الرسالة للأستاذ بنجاح']);
    }
}
