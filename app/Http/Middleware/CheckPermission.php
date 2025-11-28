<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                abort(403, 'No tienes permiso para realizar esta acción.');
            }

            // Show 403 error page instead of redirect to avoid loops
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
