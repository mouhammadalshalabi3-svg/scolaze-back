<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class AbsenceJustification extends Model
{
        use HasFactory;

    protected $fillable = [
        'reason',
        'status',
        'attendance_id',
        'parent_id'
    ];

    public $timestamps = false;

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function parent()
    {
        return $this->belongsTo(ParentModel::class);
    }
}
