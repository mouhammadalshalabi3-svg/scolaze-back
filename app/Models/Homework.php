<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Homework extends Model
{
        use HasFactory;
 protected $table = 'homeworks';
    protected $fillable = [
        'title',
        'description',
        'due_date',
        'assignment_id'
    ];

    public $timestamps = false;

    public function assignment()
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function grades()
    {
        return $this->hasMany(HomeworkGrade::class);
    }
}
