<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'cars_category_id_fk_support_index';

    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->index('category_id', self::INDEX);
        });
    }

    public function down(): void
    {
        // Keep this supporting index until the baseline cars table is dropped.
        // MySQL may otherwise bind the category foreign key to the older
        // (category_id, is_visible) index and reject that migration's rollback.
    }
};
