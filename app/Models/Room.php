<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Room extends Model
{
    protected $fillable = [
        'uuid',
        'max_players',
        'expires_at',
        'creator_ip',
        'last_activity_at',
    ];

    protected $casts = [
        'max_players' => 'integer',
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();
        $model = static::query()->where($field, $value)->first();
        if (! $model) {
            return null;
        }

        if ($model->isExpired()) {
            abort(404);
        }

        return $model;
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    public function touchActivityIfStale(): void
    {
        $minutes = max(1, (int) config('moba.room_activity_touch_interval_minutes', 5));
        $hours = max(1, (int) config('moba.room_sliding_ttl_hours', 72));
        $now = now();

        static::query()
            ->whereKey($this->id)
            ->where(function ($q) use ($minutes, $now) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', $now->copy()->subMinutes($minutes));
            })
            ->update([
                'last_activity_at' => $now,
                'expires_at' => $now->copy()->addHours($hours),
                'updated_at' => $now,
            ]);
    }

    public static function countRecentCreationsForIp(?string $ip): int
    {
        if ($ip === null || $ip === '') {
            return 0;
        }

        return static::query()
            ->where('creator_ip', $ip)
            ->where('created_at', '>', now()->subHour())
            ->count();
    }

    /**
     * @return HasMany<RoomPlayer, Room>
     */
    public function players(): HasMany
    {
        return $this->hasMany(RoomPlayer::class);
    }

    /**
     * @return HasOne<RoomDraft, Room>
     */
    public function draft(): HasOne
    {
        return $this->hasOne(RoomDraft::class);
    }
}

