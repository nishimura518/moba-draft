<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomDraft;
use App\Models\RoomPlayer;
use App\Support\PlayerToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class DraftController extends Controller
{
    private const BAN_PER_TEAM = 3;
    private const PICK_PER_TEAM = 4;

    /**
     * Turn script (strict step list):
     * - bans: left 3, right 3 (total 6)
     * - picks: left 4, right 4
     *
     * Flow: 左2BAN → 右2BAN → 左1BAN+1PICK → 右1BAN+1PICK → 左2PICK → 右2PICK → 左1PICK → 右1PICK
     */
    private const TURN_SCRIPT = [
        ['type' => 'ban', 'team' => 'left'],   // 1) 左 バン1
        ['type' => 'ban', 'team' => 'left'],   // 2) 左 バン2
        ['type' => 'ban', 'team' => 'right'],  // 3) 右 バン1
        ['type' => 'ban', 'team' => 'right'],  // 4) 右 バン2
        ['type' => 'ban', 'team' => 'left'],   // 5) 左 バン3
        ['type' => 'pick', 'team' => 'left'],  // 6) 左 ピック1
        ['type' => 'ban', 'team' => 'right'],  // 7) 右 バン3
        ['type' => 'pick', 'team' => 'right'], // 8) 右 ピック1
        ['type' => 'pick', 'team' => 'left'],  // 9) 左 ピック2
        ['type' => 'pick', 'team' => 'left'],  // 10) 左 ピック3
        ['type' => 'pick', 'team' => 'right'], // 11) 右 ピック2
        ['type' => 'pick', 'team' => 'right'], // 12) 右 ピック3
        ['type' => 'pick', 'team' => 'left'],  // 13) 左 ピック4
        ['type' => 'pick', 'team' => 'right'], // 14) 右 ピック4
    ];

    private const ROLE_TECHNICAL = 'technical';
    private const ROLE_TANK = 'tank';
    private const ROLE_DAMAGE = 'damage';

    private const TECHNICAL_IDS = [1, 3, 8, 11, 19, 20, 21, 33, 34];
    private const TANK_IDS = [5, 7, 12, 13, 17, 23, 24, 25, 27];

    public function start(Request $request, Room $room): JsonResponse
    {
        $room->touchActivityIfStale();

        [$player, $error] = $this->resolvePlayer($request, $room);
        if ($error) {
            return $error;
        }

        if (!$player->is_leader) {
            return response()->json([
                'error' => 'FORBIDDEN',
                'message' => 'リーダーのみ操作できます。',
            ], Response::HTTP_FORBIDDEN);
        }

        $draft = DB::transaction(function () use ($room) {
            $room->loadCount('players');

            if ($room->players_count < 2) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, '参加者が2名以上必要です。');
            }

            $draft = RoomDraft::query()->where('room_id', $room->id)->lockForUpdate()->first();
            if (!$draft) {
                $draft = RoomDraft::create([
                    'room_id' => $room->id,
                    'status' => 'lobby',
                    'kami_draw' => [],
                    'left_bans' => [],
                    'right_bans' => [],
                    'left_picks' => [],
                    'right_picks' => [],
                    'turn_index' => 0,
                    'turn_started_at' => null,
                    'version' => 1,
                ]);
            }

            if ($draft->status === 'completed') {
                abort(Response::HTTP_CONFLICT, 'ドラフトは既に終了しています。');
            }

            $draft->kami_draw = $this->drawKamiImages();
            $draft->status = 'running';
            $draft->turn_index = 0;
            // カミドロー表示後、15秒経過してから左バン1の30秒タイマーを開始する
            $draft->kami_lock_until = now()->addSeconds(15);
            $draft->turn_started_at = null;
            $draft->version = $draft->version + 1;
            $draft->save();

            return $draft->fresh();
        }, 3);

        return response()->json([
            'draft' => $this->serializeDraft($draft),
        ]);
    }

    public function ban(Request $request, Room $room): JsonResponse
    {
        $room->touchActivityIfStale();

        [$player, $error] = $this->resolvePlayer($request, $room);
        if ($error) {
            return $error;
        }

        if (!$player->is_leader) {
            return response()->json([
                'error' => 'FORBIDDEN',
                'message' => 'リーダーのみ操作できます。',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'character' => ['required', 'string', 'min:1', 'max:64'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);

        $draft = DB::transaction(function () use ($room, $player, $validated) {
            $draft = RoomDraft::query()
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->first();

            if (!$draft || $draft->status !== 'running') {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'ドラフトが開始されていません。');
            }

            $draft = $this->applyTimeouts($draft);

            if ($this->isKamiLocked($draft)) {
                abort(Response::HTTP_CONFLICT, 'カミドロー表示中です。しばらくお待ちください。');
            }

            if (((int) $validated['expected_version']) !== ((int) $draft->version)) {
                abort(Response::HTTP_CONFLICT, '状態が更新されました。もう一度お試しください。');
            }

            $next = $this->nextAction($draft);
            if (!$next || $next['type'] !== 'ban') {
                abort(Response::HTTP_CONFLICT, '現在はバンの手番ではありません。');
            }
            if ($player->team !== $next['team']) {
                abort(Response::HTTP_CONFLICT, "現在の手番は {$next['team']} チームです。");
            }

            $character = $validated['character'];

            $leftBans = $draft->left_bans ?? [];
            $rightBans = $draft->right_bans ?? [];

            $already = array_merge($leftBans, $rightBans, $draft->left_picks ?? [], $draft->right_picks ?? []);
            if (in_array($character, $already, true)) {
                abort(Response::HTTP_CONFLICT, '既に選択済みです。');
            }

            if ($player->team === 'left') {
                if (count($leftBans) >= self::BAN_PER_TEAM) {
                    abort(Response::HTTP_CONFLICT, '左チームのBAN枠が埋まっています。');
                }
                $leftBans[] = $character;
                $draft->left_bans = $leftBans;
            } else {
                if (count($rightBans) >= self::BAN_PER_TEAM) {
                    abort(Response::HTTP_CONFLICT, '右チームのBAN枠が埋まっています。');
                }
                $rightBans[] = $character;
                $draft->right_bans = $rightBans;
            }

            $draft->turn_index = $draft->turn_index + 1;
            $draft->turn_started_at = now();
            $draft->version = $draft->version + 1;
            $draft->save();

            return $draft->fresh();
        }, 3);

        return response()->json([
            'draft' => $this->serializeDraft($draft),
        ]);
    }

    public function pick(Request $request, Room $room): JsonResponse
    {
        $room->touchActivityIfStale();

        [$player, $error] = $this->resolvePlayer($request, $room);
        if ($error) {
            return $error;
        }

        if (!$player->is_leader) {
            return response()->json([
                'error' => 'FORBIDDEN',
                'message' => 'リーダーのみ操作できます。',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'character' => ['required', 'string', 'min:1', 'max:64'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);

        $draft = DB::transaction(function () use ($room, $player, $validated) {
            $draft = RoomDraft::query()
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->first();

            if (!$draft || $draft->status !== 'running') {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'ドラフトが開始されていません。');
            }

            $draft = $this->applyTimeouts($draft);

            if ($this->isKamiLocked($draft)) {
                abort(Response::HTTP_CONFLICT, 'カミドロー表示中です。しばらくお待ちください。');
            }

            if (((int) $validated['expected_version']) !== ((int) $draft->version)) {
                abort(Response::HTTP_CONFLICT, '状態が更新されました。もう一度お試しください。');
            }

            $next = $this->nextAction($draft);
            if (!$next || $next['type'] !== 'pick') {
                abort(Response::HTTP_CONFLICT, '現在はピックの手番ではありません。');
            }
            if ($player->team !== $next['team']) {
                abort(Response::HTTP_CONFLICT, "現在の手番は {$next['team']} チームです。");
            }

            $character = $validated['character'];

            $leftBans = $draft->left_bans ?? [];
            $rightBans = $draft->right_bans ?? [];
            $leftPicks = $draft->left_picks ?? [];
            $rightPicks = $draft->right_picks ?? [];

            $already = array_merge($leftBans, $rightBans, $leftPicks, $rightPicks);
            if (in_array($character, $already, true)) {
                abort(Response::HTTP_CONFLICT, '既に選択済みです。');
            }

            // Role limits per team: tank 1, technical 1, damage 2
            $teamPicks = $player->team === 'left' ? $leftPicks : $rightPicks;
            $teamPicks = array_values(array_filter($teamPicks, fn ($c) => $c !== self::SKIP_TOKEN));
            $teamRoles = array_map(fn ($c) => $this->roleForCharacter((string) $c), $teamPicks);

            $role = $this->roleForCharacter($character);
            if ($role === self::ROLE_TANK && in_array(self::ROLE_TANK, $teamRoles, true)) {
                abort(Response::HTTP_CONFLICT, 'タンクは選択済みです');
            }
            if ($role === self::ROLE_TECHNICAL && in_array(self::ROLE_TECHNICAL, $teamRoles, true)) {
                abort(Response::HTTP_CONFLICT, 'テクニカルは選択済みです');
            }
            if ($role === self::ROLE_DAMAGE) {
                $damageCount = count(array_filter($teamRoles, fn ($r) => $r === self::ROLE_DAMAGE));
                if ($damageCount >= 2) {
                    abort(Response::HTTP_CONFLICT, 'ダメージは選択済みです');
                }
            }

            if ($player->team === 'left') {
                if (count($leftPicks) >= self::PICK_PER_TEAM) {
                    abort(Response::HTTP_CONFLICT, '左チームのPICK枠が埋まっています。');
                }
                $leftPicks[] = $character;
                $draft->left_picks = $leftPicks;
            } else {
                if (count($rightPicks) >= self::PICK_PER_TEAM) {
                    abort(Response::HTTP_CONFLICT, '右チームのPICK枠が埋まっています。');
                }
                $rightPicks[] = $character;
                $draft->right_picks = $rightPicks;
            }

            $draft->turn_index = $draft->turn_index + 1;
            $draft->turn_started_at = now();
            $draft->version = $draft->version + 1;

            if ($draft->turn_index >= count(self::TURN_SCRIPT)) {
                $draft->status = 'completed';
            }

            $draft->save();

            return $draft->fresh();
        }, 3);

        return response()->json([
            'draft' => $this->serializeDraft($draft),
        ]);
    }

    public function show(Request $request, Room $room): JsonResponse
    {
        $room->touchActivityIfStale();

        $draft = DB::transaction(function () use ($room) {
            $draft = RoomDraft::query()
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->first();

            if (!$draft) {
                return null;
            }

            if ($draft->status === 'running') {
                $draft = $this->applyTimeouts($draft);
            }

            return $draft->fresh();
        }, 3);

        return response()->json([
            'draft' => $draft ? $this->serializeDraft($draft) : null,
        ]);
    }

    private function roleForCharacter(string $character): string
    {
        if (preg_match('/^\d+$/', $character) !== 1) {
            return self::ROLE_DAMAGE;
        }

        $id = (int) $character;
        if (in_array($id, self::TECHNICAL_IDS, true)) {
            return self::ROLE_TECHNICAL;
        }
        if (in_array($id, self::TANK_IDS, true)) {
            return self::ROLE_TANK;
        }

        return self::ROLE_DAMAGE;
    }

    private function resolvePlayer(Request $request, Room $room): array
    {
        $playerToken = PlayerToken::fromRequest($request, $room);

        if (! $playerToken) {
            return [null, response()->json([
                'error' => 'UNAUTHORIZED',
                'message' => 'player_token が必要です。',
            ], Response::HTTP_UNAUTHORIZED)];
        }

        $player = RoomPlayer::query()
            ->where('room_id', $room->id)
            ->where('player_token', $playerToken)
            ->first();

        if (! $player) {
            return [null, response()->json([
                'error' => 'UNAUTHORIZED',
                'message' => '無効な player_token です。',
            ], Response::HTTP_UNAUTHORIZED)];
        }

        return [$player, null];
    }

    private function serializeDraft(RoomDraft $draft): array
    {
        $phase = $this->phase($draft);
        $nextAction = $this->nextAction($draft);

        return [
            'status' => $draft->status,
            'phase' => $phase,
            'kami_draw' => $draft->kami_draw ?? [],
            'kami_lock_until' => optional($draft->kami_lock_until)->toIso8601String(),
            'left_bans' => $draft->left_bans ?? [],
            'right_bans' => $draft->right_bans ?? [],
            'left_picks' => $draft->left_picks ?? [],
            'right_picks' => $draft->right_picks ?? [],
            'turn_index' => $draft->turn_index,
            'turn_started_at' => optional($draft->turn_started_at)->toIso8601String(),
            'version' => $draft->version,
            'next_action' => $nextAction,
            'updated_at' => optional($draft->updated_at)->toIso8601String(),
        ];
    }

    private const SKIP_TOKEN = '__SKIP__';
    private const TURN_SECONDS = 30;

    private function applyTimeouts(RoomDraft $draft): RoomDraft
    {
        $now = now();

        if ($draft->status === 'running' && $draft->kami_lock_until) {
            $lockUntil = $draft->kami_lock_until instanceof Carbon
                ? $draft->kami_lock_until
                : Carbon::parse($draft->kami_lock_until);
            if ($now->greaterThanOrEqualTo($lockUntil)) {
                $draft->kami_lock_until = null;
                $draft->turn_started_at = $now;
                $draft->version = $draft->version + 1;
                $draft->save();
            } else {
                return $draft;
            }
        }

        if ($draft->status === 'running' && !$draft->turn_started_at && !$draft->kami_lock_until) {
            $draft->turn_started_at = $now;
            $draft->version = $draft->version + 1;
            $draft->save();
        }

        if (!$draft->turn_started_at) {
            return $draft;
        }

        $started = $draft->turn_started_at instanceof Carbon ? $draft->turn_started_at : Carbon::parse($draft->turn_started_at);

        // Catch up multiple turns if needed
        while (
            $draft->status === 'running'
            && $draft->turn_index < count(self::TURN_SCRIPT)
            && $started->diffInSeconds($now) >= self::TURN_SECONDS
        ) {
            $step = self::TURN_SCRIPT[(int) $draft->turn_index] ?? null;
            if (!$step) {
                break;
            }

            if ($step['type'] === 'ban') {
                if (($step['team'] === 'left') && count($draft->left_bans ?? []) < self::BAN_PER_TEAM) {
                    $draft->left_bans = array_merge($draft->left_bans ?? [], [self::SKIP_TOKEN]);
                }
                if (($step['team'] === 'right') && count($draft->right_bans ?? []) < self::BAN_PER_TEAM) {
                    $draft->right_bans = array_merge($draft->right_bans ?? [], [self::SKIP_TOKEN]);
                }
            } else {
                if (($step['team'] === 'left') && count($draft->left_picks ?? []) < self::PICK_PER_TEAM) {
                    $draft->left_picks = array_merge($draft->left_picks ?? [], [self::SKIP_TOKEN]);
                }
                if (($step['team'] === 'right') && count($draft->right_picks ?? []) < self::PICK_PER_TEAM) {
                    $draft->right_picks = array_merge($draft->right_picks ?? [], [self::SKIP_TOKEN]);
                }
            }

            $draft->turn_index = $draft->turn_index + 1;
            $started = $started->copy()->addSeconds(self::TURN_SECONDS);
            $draft->turn_started_at = $started;
            $draft->version = $draft->version + 1;

            if ($draft->turn_index >= count(self::TURN_SCRIPT)) {
                $draft->status = 'completed';
            }
        }

        $draft->save();
        return $draft;
    }

    /**
     * @return array<int, array{src:string,name:string}>
     */
    private function drawKamiImages(): array
    {
        $dir = public_path('images/rule');
        if (!File::isDirectory($dir)) {
            return [];
        }

        $byBase = collect(File::files($dir))
            ->filter(function ($f) {
                $ext = strtolower($f->getExtension());
                if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'], true)) {
                    return false;
                }
                $base = $f->getBasename('.'.$ext);
                return preg_match('/^\d+$/', $base) === 1;
            })
            ->mapWithKeys(function ($f) {
                $ext = strtolower($f->getExtension());
                $base = $f->getBasename('.'.$ext);
                return [$base => $f->getFilename()];
            });

        // pick from 1..8 only
        $candidates = collect(range(1, 8))
            ->map(fn ($n) => (string) $n)
            ->filter(fn ($b) => $byBase->has($b))
            ->values();

        if ($candidates->count() < 2) {
            return [];
        }

        $pickedBases = $candidates->shuffle()->take(2)->values();

        return $pickedBases->map(function ($base) use ($byBase) {
            $name = $byBase->get($base);
            return [
                'src' => "/images/rule/{$name}",
                'name' => $base,
            ];
        })->all();
    }

    private function phase(RoomDraft $draft): string
    {
        if ($draft->status !== 'running') {
            return 'none';
        }

        if ($this->isKamiLocked($draft)) {
            return 'kami';
        }

        $next = $this->nextAction($draft);
        if (!$next) {
            return 'done';
        }

        return $next['type'];
    }

    private function nextAction(RoomDraft $draft): ?array
    {
        if ($draft->status !== 'running') {
            return null;
        }

        if ($this->isKamiLocked($draft)) {
            return null;
        }

        $idx = (int) $draft->turn_index;
        if ($idx < 0 || $idx >= count(self::TURN_SCRIPT)) {
            return null;
        }

        return self::TURN_SCRIPT[$idx];
    }

    private function isKamiLocked(RoomDraft $draft): bool
    {
        if ($draft->status !== 'running' || !$draft->kami_lock_until) {
            return false;
        }

        $lockUntil = $draft->kami_lock_until instanceof Carbon
            ? $draft->kami_lock_until
            : Carbon::parse($draft->kami_lock_until);

        return now()->lessThan($lockUntil);
    }
}

