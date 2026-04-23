<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core Data
            CategorySeeder::class,

            // Localization
            TranslationSeeder::class, // Registered here

            // Content & Users
            UserSeeder::class,
            AdSeeder::class,
        ]);
    }
}
