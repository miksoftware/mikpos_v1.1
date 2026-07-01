<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Recipes extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public bool $isModalOpen = false;
    public bool $isDeleteModalOpen = false;
    public ?int $itemIdToDelete = null;

    // Form fields
    public ?int $recipeId = null;
    public ?int $product_id = null;
    public float $yield_quantity = 1;
    public ?string $instructions = null;
    public bool $is_active = true;
    
    // List of ingredients: array of ['product_id' => ..., 'quantity' => ...]
    public array $ingredients = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $recipes = Recipe::with('product')
            ->when($this->search, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);

        // Fetch products for dropdowns
        $finishedProducts = Product::where('is_active', true)
            ->where('type', 'finished_product')
            ->orderBy('name')
            ->get();

        $ingredientProducts = Product::with('unit')->where('is_active', true)
            ->where('type', 'raw_material')
            ->orderBy('name')
            ->get();

        return view('livewire.recipes', [
            'recipes' => $recipes,
            'finishedProducts' => $finishedProducts,
            'ingredientProducts' => $ingredientProducts,
        ]);
    }

    public function sortByColumn(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function addIngredient()
    {
        $this->ingredients[] = ['product_id' => '', 'quantity' => 1];
    }

    public function removeIngredient($index)
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients); // Re-index
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('recipes.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->resetForm();
        $this->addIngredient(); // Add one empty ingredient by default
        $this->isModalOpen = true;
    }

    public function edit(int $id)
    {
        if (!auth()->user()->hasPermission('recipes.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->resetForm();
        $recipe = Recipe::with('ingredients')->findOrFail($id);

        $this->recipeId = $recipe->id;
        $this->product_id = $recipe->product_id;
        $this->yield_quantity = (float) $recipe->yield_quantity;
        $this->instructions = $recipe->instructions;
        $this->is_active = $recipe->is_active;

        foreach ($recipe->ingredients as $ingredient) {
            $this->ingredients[] = [
                'id' => $ingredient->id,
                'product_id' => $ingredient->product_id,
                'quantity' => (float) $ingredient->quantity,
            ];
        }

        $this->isModalOpen = true;
    }

    public function store()
    {
        if (!auth()->user()->hasPermission($this->recipeId ? 'recipes.edit' : 'recipes.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'product_id' => 'required|exists:products,id|unique:recipes,product_id,' . $this->recipeId,
            'yield_quantity' => 'required|numeric|min:0.001',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.product_id' => 'required|exists:products,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
        ], [
            'product_id.required' => 'El producto a fabricar es obligatorio',
            'product_id.unique' => 'Este producto ya tiene una receta asociada',
            'yield_quantity.required' => 'El rendimiento es obligatorio',
            'ingredients.required' => 'Debe agregar al menos un insumo',
            'ingredients.*.product_id.required' => 'Seleccione un insumo válido',
            'ingredients.*.quantity.required' => 'La cantidad debe ser mayor a 0',
        ]);

        DB::beginTransaction();
        try {
            $isNew = !$this->recipeId;
            $oldValues = $isNew ? null : Recipe::with('ingredients')->find($this->recipeId)->toArray();

            $recipe = Recipe::updateOrCreate(
                ['id' => $this->recipeId],
                [
                    'product_id' => $this->product_id,
                    'yield_quantity' => $this->yield_quantity,
                    'instructions' => $this->instructions,
                    'is_active' => $this->is_active,
                ]
            );

            // Sync ingredients
            $existingIngredientIds = collect($this->ingredients)->filter(fn($i) => isset($i['id']))->pluck('id')->toArray();
            
            // Delete removed ingredients
            RecipeIngredient::where('recipe_id', $recipe->id)
                ->whereNotIn('id', $existingIngredientIds)
                ->delete();

            // Update or create ingredients
            foreach ($this->ingredients as $ing) {
                if (isset($ing['id'])) {
                    RecipeIngredient::where('id', $ing['id'])->update([
                        'product_id' => $ing['product_id'],
                        'quantity' => $ing['quantity'],
                    ]);
                } else {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'product_id' => $ing['product_id'],
                        'quantity' => $ing['quantity'],
                    ]);
                }
            }

            DB::commit();

            $isNew
                ? ActivityLogService::logCreate('recipes', $recipe, "Receta creada para el producto ID {$this->product_id}")
                : ActivityLogService::logUpdate('recipes', $recipe, $oldValues, "Receta actualizada para el producto ID {$this->product_id}");

            $this->isModalOpen = false;
            $this->dispatch('notify', message: $isNew ? 'Receta creada' : 'Receta actualizada');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al guardar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function confirmDelete(int $id)
    {
        if (!auth()->user()->hasPermission('recipes.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('recipes.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $recipe = Recipe::find($this->itemIdToDelete);
        if ($recipe) {
            ActivityLogService::logDelete('recipes', $recipe, "Receta eliminada (Producto ID: {$recipe->product_id})");
            $recipe->ingredients()->delete();
            $recipe->delete();
            
            $this->isDeleteModalOpen = false;
            $this->dispatch('notify', message: 'Receta eliminada');
        }
    }

    public function toggleStatus(int $id)
    {
        if (!auth()->user()->hasPermission('recipes.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $recipe = Recipe::find($id);
        if ($recipe) {
            $oldValues = $recipe->toArray();
            $recipe->is_active = !$recipe->is_active;
            $recipe->save();

            ActivityLogService::logUpdate(
                'recipes',
                $recipe,
                $oldValues,
                "Receta " . ($recipe->is_active ? 'activada' : 'desactivada')
            );
            $this->dispatch('notify', message: $recipe->is_active ? 'Activada' : 'Desactivada');
        }
    }

    private function resetForm()
    {
        $this->recipeId = null;
        $this->product_id = null;
        $this->yield_quantity = 1;
        $this->instructions = null;
        $this->is_active = true;
        $this->ingredients = [];
        $this->resetValidation();
    }
}
