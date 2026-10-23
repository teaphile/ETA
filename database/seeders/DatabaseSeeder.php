<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default seeder.
 *
 * Reference data (examinations, help articles) is always safe. Demonstration centres
 * and listings are clearly flagged and refuse to load in production unless
 * ALLOW_DEMO_SEED=true is explicitly set.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
