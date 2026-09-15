<?php

namespace App\Livewire\Shop\Concerns;

use App\Models\Branch;
use Illuminate\Support\Facades\URL;

trait WithShopBranch
{
    public function bootWithShopBranch(): void
    {
        $branch = Branch::getEcommerceBranch();
        if ($branch) {
            $slug = $branch->slug ?: \Illuminate\Support\Str::slug($branch->name ?: 'sucursal-' . $branch->id);
            URL::defaults(['branch_slug' => $slug]);
            app()->instance('ecommerce_branch', $branch);
        }
    }
}
