<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * IDs are kept in sync with Flutter's _categoryIdMap so the app works
     * out of the box without any config changes.
     *
     * Products:  1–8
     * Services:  9–12
     * Properties: 13–16
     */
    public function run(): void
    {
        $categories = [
            // ── Products ──────────────────────────────────────────────────
            ['id' =>  1, 'type' => 'product',  'name' => 'Sharp Sand'],
            ['id' =>  2, 'type' => 'product',  'name' => 'Granite'],
            ['id' =>  3, 'type' => 'product',  'name' => 'Blocks'],
            ['id' =>  4, 'type' => 'product',  'name' => 'Cement'],
            ['id' =>  5, 'type' => 'product',  'name' => 'Iron Rods'],
            ['id' =>  6, 'type' => 'product',  'name' => 'Paints'],
            ['id' =>  7, 'type' => 'product',  'name' => 'Furniture'],
            ['id' =>  8, 'type' => 'product',  'name' => 'Scaffolding'],

            // ── Services ──────────────────────────────────────────────────
            ['id' =>  9, 'type' => 'service',  'name' => 'Logistics'],
            ['id' => 10, 'type' => 'service',  'name' => 'Borehole'],
            ['id' => 11, 'type' => 'service',  'name' => 'Cleaning'],
            ['id' => 12, 'type' => 'service',  'name' => 'Fumigation'],

            // ── Properties ────────────────────────────────────────────────
            ['id' => 13, 'type' => 'property', 'name' => 'Apartment'],
            ['id' => 14, 'type' => 'property', 'name' => 'House'],
            ['id' => 15, 'type' => 'property', 'name' => 'Commercial'],
            ['id' => 16, 'type' => 'property', 'name' => 'Land'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['id' => $cat['id']],
                [
                    'name' => $cat['name'],
                    'type' => $cat['type'],
                    'slug' => Str::slug($cat['name']),
                ]
            );
        }
    }
}
