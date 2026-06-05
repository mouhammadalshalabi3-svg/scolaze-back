<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeachingAssignment extends Model
{
        use HasFactory;

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function homeworks()
    {
        return $this->hasMany(Homework::class);
    }

}
