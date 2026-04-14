<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomDraft extends Model
{
    protected $fillable = [
        'room_id',
        'status',
        'kami_draw',
        'kami_lock_until',
        'left_bans',
        'right_bans',
        'left_picks',
        'right_picks',
        'turn_index',
        'turn_started_at',
        'version',
    ];

    protected $casts = [
        'kami_draw' => 'array',
        'kami_lock_until' => 'datetime',
        'left_bans' => 'array',
        'right_bans' => 'array',
        'left_picks' => 'array',
        'right_picks' => 'array',
        'turn_index' => 'integer',
        'turn_started_at' => 'datetime',
        'version' => 'integer',
    ];

    /**
     * @return BelongsTo<Room, RoomDraft>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}

