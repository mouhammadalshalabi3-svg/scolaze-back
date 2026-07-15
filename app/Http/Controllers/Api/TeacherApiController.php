<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\Models\AdminMessage;
use App\Models\Admin;
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
        'title'      => $request->head,
        'message'    => $request->content,
        'is_read'    => false,
        'admin_id'   => $admin->id,
        'teacher_id' => $teacher->id,
        'parent_id'  => null,
    ]);
    NotificationService::send($teacher->user_id, $request->head, $request->content, 'admin_message');

    return response()->json(['message' => 'تم إرسال الرسالة للأستاذ بنجاح']);
    }
}
