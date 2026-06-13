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


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {

    // معلومات المستخدم الحالي
    Route::get('/profile', function (Request $request) {
        return $request->user();
    });

    // تسجيل خروج
    Route::post('/logout', [AuthController::class, 'logout']);

});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::post('/users', [UserController::class, 'store']);
    Route::post('/teachers', [TeacherApiController::class, 'store']);
    Route::post('/students', [StudentApiController::class, 'store']);
    Route::post('/parents', [ParentController::class, 'store']);
    Route::post('/supervisors', [SupervisorController::class, 'store']);

});


Route::middleware('auth:sanctum')->group(function () {
// Academic Year APIs
Route::get('/years/{year_id}/sections', [AcademicYearController::class, 'getSections']);
Route::get('/years/{year_id}/exams', [AcademicYearController::class, 'getExams']);
Route::get('/years/{year_id}/top-students', [AcademicYearController::class, 'getTopStudents']);
Route::post('/years/{year_id}/exam-schedule', [AcademicYearController::class, 'storeExamSchedule']);

// Section APIs
Route::get('/sections/{section_id}/students', [SectionController::class, 'getStudents']);
Route::get('/sections/{section_id}/subjects', [SectionController::class, 'getSubjects']);
Route::get('/sections/{section_id}/schedule', [SectionController::class, 'getSchedule']);
Route::post('/sections/{section_id}/schedule', [SectionController::class, 'updateSchedule']);
Route::post('/sections/{section_id}/marks', [SectionController::class, 'storeMarks']);

// Teacher APIs
Route::get('/teachers', [TeacherApiController::class, 'index']);
Route::get('/teachers/{teacher_id}/sections', [TeacherApiController::class, 'getSections']);
Route::delete('/teachers/{teacher_id}', [TeacherApiController::class, 'destroy']);
Route::post('/teachers/{teacher_id}/content', [TeacherApiController::class, 'content']);

// Student APIs
Route::delete('/students/{student_id}', [StudentApiController::class, 'destroy']);
Route::post('/students/{student_id}/content', [StudentApiController::class, 'content']);
Route::get('/students/{student_id}', [StudentApiController::class, 'show']);

// Subject APIs
Route::get('/subjects', [SubjectApiController::class, 'index']);
Route::post('/subjects', [SubjectApiController::class, 'store']);
Route::get('/subjects/{subject_id}', [SubjectApiController::class, 'show']);
Route::post('/subjects/{subject_id}/library', [SubjectApiController::class, 'addToLibrary']);
Route::post('/subjects/{subject_id}/teachers', [SubjectApiController::class, 'assignTeacher']);

// Announcement APIs
Route::get('/Annoucements', [AnnoucementApiController::class, 'index']);
Route::post('/Annoucements', [AnnoucementApiController::class, 'store']);


// Attendance APIs
Route::get('/sections/{section_id}/attendance', [AttendanceController::class, 'getSectionAttendance']);
Route::post('/sections/{section_id}/attendance', [AttendanceController::class, 'store']);
Route::get('/students/{student_id}/attendance', [AttendanceController::class, 'getStudentAttendance']);

// Homework APIs

Route::get('/sections/{section_id}/homeworks', [HomeworkController::class, 'index']);
Route::post('/homeworks', [HomeworkController::class, 'store']);
Route::post('/homeworks/{homework_id}/grades', [HomeworkController::class, 'storeGrades']);
Route::delete('/homeworks/{homework_id}', [HomeworkController::class, 'destroy']);

// Salary APIs

Route::get('/teachers/{teacher_id}/salary', [SalaryController::class, 'getTeacherSalary']);
Route::get('/salaries', [SalaryController::class, 'index']);
Route::post('/teachers/{teacher_id}/salary', [SalaryController::class, 'store']);

// Notification APIs

Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/{notification_id}/read', [NotificationController::class, 'markAsRead']);
});