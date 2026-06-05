<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Salary extends Model
{
        use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'total_amount',
        'paid_status',
        'completed_sessions',
        'price_per_session',
        'working_days',
        'user_id'
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
