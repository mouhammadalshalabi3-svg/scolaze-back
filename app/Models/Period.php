<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Period extends Model
{
    use HasFactory;

   protected $fillable = [
    'period_number',
    'start_time',
    'end_time',
    'day_of_week',
    'status',
    'assignment_id'
];

public function assignment()
{
    return $this->belongsTo(TeachingAssignment::class);
}
public function periodAttendances()
    {
        return $this->hasMany(PeriodAttendance::class);
    }
}
