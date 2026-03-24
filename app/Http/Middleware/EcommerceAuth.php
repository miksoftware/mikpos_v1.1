<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EcommerceAuth
{
    /**
     * Handle an incoming request.
     * Verifies the ecommerce branch is enabled and the customer is authenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branchId = config('ecommerce.branch_id');

        if ($branchId) {
            $branch = Branch::find($branchId);
            if (!$branch || !$branch->is_active || !$branch->ecommerce_enabled) {
                abort(503, 'La tienda en línea no está disponible en este momento.');
            }
        } else {
            abort(503, 'La tienda en línea no está disponible en este momento.');
        }

        if (!Auth::guard('customer')->check()) {
            return redirect('/shop/login');
        }

        return $next($request);
    }
}
