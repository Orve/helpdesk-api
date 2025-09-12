<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // 🧩 Ticket関係
    public function tickets() {
        return $this->hasMany(Ticket::class, 'user_id'); // requesterとして
    }

    public function assignedTickets() {
        return $this->hasMany(Ticket::class, 'agent_id');
    }

    // 🧩 ロール判定
    public function isAdmin() {
        return $this->role === 'admin';
    }

    public function isAgent() {
        return $this->role === 'agent';
    }
}
