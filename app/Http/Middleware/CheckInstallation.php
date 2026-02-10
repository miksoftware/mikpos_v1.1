<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     * Redirects to installation page if not installed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lockFile = storage_path('installed.lock');
        
        // If not installed and not on install route or livewire route, redirect to install
        if (!File::exists($lockFile) && !$request->is('install*') && !$request->is('livewire/*')) {
            return redirect('/install');
        }
        
        // If installed and on install route, redirect to login
        if (File::exists($lockFile) && $request->is('install*')) {
            return redirect('/login');
        }
        
        return $next($request);
    }
}
