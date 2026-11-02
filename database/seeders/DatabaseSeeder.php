<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default seeder.
 *
 * Seeds only production-safe reference data (examinations, help articles).
 * Real centre and listing data is imported by an administrator through the
 * verified CSV import. The first admin account is created with
 * `php artisan app:create-admin`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
        ]);
    }
}
