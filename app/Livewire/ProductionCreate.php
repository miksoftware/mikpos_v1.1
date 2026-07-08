<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductionCreate extends Component
{
    public ?int $selectedRecipeId = null;
    public $quantity_to_produce = 1;
    public ?string $notes = null;

    public array $requiredIngredients = [];
    public bool $canProduce = false;
    public float $estimatedCost = 0;

    public function updatedSelectedRecipeId()
    {
        $this->quantity_to_produce = 1;
        $this->calculateRequirements();
    }

    public function updatedQuantityToProduce()
    {
        $this->quantity_to_produce = (float) $this->quantity_to_produce;
        if ($this->quantity_to_produce < 0.01) {
            $this->quantity_to_produce = 1;
        }
        $this->calculateRequirements();
    }

    public function calculateRequirements()
    {
        $this->requiredIngredients = [];
        $this->canProduce = false;
        $this->estimatedCost = 0;

        if (!$this->selectedRecipeId || $this->quantity_to_produce <= 0) {
            return;
        }

        $recipe = Recipe::with(['ingredients.product', 'product'])->find($this->selectedRecipeId);
        if (!$recipe) {
            return;
        }

        // The recipe defines how much yield_quantity is produced by the exact quantities of ingredients
        // Multiplier = quantity_to_produce / recipe->yield_quantity
        $multiplier = $this->quantity_to_produce / $recipe->yield_quantity;

        $canProduceAll = true;
        $totalCost = 0;

        foreach ($recipe->ingredients as $ingredient) {
            $product = $ingredient->product;
            $requiredQuantity = $ingredient->quantity * $multiplier;
            $availableStock = $product->current_stock ?? 0;
            $isEnough = $availableStock >= $requiredQuantity;

            if (!$isEnough) {
                $canProduceAll = false;
            }

            $cost = ($product->average_cost > 0 ? $product->average_cost : $product->purchase_price) * $requiredQuantity;
            $totalCost += $cost;

            $this->requiredIngredients[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'required_quantity' => $requiredQuantity,
                'available_stock' => $availableStock,
                'is_enough' => $isEnough,
                'unit_cost' => $product->average_cost > 0 ? $product->average_cost : $product->purchase_price,
            ];
        }

        $this->canProduce = $canProduceAll && count($this->requiredIngredients) > 0;
        $this->estimatedCost = $totalCost;
    }

    public function produce()
    {
        if (!auth()->user()->hasPermission('production.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->calculateRequirements();

        if (!$this->canProduce) {
            $this->dispatch('notify', message: 'No hay suficiente inventario para producir esta cantidad', type: 'error');
            return;
        }

        $recipe = Recipe::find($this->selectedRecipeId);
        if (!$recipe) return;

        DB::beginTransaction();
        try {
            $branchId = auth()->user()->branch_id ?? $recipe->product->branch_id;
            
            if (!$branchId) {
                $branchId = \App\Models\Branch::first()->id;
            }

            // Create Production Order
            $order = ProductionOrder::create([
                'product_id' => $recipe->product_id,
                'recipe_id' => $recipe->id,
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'quantity_to_produce' => $this->quantity_to_produce,
                'total_cost' => $this->estimatedCost,
                'status' => 'completed', // Production is immediate
                'notes' => $this->notes,
            ]);

            // Deduct ingredients
            foreach ($this->requiredIngredients as $req) {
                $product = Product::find($req['product_id']);
                
                // Create Order Detail
                ProductionOrderDetail::create([
                    'production_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity_consumed' => $req['required_quantity'],
                    'unit_cost_at_time' => $req['unit_cost'],
                ]);

                // Update Stock
                $product->decrement('current_stock', $req['required_quantity']);

                // Inventory Movement Out
                InventoryMovement::createMovement(
                    'PROD', // Need to make sure this system document exists, it was created in migration
                    $product,
                    'out',
                    $req['required_quantity'],
                    $req['unit_cost'],
                    "Consumo para Orden de Producción #{$order->id}",
                    $order,
                    $branchId
                );
            }

            // Add Finished Product Stock
            $finishedProduct = Product::find($recipe->product_id);
            $finishedProduct->increment('current_stock', $this->quantity_to_produce);
            
            $unitCost = $this->estimatedCost / $this->quantity_to_produce;
            
            // Only update cost if the estimated cost is greater than 0
            // This prevents zeroing out the cost if raw materials have no cost
            if ($unitCost > 0) {
                $finishedProduct->updateAverageCost((float) $this->quantity_to_produce, (float) $unitCost);
            }

            InventoryMovement::createMovement(
                'PROD',
                $finishedProduct,
                'in',
                $this->quantity_to_produce,
                $this->estimatedCost / $this->quantity_to_produce,
                "Ingreso por Orden de Producción #{$order->id}",
                $order,
                $branchId
            );

            DB::commit();

            session()->flash('success', 'Orden de producción creada y stock actualizado correctamente.');
            return redirect()->route('production.index');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $recipes = Recipe::with('product')
            ->where('is_active', true)
            ->get();

        return view('livewire.production-create', [
            'recipes' => $recipes
        ]);
    }
}
