<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CatalogSeeder::class);
        // TV5 adds the service seeder with explicit demo data provenance.
    }
}
