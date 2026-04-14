<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;

class PruneExpiredRoomsCommand extends Command
{
    protected $signature = 'rooms:prune-expired';

    protected $description = '期限切れの部屋と関連データを削除します';

    public function handle(): int
    {
        $deleted = Room::query()->where('expires_at', '<=', now())->delete();

        $this->info("Deleted {$deleted} expired room(s).");

        return self::SUCCESS;
    }
}
