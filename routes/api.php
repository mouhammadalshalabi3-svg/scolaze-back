<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\SupervisorController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\TeacherApiController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\SubjectApiController;
use App\Http\Controllers\Api\AnnoucementApiController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\HomeworkController;
use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PeriodAttendanceController;
use App\Http\Controllers\Api\GradeApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AbsenceJustificationController;
use App\Http\Controllers\Api\StaffAttendanceController;
use App\Http\Controllers\Api\LibraryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/parent-login', [AuthController::class, 'parentLogin']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

// ─── أدمن فقط ────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // إنشاء حسابات
    Route::post('/users',       [UserController::class, 'store']);
    Route::post('/teachers',    [TeacherApiController::class, 'store']);
    Route::post('/sections/{section_id}/students', [StudentApiController::class, 'store']);
    Route::post('/parents',     [ParentController::class, 'store']);
    Route::post('/supervisors', [SupervisorController::class, 'store']);

    // حذف أستاذ أو طالب
    Route::delete('/teachers/{teacher_id}', [TeacherApiController::class, 'destroy']);
    Route::delete('/students/{student_id}', [StudentApiController::class, 'destroy']);

    // إنشاء شعبة وسنة دراسية
    Route::post('/sections', [SectionController::class, 'store']);
    Route::post('/years', [AcademicYearController::class, 'store']);

    // تحديد السنة النشطة
    Route::post('/years/active', [AcademicYearController::class, 'setActiveYear']);

    // جدول امتحانات وجدول شعبة — الأدمن هو اللي يعدلهم
    Route::post('/years/{year_id}/exam-schedule', [AcademicYearController::class, 'storeExamSchedule']);
    Route::post('/sections/{section_id}/schedule', [SectionController::class, 'updateSchedule']);

    // علامات امتحانات
    Route::post('/sections/{section_id}/marks', [SectionController::class, 'storeMarks']);
    Route::post('/sections/{section_id}/evaluations', [SectionController::class, 'storeEvaluation']);

    // مواد — إضافة مادة وربط أستاذ فيها
    Route::post('/subjects', [SubjectApiController::class, 'store']);
    Route::post('/subjects/{subject_id}/teachers', [SubjectApiController::class, 'assignTeacher']);
    Route::post('/subjects/{subject_id}/connect-teacher', [SubjectApiController::class, 'connectTeacher']);
    // رواتب — كلها أدمن فقط (بيانات حساسة)
    Route::post('/teachers/{teacher_id}/salary', [SalaryController::class, 'store']);
    Route::patch('/salaries/{salary_id}/mark-paid', [SalaryController::class, 'markPaid']);
    Route::get('/teachers/{teacher_id}/salary',  [SalaryController::class, 'getTeacherSalary']);
    Route::get('/salaries',                      [SalaryController::class, 'index']);

    // مكتبة المادة — الإضافة للأدمن فقط
    Route::post('/subjects/{subject_id}/library', [SubjectApiController::class, 'addToLibrary']);

    // مراسلة أهل أو أستاذ
    Route::post('/teachers/{teacher_id}/content', [TeacherApiController::class, 'content']);
    Route::post('/students/{student_id}/content',  [StudentApiController::class, 'content']);
    // رسائل الأساتذة الواصلة للإدارة
    Route::get('/teacher-messages', [TeacherApiController::class, 'adminInbox']);
    // ملخص حصص الأستاذ للراتب — أدمن فقط
    Route::get('/teachers/{teacher_id}/sessions-summary', [PeriodAttendanceController::class, 'sessionsSummary']);

    // إحصائيات الداشبورد الرئيسية — أدمن فقط
    Route::get('/dashboard/overview', [DashboardApiController::class, 'overview']);

    // تبريرات الغياب — الأدمن هو يلي يوافق/يرفض
    Route::patch('/justifications/{id}', [AbsenceJustificationController::class, 'resolve']);

    // حضور الطاقم — مسؤولية الأدمن
    Route::get('/staff', [SupervisorController::class, 'staff']);
    Route::post('/staff/attendance', [StaffAttendanceController::class, 'store']);
    Route::get('/staff/attendance', [StaffAttendanceController::class, 'index']);
});


Route::middleware('auth:sanctum')->group(function () {

    // بروفايلي (حسب الدور)
    Route::get('/me', [ProfileController::class, 'show']);

    // Academic Year
    Route::get('/years/{year_id}/sections', [AcademicYearController::class, 'getSections']);
    Route::get('/years/{year_id}/exams', [AcademicYearController::class, 'getExams']);
    Route::get('/years/{year_id}/top-students', [AcademicYearController::class, 'getTopStudents']);
    Route::get('/years/active', [AcademicYearController::class, 'getActiveYear']);
    Route::post('/search', [AcademicYearController::class, 'search']);

    Route::get('/grades/{grade_id}/sections', [GradeApiController::class, 'sections']);

    // Section
    Route::get('/sections/{section_id}/students', [SectionController::class, 'getStudents']);
    Route::get('/sections/{section_id}/subjects', [SectionController::class, 'getSubjects']);
    Route::get('/sections/{section_id}/schedule', [SectionController::class, 'getSchedule']);
    Route::get('/sections/{section_id}/exams', [SectionController::class, 'getExams']);

    // Teacher
    Route::get('/teachers', [TeacherApiController::class, 'index']);
    Route::get('/teachers/{teacher_id}/sections', [TeacherApiController::class, 'getSections']);
    Route::get('/teacher/schedule', [TeacherApiController::class, 'mySchedule']);
    Route::post('/students/{student_id}/teacher-message', [TeacherApiController::class, 'messageParent']);
    Route::get('/teacher/messages', [TeacherApiController::class, 'messages']);
    Route::post('/teacher/messages/{id}/read', [TeacherApiController::class, 'markMessageRead']);
    Route::post('/teacher/message-admin', [TeacherApiController::class, 'messageAdmin']);

    // Student
    Route::get('/students/{student_id}', [StudentApiController::class, 'show']);

    // Subject
    Route::get('/subjects', [SubjectApiController::class, 'index']);
    Route::get('/subjects/{subject_id}', [SubjectApiController::class, 'show']);

    // Announcements — الأدمن والأستاذ فيهم ينشئوا/يعدلوا/يحذفوا (كل واحد إعلاناته)
    Route::get('/Annoucements', [AnnoucementApiController::class, 'index']);
    Route::post('/Annoucements', [AnnoucementApiController::class, 'store']);
    Route::put('/announcements/{id}', [AnnoucementApiController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnoucementApiController::class, 'destroy']);

    // Attendance
    Route::get('/sections/{section_id}/attendance', [AttendanceController::class, 'getSectionAttendance']);
    Route::post('/sections/{section_id}/attendance', [AttendanceController::class, 'store']);
    Route::get('/students/{student_id}/attendance', [AttendanceController::class, 'getStudentAttendance']);
    // Period Attendance — الموجه يسجل حضور الأستاذ بالحصة
    Route::post('/periods/{period_id}/attendance', [PeriodAttendanceController::class, 'store']);
    // Homework
    Route::get('/sections/{section_id}/homeworks',   [HomeworkController::class, 'index']);
    Route::post('/homeworks', [HomeworkController::class, 'store']);
    Route::put('/homeworks/{homework_id}', [HomeworkController::class, 'update']);
    Route::post('/homeworks/{homework_id}/grades', [HomeworkController::class, 'storeGrades']);
    Route::delete('/homeworks/{homework_id}', [HomeworkController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification_id}/read', [NotificationController::class, 'markAsRead']);

    // Parent
    Route::get('/parent/children', [ParentController::class, 'children']);
    Route::get('/parent/messages', [ParentController::class, 'messages']);
    Route::post('/parent/messages/{id}/read', [ParentController::class, 'markMessageRead']);
    Route::post('/grades/{grade_id}/acknowledge', [ParentController::class, 'acknowledgeGrade']);

    // Absence justification — الأهل بس يقدموا/يشوفوا، الموافقة/الرفض عند الأدمن (فوق بقسم الأدمن)
    Route::get('/students/{student_id}/absences', [AbsenceJustificationController::class, 'indexForStudent']);
    Route::post('/attendance/{attendance_id}/justification', [AbsenceJustificationController::class, 'store']);

    // Library (قراءة فقط — الرفع أدمن فقط)
    Route::get('/library/categories', [LibraryController::class, 'categories']);
    Route::get('/subjects/{subject_id}/library', [LibraryController::class, 'forSubject']);
});
