<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        // Sample products only outside production (test domain, local, CI).
        if (! app()->isProduction() || config('fritz.demo_data')) {
            $this->call(DemoProductSeeder::class);
        }
    }
}
