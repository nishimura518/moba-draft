<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 部屋の寿命（スライディング）
    |--------------------------------------------------------------------------
    |
    | アクセスがあるたびに最大でこの時間先まで expires_at が延長されます。
    | room_activity_touch_interval_minutes 以上空いたアクセスでのみ DB 更新します。
    |
    */
    'room_sliding_ttl_hours' => (int) env('MOBA_ROOM_SLIDING_TTL_HOURS', 72),

    'room_activity_touch_interval_minutes' => (int) env('MOBA_ROOM_ACTIVITY_TOUCH_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | 同一 IP の部屋作成レート
    |--------------------------------------------------------------------------
    */
    'max_rooms_per_ip_per_hour' => (int) env('MOBA_MAX_ROOMS_PER_IP_PER_HOUR', 24),

];
