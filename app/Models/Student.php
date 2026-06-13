<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Student extends Model
{
       use HasFactory;

    protected $fillable = [
        'academic_number',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'address',
        'photo',
        'user_id',
        'parent_id',
        'section_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(ParentModel::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function grades()
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function homeworkGrades()
    {
        return $this->hasMany(HomeworkGrade::class);
    }

    public function messages()
    {
        return $this->hasMany(  AdminMessage::class);
    }
}
