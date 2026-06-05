<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class HomeworkGrade extends Model
{
        use HasFactory;

    protected $fillable = [
        'mark',
        'homework_id',
        'student_id'
    ];

    public $timestamps = false;

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
