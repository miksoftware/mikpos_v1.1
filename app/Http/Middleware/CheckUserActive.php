<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     * Ensure the authenticated user is active. If deactivated, log them out immediately.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->header('X-Livewire')) {
                    return response()->json([
                        'message' => 'Algo paso. Intenta mas tarde!'
                    ], 401)->header('X-Livewire-Redirect', route('login'));
                }

                return redirect()->route('login')->with('error', 'Algo paso. Intenta mas tarde!');
            }
        }

        return $next($request);
    }
}
