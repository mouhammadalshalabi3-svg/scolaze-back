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

// تسجيل دخول الطالب/الأستاذ/الموجه/الأدمن — body: { username, password } — بيرجع token
Route::post('/login', [AuthController::class, 'login']);

// تسجيل دخول الأهل — body: { password } بس، بدون معرّف (كل ولي أمر كلمة سره فريدة)
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

    // الأدمن يراسل أستاذ — body: { head, content } — بيتخزن بجدول admin_messages مع sender_role=admin
    Route::post('/teachers/{teacher_id}/content', [TeacherApiController::class, 'content']);
    // الأدمن يراسل ولي أمر طالب — body: { head, content }
    Route::post('/students/{student_id}/content',  [StudentApiController::class, 'content']);
    // صندوق وارد الأدمن — كل الرسائل يلي بعتوها الأساتذة للإدارة (عبر /teacher/message-admin تحت)
    Route::get('/teacher-messages', [TeacherApiController::class, 'adminInbox']);
    // ملخص حصص الأستاذ للراتب — أدمن فقط
    Route::get('/teachers/{teacher_id}/sessions-summary', [PeriodAttendanceController::class, 'sessionsSummary']);

    // إحصائيات الداشبورد الرئيسية — أدمن فقط
    Route::get('/dashboard/overview', [DashboardApiController::class, 'overview']);

    // موافقة/رفض تبرير غياب — body: { status: approved|rejected } — الأدمن هو يلي يقرر (مو الموجه)
    // بيبعت إشعار لولي الأمر بالنتيجة. id هون هو id تبع AbsenceJustification (مو attendance_id).
    Route::patch('/justifications/{id}', [AbsenceJustificationController::class, 'resolve']);

    // حضور الطاقم (أدمن/أستاذ/موجه) — مسؤولية الأدمن حصراً، مو الموجه
    // GET /staff: لائحة الطاقم (user_id, role, name, photo) لبناء نموذج تسجيل الحضور
    Route::get('/staff', [SupervisorController::class, 'staff']);
    // POST /staff/attendance — body: { date, records: { user_id: status } } — status: present|absent|late
    Route::post('/staff/attendance', [StaffAttendanceController::class, 'store']);
    // GET /staff/attendance?date=YYYY-MM-DD — حالة حضور كل الطاقم بيوم معين
    Route::get('/staff/attendance', [StaffAttendanceController::class, 'index']);
});


Route::middleware('auth:sanctum')->group(function () {

    // بروفايلي (حسب الدور) — بيرجع { role, server_date, profile: {...} }
    // profile بيختلف شكلو حسب role: teacher/student/parent/supervisor/admin (شوف ProfileController)
    // server_date = تاريخ اليوم من السيرفر، للاستخدام بشاشات الموجه بدل ما تعتمد على تاريخ الجهاز
    Route::get('/me', [ProfileController::class, 'show']);

    // Academic Year
    Route::get('/years/{year_id}/sections', [AcademicYearController::class, 'getSections']);
    Route::get('/years/{year_id}/exams', [AcademicYearController::class, 'getExams']);
    Route::get('/years/{year_id}/top-students', [AcademicYearController::class, 'getTopStudents']);
    Route::get('/years/active', [AcademicYearController::class, 'getActiveYear']);
    Route::post('/search', [AcademicYearController::class, 'search']);

    Route::get('/grades/{grade_id}/sections', [GradeApiController::class, 'sections']);

    // Section — كل هاي محمية بـ AccessControlService: كل واحد بيشوف بس شعبتو
    // (طالب/أهل: شعبتو/شعبة ولده، أستاذ: الشعب يلي بيدرّسها، موجه: شعب صفو، أدمن: الكل)
    Route::get('/sections/{section_id}/students', [SectionController::class, 'getStudents']);
    Route::get('/sections/{section_id}/subjects', [SectionController::class, 'getSubjects']);
    Route::get('/sections/{section_id}/schedule', [SectionController::class, 'getSchedule']);
    // جدول امتحانات الشعبة — exam_id, exam_type, exam_date, total_mark, subject
    Route::get('/sections/{section_id}/exams', [SectionController::class, 'getExams']);


    //-------------------------------------------------------------------------------------------------


    // Teacher
    Route::get('/teachers', [TeacherApiController::class, 'index']);
    Route::get('/teachers/{teacher_id}/sections', [TeacherApiController::class, 'getSections']);
    // جدول دوام الأستاذ الحالي (توكن الأستاذ نفسو) — كل حصصو بكل الشعب يلي بيدرّسها
    Route::get('/teacher/schedule', [TeacherApiController::class, 'mySchedule']);
    // الأستاذ يراسل ولي أمر طالب من طلابه — body: { head, content } — 403 لو الطالب مش من شعبتو
    Route::post('/students/{student_id}/teacher-message', [TeacherApiController::class, 'messageParent']);
    // الرسائل الواصلة للأستاذ من الإدارة
    Route::get('/teacher/messages', [TeacherApiController::class, 'messages']);
    Route::post('/teacher/messages/{id}/read', [TeacherApiController::class, 'markMessageRead']);
    // الأستاذ يراسل الإدارة — body: { head, content } — بتظهر عند الأدمن بـ GET /teacher-messages
    Route::post('/teacher/message-admin', [TeacherApiController::class, 'messageAdmin']);


    //--------------------------------------------------------------------------------------------------


    // Student — بروفايل طالب كامل: بيانات شخصية + marks_history (مع grade_id وparent_viewed لكل علامة)
    // + evaluations + إحصائيات حضور. مسموح: الطالب نفسو / ولي أمرو / أستاذ شعبتو / الأدمن.
    Route::get('/students/{student_id}', [StudentApiController::class, 'show']);



    // Subject
    Route::get('/subjects', [SubjectApiController::class, 'index']);
    Route::get('/subjects/{subject_id}', [SubjectApiController::class, 'show']);


    //-----------------------------------------------------------------------------------


    // Announcements — الأدمن والأستاذ فيهم ينشئوا/يعدلوا/يحذفوا (كل واحد إعلاناته بس، الأدمن بيحذف/يعدل الكل)
    // index بيرجع بس الإعلانات المناسبة لدور المستخدم (صفو + all + teachers لو أستاذ + إعلاناته الخاصة)
    // كل إعلان فيه posted_by (اسم الأستاذ أو "الإدارة") وis_mine (هل أنا صاحبو)
    Route::get('/Annoucements', [AnnoucementApiController::class, 'index']);
    // POST body (multipart): title, content, target_role(all|teachers|first_grade|second_grade|third_grade),
    // type(normal|urgent|important|activity|rule_policy|administrative|academic), image(اختياري)
    Route::post('/Annoucements', [AnnoucementApiController::class, 'store']);
    // PUT — كل الحقول اختيارية (sometimes)، ابعت بس يلي بدك تعدلو
    Route::put('/announcements/{id}', [AnnoucementApiController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnoucementApiController::class, 'destroy']);


    //----------------------------------------------------------------------------------------------


    // Attendance
    Route::get('/sections/{section_id}/attendance', [AttendanceController::class, 'getSectionAttendance']);
    // تسجيل حضور طلاب شعبة ليوم كامل — body: { date, records: { student_id: status }, supervisor_id }
    // status: present|absent|late — أدمن أو موجه بس، والموجه ما بيقدر يسجل خارج صفو
    Route::post('/sections/{section_id}/attendance', [AttendanceController::class, 'store']);
    Route::get('/students/{student_id}/attendance', [AttendanceController::class, 'getStudentAttendance']);

    // Period Attendance — الموجه يسجل حضور الأستاذ بالحصة
    // GET قبل الـ POST: بترجع كل حصص هاد اليوم بالشعبة (period_id, teacher, subject, status)
    // status بيكون null لو الحصة لسا ما تسجل حضورها — استخدمها لبناء شاشة "شو باقي أسجّل"
    Route::get('/sections/{section_id}/period-attendance', [PeriodAttendanceController::class, 'sectionAttendance']);
    // body: { session_date, status: completed|absent|cancelled, supervisor_id }
    Route::post('/periods/{period_id}/attendance', [PeriodAttendanceController::class, 'store']);


    //-----------------------------------------------------------------------------------------

    // Homework
    Route::get('/sections/{section_id}/homeworks',   [HomeworkController::class, 'index']);
    // نفس واجبات الشعبة بس مربوطة مباشرة بـ student_id (بلا ما تحتاج تجيب section_id أول)
    // كل واجب معو my_mark = علامة هاد الطالب فيه (null لو لسا ما انرصدت)
    Route::get('/students/{student_id}/homeworks', [HomeworkController::class, 'forStudent']);
    // ★ جديد (2026-08-19): كل واجبات الأستاذ الحالي (توكنو) عبر كل شعبه ومواده — بلا ما تحتاج تلف
    // على كل شعبة لحالها زي /sections/{id}/homeworks. مرتبة الأحدث أولاً (due_date تنازلي).
    Route::get('/teacher/homeworks', [HomeworkController::class, 'myHomeworks']);
    // body: { assignment_id, title, description, due_date, priority(اختياري: normal|medium|urgent) }
    Route::post('/homeworks', [HomeworkController::class, 'store']);
    // PUT — كل الحقول اختيارية، بس للأستاذ صاحب المادة أو الأدمن
    Route::put('/homeworks/{homework_id}', [HomeworkController::class, 'update']);
    // body: { grades: { student_id: mark } }
    //Route::post('/homeworks/{homework_id}/grades', [HomeworkController::class, 'storeGrades']);
    Route::delete('/homeworks/{homework_id}', [HomeworkController::class, 'destroy']);


    //----------------------------------------------------------------------------------------------------------
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification_id}/read', [NotificationController::class, 'markAsRead']);

//----------------------------------------------------------------------------------------------------->

    // Parent — كلهم بياخدوا ولي الأمر من التوكن نفسو (ما في داعي تمرر parent_id)
    Route::get('/parent/children', [ParentController::class, 'children']);
    // كل الرسائل الواصلة لولي الأمر — sender_type: admin أو teacher لكل رسالة
    Route::get('/parent/messages', [ParentController::class, 'messages']);
    Route::post('/parent/messages/{id}/read', [ParentController::class, 'markMessageRead']);
    // تأكيد اطلاع ولي الأمر عالعلامة — grade_id هون هو id سطر StudentGrade
    // (بتلاقيه بحقل grade_id ضمن marks_history يلي بيرجعو GET /students/{id})
    Route::post('/grades/{grade_id}/acknowledge', [ParentController::class, 'acknowledgeGrade']);
    // ★ جديد: الطالب (بتوكنو هو) بيتحقق من كلمة سر ولي أمرو — body: { parent_password }
    // لو صح، بيرجع علامات وإحصائيات حضور الطالب نفسو (لفتح "وضع الأهل" جوا تطبيق الطالب)
    Route::post('/parent-verification', [ParentController::class, 'verifyParentAccess']);

    // Absence justification — الأهل بس يقدموا/يشوفوا، الموافقة/الرفض عند الأدمن (فوق بقسم الأدمن)
    // GET: غيابات/تأخيرات الطالب مع حالة التبرير (justification) لكل وحدة إذا موجود
    Route::get('/students/{student_id}/absences', [AbsenceJustificationController::class, 'indexForStudent']);
    // POST body: { reason } — attendance_id بتلاقيه بحقل attendance_id ضمن الرد فوق
    // لازم تكون حالة الحضور absent أو late، وما يكون في تبرير مقدّم أصلاً (تبرير واحد لكل غياب)
    Route::post('/attendance/{attendance_id}/justification', [AbsenceJustificationController::class, 'store']);

    // Library (قراءة فقط — الرفع أدمن فقط عبر مسار تاني)
    Route::get('/library/categories', [LibraryController::class, 'categories']);
    // فلترة اختيارية: ?type=pdf أو ?type=video
    Route::get('/subjects/{subject_id}/library', [LibraryController::class, 'forSubject']);
});
