<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
     use HasFactory;
     protected $table = 'attendance';

    protected $fillable = [
        'attendance_date',
        'status',
        'supervisor_id',
        'student_id'
    ];

    public $timestamps = false;

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function justification()
    {
        return $this->hasOne(AbsenceJustification::class);
    }
}
