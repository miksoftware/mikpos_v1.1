<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('completed', 'cancelled', 'refunded', 'pending_approval', 'rejected') DEFAULT 'completed'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('completed', 'cancelled', 'refunded') DEFAULT 'completed'");
    }
};
