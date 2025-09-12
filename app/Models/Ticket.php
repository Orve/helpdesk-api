<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'category_id',
        'user_id',
        'agent_id',
    ];

    public function requester() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent() {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function comments() {
        return $this->hasMany(Comment::class);
    }
}
