<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomPlayer;
use App\Support\PlayerToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RoomController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'max_players' => ['nullable', 'integer', 'min:2', 'max:10'],
        ]);

        $ip = $request->ip();
        $max = (int) config('moba.max_rooms_per_ip_per_hour', 24);
        if ($max > 0 && Room::countRecentCreationsForIp($ip) >= $max) {
            return response()->json([
                'error' => 'RATE_LIMIT',
                'message' => '短時間に作成できる部屋の上限に達しました。しばらくしてからお試しください。',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $hours = max(1, (int) config('moba.room_sliding_ttl_hours', 72));
        $now = now();

        $room = Room::create([
            'uuid' => (string) Str::uuid(),
            'max_players' => $validated['max_players'] ?? 10,
            'creator_ip' => $ip,
            'expires_at' => $now->copy()->addHours($hours),
            'last_activity_at' => $now,
        ]);

        return response()->json([
            'room' => $this->serializeRoom($room->fresh()),
        ], Response::HTTP_CREATED);
    }

    public function join(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['nullable', 'uuid'],
        ]);

        $playerToken = PlayerToken::fromRequest($request, $room, $validated['player_token'] ?? null);

        $result = DB::transaction(function () use ($room, $playerToken, $validated) {
            $room->refresh();

            if ($playerToken) {
                $existing = RoomPlayer::query()
                    ->where('room_id', $room->id)
                    ->where('player_token', $playerToken)
                    ->first();

                if ($existing) {
                    return [$existing->fresh(), false, null];
                }
            }

            // Lock the room rows to avoid duplicate seat numbers under concurrency
            RoomPlayer::query()
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->get(['id']);

            $count = RoomPlayer::query()
                ->where('room_id', $room->id)
                ->count();

            // Only 2 leaders can join as players (left/right leaders)
            if ($count >= 2) {
                return [null, null, 'LEADERS_ONLY'];
            }

            if ($count >= $room->max_players) {
                return [null, null, 'ROOM_FULL'];
            }

            $newToken = (string) Str::uuid();
            $seatNumber = $count + 1;
            $team = $seatNumber % 2 === 1 ? 'left' : 'right';
            $isLeader = $seatNumber <= 2;

            $player = RoomPlayer::create([
                'room_id' => $room->id,
                'seat_number' => $seatNumber,
                'team' => $team,
                'is_leader' => $isLeader,
                'player_token' => $newToken,
                'session_id' => null,
                'display_name' => "ユーザー{$seatNumber}",
            ]);

            return [$player->fresh(), true, null];
        }, 3);

        [$player, $created, $error] = $result;

        if ($error === 'ROOM_FULL') {
            return response()->json([
                'error' => 'ROOM_FULL',
                'message' => 'この部屋は満員です。',
            ], Response::HTTP_CONFLICT);
        }

        if ($error === 'LEADERS_ONLY') {
            return response()->json([
                'error' => 'LEADERS_ONLY',
                'message' => '参加できるのは左右リーダーの2名のみです。',
            ], Response::HTTP_CONFLICT);
        }

        $room->touchActivityIfStale();

        $response = response()->json([
            'room' => $this->serializeRoom($room->fresh()->load(['players'])),
            'you' => $player ? $this->serializePlayer($player) : null,
            'joined' => (bool) $created,
            'player_token' => $player?->player_token,
        ]);

        if ($player) {
            return $response->withCookie(PlayerToken::makeCookie($room, $player->player_token));
        }

        return $response;
    }

    public function show(Request $request, Room $room): JsonResponse
    {
        $room->touchActivityIfStale();
        $room->load(['players']);

        $playerToken = PlayerToken::fromRequest($request, $room);
        $you = null;

        if ($playerToken) {
            $youModel = $room->players->firstWhere('player_token', $playerToken);
            $you = $youModel ? $this->serializePlayer($youModel) : null;
        }

        return response()->json([
            'room' => $this->serializeRoom($room),
            'you' => $you,
        ]);
    }

    public function leave(Request $request, Room $room): JsonResponse
    {
        $playerToken = PlayerToken::fromRequest($request, $room);
        if (! $playerToken) {
            return response()->json([
                'error' => 'UNAUTHORIZED',
                'message' => 'player_token が必要です。',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $roomDeleted = DB::transaction(function () use ($room, $playerToken) {
            // lock rows to safely reseat
            RoomPlayer::query()
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->get(['id']);

            $me = RoomPlayer::query()
                ->where('room_id', $room->id)
                ->where('player_token', $playerToken)
                ->first();

            if (! $me) {
                return false;
            }

            $me->delete();

            $players = RoomPlayer::query()
                ->where('room_id', $room->id)
                ->orderBy('joined_at')
                ->orderBy('id')
                ->get();

            if ($players->count() === 0) {
                // Both leaders left -> delete room (cascade deletes players/drafts)
                $room->delete();

                return true;
            }

            $seat = 1;
            foreach ($players as $player) {
                $team = $seat % 2 === 1 ? 'left' : 'right';
                $isLeader = $seat <= 2;

                $player->seat_number = $seat;
                $player->team = $team;
                $player->is_leader = $isLeader;
                $player->display_name = "ユーザー{$seat}";
                $player->save();

                $seat++;
            }

            return false;
        }, 3);

        return response()->json([
            'left' => true,
            'room_deleted' => (bool) $roomDeleted,
        ])->withCookie(PlayerToken::forgetCookie($room));
    }

    private function serializeRoom(Room $room): array
    {
        $players = $room->relationLoaded('players')
            ? $room->players
                ->sortBy('joined_at')
                ->values()
                ->map(fn (RoomPlayer $p) => $this->serializePlayer($p))
                ->all()
            : [];

        return [
            'uuid' => $room->uuid,
            'max_players' => $room->max_players,
            'players' => $players,
        ];
    }

    private function serializePlayer(RoomPlayer $player): array
    {
        return [
            'seat_number' => $player->seat_number,
            'display_name' => $player->display_name,
            'team' => $player->team,
            'is_leader' => (bool) $player->is_leader,
            'joined_at' => optional($player->joined_at)->toIso8601String(),
        ];
    }
}
