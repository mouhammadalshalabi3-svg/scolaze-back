<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeriodAttendance extends Model
{
    use HasFactory;

    protected $table = 'period_attendance';

    protected $fillable = [
        'period_id',
        'session_date',
        'status',
        'supervisor_id',
    ];

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }
}