<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => '/');

        $proxies = env('TRUSTED_PROXIES', '*');
        if ($proxies === null || $proxies === '') {
            $proxies = '*';
        }
        $middleware->trustProxies(at: $proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', (string) $proxies)))));

        $middleware->append(SecurityHeaders::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('rooms:prune-expired')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
