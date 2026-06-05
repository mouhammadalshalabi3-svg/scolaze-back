<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicYear extends Model
{
        use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date'
    ];

    public $timestamps = false;

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
