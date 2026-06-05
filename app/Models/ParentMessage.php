<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParentMessage extends Model
{
        use HasFactory;

    protected $fillable = [
        'message',
        'sent_at',
        'parent_id',
        'student_id'
    ];

    public $timestamps = false;

    public function parent()
    {
        return $this->belongsTo(ParentModel::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

}
