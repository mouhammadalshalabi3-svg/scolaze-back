<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LibraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_uri',
        'type',
        'category_id',
        'grade_id',
        'subject_id',
    ];

    public $timestamps = false;

    public function category()
    {
        return $this->belongsTo(LibraryCategory::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}