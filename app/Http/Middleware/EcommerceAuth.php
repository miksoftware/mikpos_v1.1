<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EcommerceAuth
{
    /**
     * Handle an incoming request.
     * Verifies the ecommerce branch is enabled and the customer is authenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branchSlug = $request->route('branch_slug');
        $branch = Branch::getEcommerceBranch($branchSlug);

        if (!$branch || !$branch->is_active || !$branch->ecommerce_enabled) {
            $hasAnyBranch = Branch::where('is_active', true)->where('ecommerce_enabled', true)->exists();
            if (!$hasAnyBranch) {
                abort(503, 'La tienda en línea no está disponible en este momento.');
            }
        }

        if ($branch) {
            URL::defaults(['branch_slug' => $branch->slug]);
            session([
                'ecommerce_branch_id' => $branch->id,
                'ecommerce_branch_slug' => $branch->slug,
            ]);
            app()->instance('ecommerce_branch', $branch);
        }

        if (!Auth::guard('customer')->check()) {
            return redirect()->route('shop.login');
        }

        $customer = Auth::guard('customer')->user();
        if ($customer && !$customer->is_active) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('shop.login')->with('error', 'Tu cuenta ha sido desactivada.');
        }

        return $next($request);
    }
}
