<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\LibraryItem;

class SubjectApiController extends Controller
{
    
    // كل المواد مصنفة حسب السنة
    public function index()
    {
        $subjects = Subject::with([
            'teachingAssignments.section.academicYear',
            'teachingAssignments.teacher'
        ])->get();

        
        $classified = [];

        foreach ($subjects as $subject) {

            $years = $subject->teachingAssignments->map(fn($a) => $a->section->academicYear->name ?? 'غير محدد')->unique()->values();

            foreach ($years as $year) {
                $classified[$year][] = [
                    'subject_id'   => $subject->id,
                    'subject_name' => $subject->name,
                    'teachers'     => $subject->teachingAssignments
                        ->filter(fn($a) => ($a->section->academicYear->name ?? '') === $year)
                        ->map(fn($a) => $a->teacher->first_name . ' ' . $a->teacher->last_name)->unique()->values(),
                ];
            }

            if ($years->isEmpty()) {
                $classified['غير محدد'][] = [
                    'subject_id'   => $subject->id,
                    'subject_name' => $subject->name,
                    'teachers'     => [],
                ];
            }
        }

        return response()->json(['subjects' => $classified]);
    }

    // إضافة مادة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'description' => 'nullable|string',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:teachers,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $subject = Subject::create([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        if ($request->teacher_ids && $request->section_ids) {
            foreach ($request->section_ids as $section_id) {
                foreach ($request->teacher_ids as $teacher_id) {
                    TeachingAssignment::create([
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher_id,
                        'section_id' => $section_id,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'تم إضافة المادة بنجاح',
            'subject' => [
                'subject_id'   => $subject->id,
                'subject_name' => $subject->name,
            ],
        ], 201);
    }

   
    // معلومات مادة كاملة
    public function show($subject_id)
    {
        $subject = Subject::with([
            'teachingAssignments.teacher',
            'teachingAssignments.section.academicYear',
            'grades',
        ])->findOrFail($subject_id);

        // حساب نسبة النجاح
        $grades     = $subject->grades;
        $totalGrades = $grades->count();
        $passedGrades = $grades->filter(fn($g) => $g->mark >= 50)->count();
        $successRate  = $totalGrades > 0 ? round(($passedGrades / $totalGrades) * 100, 2) : 0;

        // الأساتذة
        $teachers = $subject->teachingAssignments
            ->map(fn($a) => [
                'teacher_id' => $a->teacher->id,
                'name'       => $a->teacher->first_name . ' ' . $a->teacher->last_name,
                'photo'      => $a->teacher->photo,
                'year'       => $a->section->academicYear->name ?? null,
            ])->unique('teacher_id')->values();

            return response()->json([
            'subject' => [
                'subject_id'   => $subject->id,
                'name'         => $subject->name,
                'description'  => $subject->description,
                'success_rate' => $successRate . '%',
                'teachers'     => $teachers,
                
            ],
        ]);
    }

    
    // رفع ملف أو فيديو للمكتبة
   public function addToLibrary(Request $request, $subject_id)
{
    $request->validate([
        'title'       => 'required|string',
        'grade_id'    => 'required|exists:grades,id',
        'category_id' => 'required|exists:library_categories,id',
        'file'        => 'required|file|mimes:pdf,mp4,mov,avi|max:51200',
    ]);

    Subject::findOrFail($subject_id);

    $filePath = $request->file('file')->store('library', 'public');
    $fileType = in_array(
        $request->file('file')->getClientOriginalExtension(),
        ['mp4', 'mov', 'avi']) ? 'video' : 'pdf';

    $item = \App\Models\LibraryItem::create([
        'title'       => $request->title,
        'type'        => $fileType,
        'file_uri'    => $filePath,
        'category_id' => $request->category_id,
        'grade_id'    => $request->grade_id,
        'subject_id'  => $subject_id,
    ]);

    return response()->json([
        'message' => 'تم رفع الملف بنجاح',
        'item'    => [
            'item_id'    => $item->id,
            'title'      => $item->title,
            'type'       => $item->type,
            'file_uri'   => $item->file_uri,
            'subject_id' => $item->subject_id,
            'grade_id'   => $item->grade_id,
        ],
    ], 201);
}
    

    
    // تعيين أستاذ لمادة
    public function assignTeacher(Request $request, $subject_id)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        $subject = Subject::findOrFail($subject_id);

    
        $exists = TeachingAssignment::where('subject_id', $subject_id)
            ->where('teacher_id', $request->teacher_id)->where('section_id', $request->section_id) ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'هاد الأستاذ مسبقاً معين لهاي المادة في هاد الصف',
            ], 422);
        }

        TeachingAssignment::create([
            'subject_id' => $subject_id,
            'teacher_id' => $request->teacher_id,
            'section_id' => $request->section_id,
        ]);

        return response()->json(['message' => 'تم تعيين الأستاذ للمادة بنجاح']);
    }
}