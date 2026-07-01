<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ParentModel extends Model
{
       use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'father_name',
        'mother_name',
        'phone',
        'email',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function justifications()
    {
        return $this->hasMany(AbsenceJustification::class);
    }
public function messages()
    {
        return $this->hasMany(AdminMessage::class, 'parent_id');
    }
}
