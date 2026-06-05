<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeacherSession extends Model
{
        use HasFactory;

    protected $fillable = [
        'session_date',
        'status',
        'notes',
        'schedule_id',
        'teacher_id'
    ];

    public $timestamps = false;

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
