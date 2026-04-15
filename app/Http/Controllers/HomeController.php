<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home');
    }

    public function createRoom(Request $request): RedirectResponse
    {
        $ip = $request->ip();
        $max = (int) config('moba.max_rooms_per_ip_per_hour', 24);
        if ($max > 0 && Room::countRecentCreationsForIp($ip) >= $max) {
            return back()->withErrors([
                'room' => '短時間に作成できる部屋の上限に達しました。しばらくしてからお試しください。',
            ]);
        }

        $hours = max(1, (int) config('moba.room_sliding_ttl_hours', 72));
        $now = now();

        $room = Room::create([
            'uuid' => (string) Str::uuid(),
            'max_players' => 10,
            'creator_ip' => $ip,
            'expires_at' => $now->copy()->addHours($hours),
            'last_activity_at' => $now,
        ]);

        return redirect()->route('room.show', $room);
    }
}

