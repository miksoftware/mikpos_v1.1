<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetEcommerceBranchDefaults
{
    /**
     * Set default URL parameters for ecommerce routes across web and Livewire requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branch = Branch::getEcommerceBranch();

        if ($branch) {
            $slug = $branch->slug ?: \Illuminate\Support\Str::slug($branch->name ?: 'sucursal-' . $branch->id);
            URL::defaults(['branch_slug' => $slug]);
            app()->instance('ecommerce_branch', $branch);

            if ($request->hasSession() && !$request->session()->has('ecommerce_branch_id')) {
                session([
                    'ecommerce_branch_id' => $branch->id,
                    'ecommerce_branch_slug' => $slug,
                ]);
            }
        }

        return $next($request);
    }
}
