<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Exam extends Model
{
       use HasFactory;

    protected $fillable = [
        'exam_type',
        'exam_date',
        'total_mark',
        'section_id',
        'subject_id'
    ];

    public $timestamps = false;

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function grades()
    {
        return $this->hasMany(StudentGrade::class);
    }
}
