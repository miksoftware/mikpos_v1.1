<?php

namespace App\Livewire\Shop;

use App\Models\Branch;
use App\Models\EcommerceSetting;
use App\Services\CustomerSyncService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class BranchSelectorModal extends Component
{
    public bool $isOpen = false;
    public ?int $selectedBranchId = null;
    public bool $showCartWarning = false;
    public ?int $pendingBranchId = null;
    public string $pendingBranchName = '';

    public function mount(): void
    {
        $this->selectedBranchId = Branch::getEcommerceBranchId();

        // If in unified mode and user has not explicitly selected a branch in this session,
        // and there is more than 1 active ecommerce branch, show modal automatically.
        if (EcommerceSetting::isUnifiedMode() && !session('branch_user_selected')) {
            $branchesCount = Branch::where('is_active', true)
                ->where('ecommerce_enabled', true)
                ->count();

            if ($branchesCount > 1) {
                $this->isOpen = true;
            } else {
                // If only 1 branch exists, auto-select it
                $onlyBranch = Branch::where('is_active', true)->where('ecommerce_enabled', true)->first();
                if ($onlyBranch) {
                    session([
                        'ecommerce_branch_id' => $onlyBranch->id,
                        'ecommerce_branch_slug' => $onlyBranch->slug,
                        'branch_user_selected' => true,
                    ]);
                    $this->selectedBranchId = $onlyBranch->id;
                }
            }
        }
    }

    #[On('open-branch-selector')]
    public function openModal(): void
    {
        $this->selectedBranchId = Branch::getEcommerceBranchId();
        $this->showCartWarning = false;
        $this->pendingBranchId = null;
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        // Only allow closing if a branch is already active
        if ($this->selectedBranchId) {
            $this->isOpen = false;
            $this->showCartWarning = false;
            $this->pendingBranchId = null;
        }
    }

    public function selectBranch(int $branchId): void
    {
        // If clicking the currently selected branch, just close
        if ($this->selectedBranchId === $branchId) {
            $this->closeModal();
            return;
        }

        $cart = session('ecommerce_cart', ['items' => []]);
        $hasItems = !empty($cart['items'] ?? []);

        // If there are items in the cart, warn before switching
        if ($hasItems && $this->selectedBranchId) {
            $targetBranch = Branch::find($branchId);
            $this->pendingBranchId = $branchId;
            $this->pendingBranchName = $targetBranch?->name ?? 'otra sucursal';
            $this->showCartWarning = true;
            return;
        }

        $this->applyBranchSelection($branchId);
    }

    public function confirmBranchSwitch(): void
    {
        if ($this->pendingBranchId) {
            $this->applyBranchSelection($this->pendingBranchId);
        }
    }

    public function cancelSwitch(): void
    {
        $this->showCartWarning = false;
        $this->pendingBranchId = null;
        $this->pendingBranchName = '';
    }

    protected function applyBranchSelection(int $branchId): void
    {
        $branch = Branch::where('id', $branchId)
            ->where('is_active', true)
            ->where('ecommerce_enabled', true)
            ->first();

        if (!$branch) {
            return;
        }

        // Clear cart to avoid cross-branch stock/price contamination
        session()->forget('ecommerce_cart');

        // Set active branch in session
        session([
            'ecommerce_branch_id' => $branch->id,
            'ecommerce_branch_slug' => $branch->slug,
            'branch_user_selected' => true,
        ]);

        $this->selectedBranchId = $branch->id;
        $this->isOpen = false;
        $this->showCartWarning = false;
        $this->pendingBranchId = null;

        // If customer is logged in, auto-ensure existence in this branch
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            if ($customer->branch_id !== $branch->id) {
                $syncService = app(CustomerSyncService::class);
                $syncedCustomer = $syncService->ensureCustomerExistsInBranch($customer, $branch->id);
                Auth::guard('customer')->login($syncedCustomer);
            }
        }

        // Redirect to catalog to refresh products with new branch
        $this->redirect(route('shop.catalog'), navigate: true);
    }

    public function render()
    {
        $branches = Branch::where('is_active', true)
            ->where('ecommerce_enabled', true)
            ->orderBy('name')
            ->get();

        return view('livewire.shop.branch-selector-modal', [
            'branches' => $branches,
        ]);
    }
}
