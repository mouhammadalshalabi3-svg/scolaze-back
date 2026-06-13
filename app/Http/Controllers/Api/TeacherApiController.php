<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\Models\AdminMessage;
use App\Models\Admin;
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
            'first_name'  => 'required|string',
            'last_name'   => 'required|string',
            'phone'       => 'required|string',
            'email'       => 'required|email|unique:teachers,email',
           'salary_type' => 'required|in:monthly,per_session',
            'photo'       => 'nullable|image|max:2048',
            'username'    => 'required|string|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('teachers', 'public');
        }

       
        $user = User::create([
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'role'     => 'teacher',
            'status'   => true,
        ]);

      
        $teacher = Teacher::create([
            'first_name'  => $request->first_name,
            'last_name'   => $request->last_name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'salary_type' => $request->salary_type,
            'photo'       => $photoPath,
            'user_id'     => $user->id,
        ]);

        return response()->json([
            'message' => 'تم إضافة الأستاذ بنجاح',
            'teacher' => [
                'teacher_id' => $teacher->id,
                'name'       => $teacher->first_name . ' ' . $teacher->last_name,
                'username'   => $user->username,
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

    \App\Models\AdminMessage::create([
        'title'      => $request->head,
        'message'    => $request->content,
        'is_read'    => false,
        'admin_id'   => $admin->id,
        'teacher_id' => $teacher->id,
        'parent_id'  => null,
    ]);

    return response()->json(['message' => 'تم إرسال الرسالة للأستاذ بنجاح']);
    }
}
