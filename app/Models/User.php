<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    
    protected $fillable = [
        'username',
        'password',
        'role',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
        */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'password' => 'hashed',
        ];
    }
    public function admin()
{
    return $this->hasOne(Admin::class);
}

public function teacher()
{
    return $this->hasOne(Teacher::class);
}

public function student()
{
    return $this->hasOne(Student::class);
}

public function parent()
{
    return $this->hasOne(ParentModel::class);
}

public function supervisor()
{
    return $this->hasOne(Supervisor::class);
}

public function notifications()
{
    return $this->hasMany(Notification::class);
}

public function announcements()
{
    return $this->hasMany(Announcement::class);
}

public function salaries()
{
    return $this->hasMany(Salary::class);
}

}
