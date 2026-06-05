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
        'file_url',
        'type',
        'category_id',
        'grade_id'
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
}
