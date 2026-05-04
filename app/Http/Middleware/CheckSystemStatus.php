<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemStatus
{
    /**
     * Handle an incoming request.
     * If the system is disabled, return the maintenance view for every route
     * except the toggle/status API endpoints and Livewire internal requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lockFile = storage_path('system.disabled');

        if (File::exists($lockFile)) {
            // Allow the system toggle/status endpoints to still work
            if ($request->is('api/system/toggle') || $request->is('api/system/status')) {
                return $next($request);
            }

            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}
