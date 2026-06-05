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
        'end_time'
    ];

    public $timestamps = false;

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
