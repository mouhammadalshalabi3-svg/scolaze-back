<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
        use HasFactory;

    protected $fillable = [
        'day_of_week',
        'period_id',
        'assignment_id'
    ];

    public $timestamps = false;

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function assignment()
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function sessions()
    {
        return $this->hasMany(TeacherSession::class);
    }
}
