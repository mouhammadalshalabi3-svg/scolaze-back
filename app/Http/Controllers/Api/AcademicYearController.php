<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\Subject;
use App\Services\NotificationService;

class AcademicYearController extends Controller
{
    
    public function getSections($year_id)
    {
        $year = AcademicYear::findOrFail($year_id);

        $sections = $year->sections()->with('grade')->get()
        ->map(fn($s) => [
                'section_id'   => $s->id,
                'section_name' => $s->name,
                'class_name'   => $s->grade->name,
            ]);

        return response()->json([
            'year'     => $year->name,
            'sections' => $sections,
        ]);
    }


    public function getExams($year_id)
    {
        $year = AcademicYear::findOrFail($year_id);
        $exams = Exam::whereHas('section', fn($q) => $q->where('academic_year_id', $year_id))->with('subject', 'section')->get()->map(fn($e) => [
                'exam_id'    => $e->id,
                'exam_type'  => $e->exam_type,
                'exam_date'  => $e->exam_date,
                'total_mark' => $e->total_mark,
                'subject'    => $e->subject->name,
                'section'    => $e->section->name,
            ]);

        return response()->json([
            'year'  => $year->name,
            'exams' => $exams,
        ]);
    }

   
    public function getTopStudents($year_id)
    {
        $students = Student::whereHas('section', fn($q) => $q->where('academic_year_id', $year_id))
            ->with(['grades', 'section'])->get()->map(function ($student) {
                $avg = $student->grades->avg('mark') ?? 0;
                return [
                    'student_id' => $student->id,
                    'name'       => $student->first_name . ' ' . $student->last_name,
                    'photo'      => $student->photo,
                    'average'    => round($avg, 2),
                    'section'    => $student->section->name ?? null,
                ];
            })
            ->sortByDesc('average')->take(5)->values();

        return response()->json(['top_students' => $students]);
    }

    
    public function storeExamSchedule(Request $request, $year_id)
    {
        $request->validate([
            'exam_number' => 'required|integer',
            'schedule'    => 'required|array',
        ]);

        AcademicYear::findOrFail($year_id);

        $sections = Section::where('academic_year_id', $year_id)->get();

        if ($sections->isEmpty()) {
            return response()->json(['message' => 'لا توجد شعب في هاي السنة'], 422);
        }

        $created = [];

        foreach ($request->schedule as $date => $subject_id) {
            if (!Subject::where('id', $subject_id)->exists()) {
                return response()->json([
                    'message' => "المادة رقم {$subject_id} غير موجودة",
                ], 422);
            }

            foreach ($sections as $section) {
                $exam = Exam::create([
                    'exam_type'  => 'exam_' . $request->exam_number,
                    'exam_date'  => $date,
                    'total_mark' => 100,
                    'section_id' => $section->id,
                    'subject_id' => $subject_id,
                ]);
                $created[] = $exam->id;
            }
        }
$userIds = Student::whereHas('section', fn($q) => $q->where('academic_year_id', $year_id))->pluck('user_id');
NotificationService::sendToMany($userIds, 'جدول امتحانات جديد', 'تم نشر جدول الامتحان رقم ' . $request->exam_number, 'exam_schedule');
        return response()->json([
            'message' => 'تم حفظ جدول الامتحانات بنجاح',
            'created' => count($created),
        ], 201);
    }

    
    public function getActiveYear()
    {
        $activeYear = AcademicYear::latest('id')->first();

        return response()->json([
            'active_year' => [
                'year_id'    => $activeYear->id,
                'name'       => $activeYear->name,
                'start_date' => $activeYear->start_date,
                'end_date'   => $activeYear->end_date,
            ]
        ]);
    }

   
    public function setActiveYear(Request $request)
    {
        $request->validate([
            'year_id' => 'required|exists:academic_years,id',
        ]);

        $year        = AcademicYear::findOrFail($request->year_id);
        $latestYear  = AcademicYear::latest('id')->first();
        $isReadOnly  = $year->id !== $latestYear->id;

        return response()->json([
            'active_year' => [
                'year_id'      => $year->id,
                'name'         => $year->name,
                'is_read_only' => $isReadOnly,
                'message'      => $isReadOnly
                    ? 'هاي سنة سابقة — للقراءة فقط'
                    : 'السنة الحالية — يمكن التعديل',
            ]
        ]);
    }

    
    public function search(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|min:2',
            'year_id' => 'required|exists:academic_years,id',
        ]);

        $name   = $request->name;
        $yearId = $request->year_id;

        $students = Student::whereHas('section', fn($q) => $q->where('academic_year_id', $yearId))
            ->where(fn($q) => $q->where('first_name', 'LIKE', "%{$name}%")->orWhere('last_name', 'LIKE', "%{$name}%"))->get()
            ->map(fn($s) => [
                'type'    => 'student',
                'id'      => $s->id,
                'name'    => $s->first_name . ' ' . $s->last_name,
                'photo'   => $s->photo,
                'section' => $s->section->name ?? null,
            ]);

        $teachers = Teacher::whereHas('assignments.section', fn($q) => $q->where('academic_year_id', $yearId))
            ->where(fn($q) => $q->where('first_name', 'LIKE', "%{$name}%")->orWhere('last_name', 'LIKE', "%{$name}%"))->get()
            ->map(fn($t) => [
                'type'  => 'teacher',
                'id'    => $t->id,
                'name'  => $t->first_name . ' ' . $t->last_name,
                'photo' => $t->photo,
            ]);

        $results = $students->merge($teachers)->values();

        return response()->json([
            'results' => $results,
            'count'   => $results->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|unique:academic_years,name',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $year = AcademicYear::create([
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        return response()->json([
            'message' => 'تم إنشاء السنة الدراسية بنجاح',
            'year'    => $year,
        ], 201);
    }
}
