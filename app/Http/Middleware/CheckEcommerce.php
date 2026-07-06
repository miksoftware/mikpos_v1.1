<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEcommerce
{
    /**
     * Check if the ecommerce shop is enabled for the configured branch.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branch = Branch::getEcommerceBranch();

        if ($branch) {
            return $next($request);
        }

        abort(503, 'La tienda en línea no está disponible en este momento.');
    }
}
