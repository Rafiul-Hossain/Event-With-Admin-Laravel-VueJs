<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'profile_image'
    ];
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected $attributes = [
        'otp' => '0'
    ];

    
    

}
