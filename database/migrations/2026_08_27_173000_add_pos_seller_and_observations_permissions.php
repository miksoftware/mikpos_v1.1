<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get or create POS module
        $posModule = Module::firstOrCreate(
            ['name' => 'pos'],
            [
                'display_name' => 'Punto de Venta',
                'icon' => 'calculator',
                'order' => 7,
                'is_active' => true,
            ]
        );

        // 2. Create pos.change_seller permission
        $changeSellerPerm = Permission::firstOrCreate(
            ['name' => 'pos.change_seller'],
            [
                'module_id' => $posModule->id,
                'display_name' => 'Cambiar Vendedor en POS',
                'description' => 'Permite seleccionar o cambiar manualmente el vendedor al realizar una venta en el POS',
            ]
        );

        // 3. Create pos.observations permission
        $observationsPerm = Permission::firstOrCreate(
            ['name' => 'pos.observations'],
            [
                'module_id' => $posModule->id,
                'display_name' => 'Observaciones en Venta POS',
                'description' => 'Permite ingresar observaciones o notas opcionales al procesar una venta en el POS',
            ]
        );

        // 4. Assign permissions to super_admin, admin, branch_admin roles
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin', 'admin'])->get();

        foreach ($roles as $role) {
            if ($changeSellerPerm && !$role->permissions()->where('permissions.id', $changeSellerPerm->id)->exists()) {
                $role->permissions()->attach($changeSellerPerm->id);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['pos.change_seller', 'pos.observations'] as $permName) {
            $permission = Permission::where('name', $permName)->first();
            if ($permission) {
                $permission->roles()->detach();
                $permission->delete();
            }
        }
    }
};
