<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPlayer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'player_token',
        'session_id',
        'display_name',
        'joined_at',
        'seat_number',
        'team',
        'is_leader',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Room, RoomPlayer>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}

