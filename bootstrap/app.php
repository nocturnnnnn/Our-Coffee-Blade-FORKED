<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureKioskDevice;
use App\Http\Middleware\EnsureShiftIsOpen;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\ShareViewData;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'kiosk_device_token']);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'kiosk.device' => EnsureKioskDevice::class,
            'shift.open' => EnsureShiftIsOpen::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            ShareViewData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
