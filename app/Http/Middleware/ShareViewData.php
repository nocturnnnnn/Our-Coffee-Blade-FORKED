<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareViewData
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $activeShift = null;

        if ($user !== null && $user->hasRole(UserRole::Admin, UserRole::Kasir)) {
            $shift = $user->activeShift()->first();
            if ($shift !== null) {
                $activeShift = [
                    'id' => $shift->id,
                    'opening_cash' => $shift->opening_cash,
                    'opened_at' => $shift->opened_at->toIso8601String(),
                ];
            }
        }

        View::share('activeShift', $activeShift);

        return $next($request);
    }
}
