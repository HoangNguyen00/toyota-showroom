<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TV3 catalog: indexes for admin search/filter and public listing.
 * New migration only — the baseline cars/car_images migrations stay untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->index(['category_id', 'is_visible']);
            $table->index(['name']);
        });

        Schema::table('car_images', function (Blueprint $table) {
            $table->index(['car_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        // MySQL may replace an implicit FK index with the new composite index.
        // Restore the single-column index before dropping the composite on rollback.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            if (! Schema::hasIndex('cars', 'cars_category_id_foreign')) {
                Schema::table('cars', fn (Blueprint $table) => $table->index('category_id', 'cars_category_id_foreign'));
            }
            if (! Schema::hasIndex('car_images', 'car_images_car_id_foreign')) {
                Schema::table('car_images', fn (Blueprint $table) => $table->index('car_id', 'car_images_car_id_foreign'));
            }
        }
        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'is_visible']);
            $table->dropIndex(['name']);
        });

        Schema::table('car_images', function (Blueprint $table) {
            $table->dropIndex(['car_id', 'is_primary']);
        });
    }
};
