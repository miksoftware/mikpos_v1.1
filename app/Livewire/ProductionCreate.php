<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Location;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
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
    public ?int $selectedLocationId = null;
    public ?string $notes = null;

    public array $cart = [];
    public array $globalRequiredIngredients = [];
    public bool $canProduceAll = false;
    public float $globalEstimatedCost = 0;

    public bool $recipeRequiresLocation = false;

    public function updatedSelectedRecipeId()
    {
        $this->quantity_to_produce = 1;
        $this->selectedLocationId = null;
        $this->recipeRequiresLocation = false;

        if ($this->selectedRecipeId) {
            $recipe = Recipe::with('product')->find($this->selectedRecipeId);
            if ($recipe && $recipe->product) {
                // If it's a finished product that normally has locations (or depends on branch locations)
                // Let's check if the location module is active or if we just want to show it.
                // Assuming standard mikpos behavior: products have locations.
                $this->recipeRequiresLocation = true; // We will show the dropdown
            }
        }
    }

    public function updatedQuantityToProduce()
    {
        $this->quantity_to_produce = (float) $this->quantity_to_produce;
        if ($this->quantity_to_produce < 0.01) {
            $this->quantity_to_produce = 1;
        }
    }

    public function addToCart()
    {
        if (!$this->selectedRecipeId || $this->quantity_to_produce <= 0) {
            return;
        }

        $recipe = Recipe::with(['ingredients.product', 'product'])->find($this->selectedRecipeId);
        if (!$recipe) return;

        if ($this->recipeRequiresLocation && !$this->selectedLocationId) {
            $this->dispatch('notify', message: 'Debe seleccionar una ubicación de destino', type: 'error');
            return;
        }

        $multiplier = $this->quantity_to_produce / $recipe->yield_quantity;
        $itemCost = 0;
        
        foreach ($recipe->ingredients as $ingredient) {
            $product = $ingredient->product;
            $requiredQuantity = $ingredient->quantity * $multiplier;
            $unitCost = $product->average_cost > 0 ? $product->average_cost : $product->purchase_price;
            $itemCost += $unitCost * $requiredQuantity;
        }

        $locationName = null;
        if ($this->selectedLocationId) {
            $loc = Location::find($this->selectedLocationId);
            $locationName = $loc ? $loc->name : null;
        }

        $this->cart[] = [
            'id' => uniqid(),
            'recipe_id' => $recipe->id,
            'recipe_name' => $recipe->name,
            'product_id' => $recipe->product_id,
            'product_name' => $recipe->product->name,
            'quantity' => $this->quantity_to_produce,
            'location_id' => $this->selectedLocationId,
            'location_name' => $locationName,
            'estimated_cost' => $itemCost,
            'multiplier' => $multiplier,
        ];

        $this->selectedRecipeId = null;
        $this->quantity_to_produce = 1;
        $this->selectedLocationId = null;
        $this->recipeRequiresLocation = false;

        $this->calculateRequirements();
    }

    public function removeFromCart($id)
    {
        $this->cart = array_filter($this->cart, fn($item) => $item['id'] !== $id);
        $this->calculateRequirements();
    }

    public function calculateRequirements()
    {
        $this->globalRequiredIngredients = [];
        $this->canProduceAll = false;
        $this->globalEstimatedCost = 0;

        if (empty($this->cart)) return;

        $ingredientsMap = [];
        $totalCost = 0;

        foreach ($this->cart as $cartItem) {
            $recipe = Recipe::with('ingredients.product')->find($cartItem['recipe_id']);
            if (!$recipe) continue;

            $totalCost += $cartItem['estimated_cost'];

            foreach ($recipe->ingredients as $ingredient) {
                $product = $ingredient->product;
                $reqQty = $ingredient->quantity * $cartItem['multiplier'];

                if (!isset($ingredientsMap[$product->id])) {
                    $ingredientsMap[$product->id] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'required_quantity' => 0,
                        'available_stock' => $product->current_stock ?? 0,
                        'unit_cost' => $product->average_cost > 0 ? $product->average_cost : $product->purchase_price,
                    ];
                }
                $ingredientsMap[$product->id]['required_quantity'] += $reqQty;
            }
        }

        $canProduce = true;
        foreach ($ingredientsMap as &$ing) {
            $ing['is_enough'] = $ing['available_stock'] >= $ing['required_quantity'];
            if (!$ing['is_enough']) {
                $canProduce = false;
            }
        }

        $this->globalRequiredIngredients = array_values($ingredientsMap);
        $this->canProduceAll = $canProduce && count($this->globalRequiredIngredients) > 0;
        $this->globalEstimatedCost = $totalCost;
    }

    public function produce()
    {
        if (!auth()->user()->hasPermission('production.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->calculateRequirements();

        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Agrega al menos una receta para producir', type: 'error');
            return;
        }

        if (!$this->canProduceAll) {
            $this->dispatch('notify', message: 'No hay suficiente inventario para producir esta orden', type: 'error');
            return;
        }

        DB::beginTransaction();
        try {
            $branchId = auth()->user()->branch_id ?? \App\Models\Branch::first()->id;

            // Create Order
            $order = ProductionOrder::create([
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'status' => 'completed',
                'notes' => $this->notes,
            ]);

            // Deduct Ingredients exactly as required by each cart item
            // But doing it globally or per item? Per item is better to associate costs.
            foreach ($this->cart as $cartItem) {
                $recipe = Recipe::with('ingredients.product')->find($cartItem['recipe_id']);
                
                $orderItem = ProductionOrderItem::create([
                    'production_order_id' => $order->id,
                    'recipe_id' => $recipe->id,
                    'product_id' => $cartItem['product_id'],
                    'location_id' => $cartItem['location_id'],
                    'quantity_to_produce' => $cartItem['quantity'],
                    'total_cost' => $cartItem['estimated_cost'],
                ]);

                // Consume ingredients for this specific item
                foreach ($recipe->ingredients as $ingredient) {
                    $product = $ingredient->product;
                    $reqQty = $ingredient->quantity * $cartItem['multiplier'];
                    $unitCost = $product->average_cost > 0 ? $product->average_cost : $product->purchase_price;

                    ProductionOrderDetail::create([
                        'production_order_id' => $order->id,
                        'production_order_item_id' => $orderItem->id,
                        'product_id' => $product->id,
                        'quantity_consumed' => $reqQty,
                        'unit_cost_at_time' => $unitCost,
                    ]);

                    $product->decrement('current_stock', $reqQty);

                    InventoryMovement::createMovement(
                        'PROD',
                        $product,
                        'out',
                        $reqQty,
                        $unitCost,
                        "Consumo para Producción de {$cartItem['product_name']} (Orden #{$order->id})",
                        $order,
                        $branchId
                    );
                }

                // Add Finished Product Stock
                $finishedProduct = Product::find($cartItem['product_id']);
                $finishedProduct->increment('current_stock', $cartItem['quantity']);

                $unitCostProd = $cartItem['estimated_cost'] / $cartItem['quantity'];
                if ($unitCostProd > 0) {
                    $finishedProduct->updateAverageCost((float) $cartItem['quantity'], (float) $unitCostProd);
                }

                if ($cartItem['location_id']) {
                    $locProduct = DB::table('location_products')
                        ->where('location_id', $cartItem['location_id'])
                        ->where('product_id', $cartItem['product_id'])
                        ->first();
                    
                    if ($locProduct) {
                        DB::table('location_products')
                            ->where('location_id', $cartItem['location_id'])
                            ->where('product_id', $cartItem['product_id'])
                            ->update(['quantity' => $locProduct->quantity + $cartItem['quantity']]);
                    } else {
                        DB::table('location_products')->insert([
                            'location_id' => $cartItem['location_id'],
                            'product_id' => $cartItem['product_id'],
                            'quantity' => $cartItem['quantity'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                InventoryMovement::createMovement(
                    'PROD',
                    $finishedProduct,
                    'in',
                    $cartItem['quantity'],
                    $unitCostProd,
                    "Ingreso por Orden de Producción #{$order->id}",
                    $order,
                    $branchId,
                    $cartItem['location_id']
                );
            }

            DB::commit();

            session()->flash('success', 'Orden de producción creada exitosamente.');
            return redirect()->route('production.index');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $recipes = Recipe::with('product')->where('is_active', true)->get();
        $locations = Location::where('branch_id', auth()->user()->branch_id ?? \App\Models\Branch::first()->id)
            ->where('is_active', true)
            ->get();

        return view('livewire.production-create', [
            'recipes' => $recipes,
            'locations' => $locations,
        ]);
    }
}
