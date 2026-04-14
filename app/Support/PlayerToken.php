<?php

namespace App\Support;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

final class PlayerToken
{
    public const HEADER = 'X-Player-Token';

    public static function cookieName(Room $room): string
    {
        return 'moba_pt_'.substr(hash('sha256', (string) $room->uuid), 0, 16);
    }

    public static function fromRequest(Request $request, Room $room, ?string $bodyToken = null): ?string
    {
        $candidates = [
            $request->header(self::HEADER),
            self::bearer($request),
            $request->cookie(self::cookieName($room)),
            $bodyToken,
        ];

        foreach ($candidates as $t) {
            if (is_string($t) && Str::isUuid($t)) {
                return $t;
            }
        }

        return null;
    }

    public static function makeCookie(Room $room, string $token): Cookie
    {
        $secure = (bool) config('session.secure', false);

        return cookie(
            self::cookieName($room),
            $token,
            60 * 24 * 14,
            '/',
            null,
            $secure,
            true,
            false,
            'lax'
        );
    }

    public static function forgetCookie(Room $room): Cookie
    {
        return cookie()->forget(self::cookieName($room), '/');
    }

    private static function bearer(Request $request): ?string
    {
        $auth = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(?<token>[A-Za-z0-9\-]{36})$/', $auth, $m) !== 1) {
            return null;
        }

        return $m['token'];
    }
}
