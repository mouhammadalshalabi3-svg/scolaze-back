<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
        use HasFactory;

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

}
