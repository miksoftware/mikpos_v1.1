<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class CheckEcommerce
{
    /**
     * Check if the ecommerce shop is enabled for the requested branch.
     * Sets URL defaults, binds to container, and stores in session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branchSlug = $request->route('branch_slug');

        $branch = Branch::getEcommerceBranch($branchSlug);

        if ($branch && $branch->is_active && $branch->ecommerce_enabled) {
            URL::defaults(['branch_slug' => $branch->slug]);
            session([
                'ecommerce_branch_id' => $branch->id,
                'ecommerce_branch_slug' => $branch->slug,
            ]);
            app()->instance('ecommerce_branch', $branch);

            return $next($request);
        }

        abort(503, 'La tienda en línea no está disponible en este momento.');
    }
}
