<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Teacher extends Model
{
    
    use HasFactory;
protected $fillable = [
    'first_name',
    'last_name',
    'phone',
    'email',
    'photo',
    'salary_type',
    'rate_per_lesson',
    'subject_id',
    'user_id'
];

public function subject()
{
    return $this->belongsTo(Subject::class);
}

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

}
