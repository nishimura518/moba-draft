<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class RoomPageController extends Controller
{
    public function show(Room $room): View
    {
        $room->touchActivityIfStale();

        $dir = public_path('images/characters');
        $images = [];
        $kamiDefaultSrc = null;

        if (File::isDirectory($dir)) {
            $images = collect(File::files($dir))
                ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'], true))
                ->sortBy(fn ($f) => (int) preg_replace('/\D+/', '', $f->getBasename('.'.$f->getExtension())))
                ->map(function ($f) {
                    $ext = $f->getExtension();
                    $base = $f->getBasename('.'.$ext);
                    return [
                        'id' => $base,
                        'src' => "/images/characters/{$base}.{$ext}",
                    ];
                })
                ->values()
                ->all();
        }

        $ruleDir = public_path('images/rule');
        if (File::isDirectory($ruleDir)) {
            $default = collect(File::files($ruleDir))
                ->first(function ($f) {
                    $ext = strtolower($f->getExtension());
                    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'], true)) {
                        return false;
                    }
                    return $f->getBasename('.'.$ext) === '0';
                });
            if ($default) {
                $ext = $default->getExtension();
                $kamiDefaultSrc = "/images/rule/0.{$ext}";
            }
        }

        return view('room', [
            'roomUuid' => $room->uuid,
            'images' => $images,
            'kamiDefaultSrc' => $kamiDefaultSrc,
        ]);
    }
}

