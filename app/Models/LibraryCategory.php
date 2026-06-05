<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LibraryCategory extends Model
{
        use HasFactory;

    protected $fillable = [
        'name'
    ];

    public $timestamps = false;

    public function items()
    {
        return $this->hasMany(LibraryItem::class,'category_id');
    }
}
