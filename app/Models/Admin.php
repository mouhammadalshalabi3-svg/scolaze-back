<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Admin extends Model
{
    use HasFactory;

        protected $fillable = [
            'fullname',
            'phone',
            'email',
            'user_id'
        ];

        public function user()
        {
            return $this->belongsTo(User::class);
        }
        public function messages()
        {
            return $this->hasMany(AdminMessage::class);
        }
}
