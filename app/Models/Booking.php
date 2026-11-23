<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'title',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'room_id' => 'integer',
        'user_id' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
