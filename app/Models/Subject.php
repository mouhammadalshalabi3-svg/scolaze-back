<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function grades()
    {
        return $this->hasManyThrough(
            StudentGrade::class,
            Exam::class,
            'subject_id',
            'exam_id',
            'id',
            'id'
        );
    }
}