<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\EcommerceSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerSyncService
{
    /**
     * Create customer record in all active ecommerce branches.
     * Used during registration on the unified store.
     */
    public function syncToAllEcommerceBranches(array $data, ?int $primaryBranchId = null): Customer
    {
        $branches = Branch::where('is_active', true)
            ->where('ecommerce_enabled', true)
            ->get();

        if ($branches->isEmpty()) {
            $branches = Branch::where('is_active', true)->get();
        }

        $createdPrimary = null;

        DB::transaction(function () use ($branches, $data, $primaryBranchId, &$createdPrimary) {
            foreach ($branches as $branch) {
                // Check if customer already exists for this branch
                $existing = Customer::where('branch_id', $branch->id)
                    ->where(function ($q) use ($data) {
                        $q->where('email', $data['email'])
                          ->orWhere('document_number', $data['document_number']);
                    })
                    ->first();

                if ($existing) {
                    // Update profile info
                    $existing->update([
                        'first_name' => $data['first_name'] ?? $existing->first_name,
                        'last_name' => $data['last_name'] ?? $existing->last_name,
                        'business_name' => $data['business_name'] ?? $existing->business_name,
                        'phone' => $data['phone'] ?? $existing->phone,
                        'department_id' => $data['department_id'] ?? $existing->department_id,
                        'municipality_id' => $data['municipality_id'] ?? $existing->municipality_id,
                        'address' => $data['address'] ?? $existing->address,
                        'is_active' => true,
                    ]);

                    if (isset($data['password']) && !empty($data['password'])) {
                        $existing->password = $data['password'];
                        $existing->save();
                    }

                    if ($branch->id === $primaryBranchId || !$createdPrimary) {
                        $createdPrimary = $existing;
                    }
                } else {
                    $branchData = array_merge($data, ['branch_id' => $branch->id, 'is_active' => true]);
                    $customer = Customer::create($branchData);

                    if ($branch->id === $primaryBranchId || !$createdPrimary) {
                        $createdPrimary = $customer;
                    }
                }
            }
        });

        return $createdPrimary ?: Customer::where('email', $data['email'])->first();
    }

    /**
     * Ensure a customer record exists in the target branch.
     * If the customer exists only in another branch, clone their profile to the target branch.
     */
    public function ensureCustomerExistsInBranch(Customer $sourceCustomer, int $targetBranchId): Customer
    {
        if ($sourceCustomer->branch_id === $targetBranchId) {
            return $sourceCustomer;
        }

        // Check if customer already exists in the target branch
        $existing = Customer::where('branch_id', $targetBranchId)
            ->where(function ($q) use ($sourceCustomer) {
                if ($sourceCustomer->document_number) {
                    $q->where('document_number', $sourceCustomer->document_number);
                }
                if ($sourceCustomer->email) {
                    $q->orWhere('email', $sourceCustomer->email);
                }
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        // Replicate customer to the target branch
        $replicated = new Customer();
        $replicated->branch_id = $targetBranchId;
        $replicated->seller_id = $sourceCustomer->seller_id;
        $replicated->customer_type = $sourceCustomer->customer_type ?: 'natural';
        $replicated->tax_document_id = $sourceCustomer->tax_document_id;
        $replicated->document_number = $sourceCustomer->document_number;
        $replicated->dv = $sourceCustomer->dv;
        $replicated->first_name = $sourceCustomer->first_name;
        $replicated->last_name = $sourceCustomer->last_name;
        $replicated->business_name = $sourceCustomer->business_name;
        $replicated->phone = $sourceCustomer->phone;
        $replicated->email = $sourceCustomer->email;
        $replicated->department_id = $sourceCustomer->department_id;
        $replicated->municipality_id = $sourceCustomer->municipality_id;
        $replicated->address = $sourceCustomer->address;
        $replicated->has_credit = false;
        $replicated->is_default = false;
        $replicated->is_active = true;
        
        // Use the raw password hash from the source
        $replicated->password = $sourceCustomer->password;
        $replicated->save();

        Log::info("Customer #{$sourceCustomer->id} replicated to branch #{$targetBranchId} as #{$replicated->id}");

        return $replicated;
    }

    /**
     * Get all customer IDs for this customer across all branches.
     * Matches by email or document number.
     */
    public function getAllCustomerIds(Customer $customer): array
    {
        return Customer::where(function ($q) use ($customer) {
            if ($customer->email) {
                $q->where('email', $customer->email);
            }
            if ($customer->document_number) {
                $q->orWhere('document_number', $customer->document_number);
            }
        })->pluck('id')->toArray();
    }
}
