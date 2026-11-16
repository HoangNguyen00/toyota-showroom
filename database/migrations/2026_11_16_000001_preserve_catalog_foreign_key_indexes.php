<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureForeignKeyIndexes();
    }

    public function down(): void
    {
        // These support baseline foreign keys, not the catalog feature. Keep them
        // so the earlier catalog migration can safely drop its composite indexes.
        $this->ensureForeignKeyIndexes();
    }

    private function ensureForeignKeyIndexes(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['cars' => 'category_id', 'car_images' => 'car_id'] as $table => $column) {
            $index = $table.'_'.$column.'_foreign';
            if (! Schema::hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($column, $index));
            }
        }
    }
};
