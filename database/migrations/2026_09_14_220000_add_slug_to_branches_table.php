<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'slug')) {
                $table->string('slug', 100)->nullable()->unique()->after('name');
            }
        });

        // Populate slug for existing branches
        $branches = DB::table('branches')->get();
        foreach ($branches as $branch) {
            if (empty($branch->slug)) {
                $base = Str::slug($branch->name ?: ('sucursal-' . $branch->id));
                if (empty($base)) {
                    $base = 'sucursal-' . $branch->id;
                }
                $slug = $base;
                $count = 1;
                while (DB::table('branches')->where('slug', $slug)->where('id', '!=', $branch->id)->exists()) {
                    $slug = "{$base}-{$count}";
                    $count++;
                }
                DB::table('branches')->where('id', $branch->id)->update(['slug' => $slug]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
