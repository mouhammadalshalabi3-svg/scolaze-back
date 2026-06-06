<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\SupervisorController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\GradeController;
Route::post('/teachers', [TeacherController::class, 'store']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {return $request->user();});

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum' , 'admin'])->post('/users', [UserController::class, 'store']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () { Route::post('/students', [StudentController::class, 'store']);});


Route::middleware(['auth:sanctum', 'admin'])->post('/teachers', [TeacherController::class, 'store']);
  Route::middleware(['auth:sanctum', 'admin'])->post('/parents', [ParentController::class, 'store']);
  Route::middleware(['auth:sanctum', 'admin'])->post('/supervisors', [SupervisorController::class, 'store']);
  Route::middleware(['auth:sanctum', 'admin'])->post('/academic-years', [AcademicYearController::class, 'store']);


Route::middleware(['auth:sanctum', 'admin'])->post('/grades', [GradeController::class, 'store']);
