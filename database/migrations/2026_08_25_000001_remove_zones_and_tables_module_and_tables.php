<?php

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes unused "zones_tables" module, permissions, and database tables.
     */
    public function up(): void
    {
        // 1. Detach and remove permissions related to zones and tables
        $permissions = Permission::where('name', 'like', 'zones_tables.%')
            ->orWhere('name', 'like', 'zones.%')
            ->orWhere('name', 'like', 'tables.%')
            ->get();

        foreach ($permissions as $perm) {
            $perm->roles()->detach();
            $perm->delete();
        }

        // 2. Delete module 'zones_tables'
        $module = Module::where('name', 'zones_tables')->first();
        if ($module) {
            $module->permissions()->delete();
            $module->delete();
        }

        // 3. Drop unused tables if they exist
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('zones');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed as restaurant tables and zones are deprecated and not part of the system scope
    }
};
