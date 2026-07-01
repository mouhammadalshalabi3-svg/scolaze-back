<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Section extends Model
{
        use HasFactory;
protected $fillable = ['name', 'academic_year_id', 'grade_id'];
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
