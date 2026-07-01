<?php
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Str;
 use App\Services\NotificationService;
class StudentApiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'birth_date' => 'required|date',
            'gender'     => 'required|in:male,female',
            'address'    => 'required|string',
            'section_id' => 'required|exists:sections,id',
            'parent_id'  => 'required|exists:parents,id',
            'photo'      => 'nullable|image|max:2048',
        ]);
 
        $username = 'std_' . Str::lower($request->first_name) . rand(100, 999);
        $password = Str::random(8);
 
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students', 'public');
        }
 
        $user = User::create([
            'username' => $username,
            'password' => bcrypt($password),
            'role'     => 'student',
            'status'   => true,
        ]);
 
        do {
            $academicNumber = 'STD' . date('Y') . rand(1000, 9999);
        } while (Student::where('academic_number', $academicNumber)->exists());
 
        $student = Student::create([
            'first_name'      => $request->first_name,
            'last_name'       => $request->last_name,
            'birth_date'      => $request->birth_date,
            'gender'          => $request->gender,
            'address'         => $request->address,
            'section_id'      => $request->section_id,
            'parent_id'       => $request->parent_id,
            'photo'           => $photoPath,
            'user_id'         => $user->id,
            'academic_number' => $academicNumber,
        ]);
 
        return response()->json([
            'message'  => 'تم إضافة الطالب بنجاح',
            'student'  => [
                'student_id' => $student->id,
                'name'       => $student->first_name . ' ' . $student->last_name,
            ],
            'credentials' => [
                'username' => $username,
                'password' => $password,
            ],
        ], 201);
    }
 
    public function show($id)
    {
        $student = Student::with([
            'section.academicYear',
            'grades.exam.subject',
            'attendance',
        ])->findOrFail($id);
        $user = auth()->user();
        $isSelf    = $user->role === 'student' && $user->student?->id === $student->id;
        $isParent  = $user->role === 'parent'  && $user->parentInfo?->id === $student->parent_id;
        $isTeacher = $user->role === 'teacher' && $user->teacher
            ?->assignments()->where('section_id', $student->section_id)->exists();
        $isAdmin   = $user->role === 'admin';

        if (!($isSelf || $isParent || $isTeacher || $isAdmin)) {
            return response()->json(['message' => 'غير مصرح لك بالاطلاع على بيانات هاد الطالب'], 403);
        }
 
        $totalDays   = $student->attendance->count();
        $presentDays = $student->attendance->where('status', 'present')->count();
        $absentDays  = $totalDays - $presentDays;
 
        $marksHistory = $student->grades->map(fn($g) => [
            'subject'    => $g->exam->subject->name ?? null,
            'exam_type'  => $g->exam->exam_type ?? null,
            'exam_date'  => $g->exam->exam_date ?? null,
            'mark'       => $g->mark,
            'total_mark' => $g->exam->total_mark ?? null,
        ]);
 
        return response()->json([
            'student' => [
                'id'         => $student->id,
                'name'       => $student->first_name . ' ' . $student->last_name,
                'photo'      => $student->photo,
                'birth_date' => $student->birth_date,
                'gender'     => $student->gender,
                'address'    => $student->address,
                'section'    => $student->section->name ?? null,
                'year'       => $student->section->academicYear->name ?? null,
            ],
            'attendance' => [
                'total'   => $totalDays,
                'present' => $presentDays,
                'absent'  => $absentDays,
            ],
            'marks_history' => $marksHistory,
        ]);
    }
 
    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $user    = User::findOrFail($student->user_id);
        $student->delete();
        $user->delete();
        return response()->json(['message' => 'تم حذف الطالب بنجاح']);
    }
 
    public function content(Request $request, $student_id)
    {
        $request->validate([
            'head'    => 'required|string',
            'content' => 'required|string',
        ]);
 
        $student = Student::with('parent')->findOrFail($student_id);
        $admin   = Admin::where('user_id', auth()->id())->firstOrFail();
 
        AdminMessage::create([
            'title'      => $request->head,
            'message'    => $request->content,
            'is_read'    => false,
            'admin_id'   => $admin->id,
            'parent_id'  => $student->parent_id,
            'teacher_id' => null,
        ]);
 
        NotificationService::send($student->parent->user_id, $request->head, $request->content, 'admin_message');

        return response()->json(['message' => 'تم إرسال الرسالة للأهل بنجاح']);
    }
}