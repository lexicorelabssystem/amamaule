<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class MustChangePassword
{
    /**
     * Routes that should be accessible even when the password must be changed.
     *
     * @var list<string>
     */
    protected array $excludedRoutes = [
        'password.change',
        'password.change.store',
        'logout',
        'verification.*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }

    protected function shouldBypass(Request $request): bool
    {
        $currentRoute = Route::currentRouteName();

        if ($currentRoute === null) {
            return false;
        }

        foreach ($this->excludedRoutes as $excluded) {
            if (str_ends_with($excluded, '.*')) {
                $prefix = substr($excluded, 0, -2);

                if (str_starts_with($currentRoute, $prefix)) {
                    return true;
                }

                continue;
            }

            if ($currentRoute === $excluded) {
                return true;
            }
        }

        return false;
    }
}
