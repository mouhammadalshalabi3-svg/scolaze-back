<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StudentGrade extends Model
{
        use HasFactory;

    protected $fillable = [
        

        'mark',
        'parent_viewed',
        'exam_id',
        'student_id'
    ];

    

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
