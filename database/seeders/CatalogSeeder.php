<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $factory  = Business::where('code', 'OIL-FACTORY')->first();
        $bookstore = Business::where('code', 'BOOKSTORE')->first();

        if (!$factory) {
            $this->command->warn('FoundationSeeder must run first (OIL-FACTORY business not found).');
            return;
        }

        // ── Oil Factory Categories ────────────────────────────────────────

        // Raw material categories (configurable — no code change required to add new ones)
        $rawMaterialParent = Category::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $factory->id, 'slug' => 'raw-materials'],
            [
                'name'      => 'Raw Materials',
                'type'      => Category::TYPE_RAW_MATERIAL,
                'is_active' => true,
                'icon'      => '🌾',
                'sort_order'=> 1,
            ]
        );

        $rawCategories = [
            ['name' => 'Sesame (Selit)',          'slug' => 'sesame-selit',       'icon' => '🫙', 'sort_order' => 1],
            ['name' => 'Black Cumin (Tukur Azmud)','slug' => 'black-cumin-tukur', 'icon' => '⚫', 'sort_order' => 2],
            ['name' => 'Flaxseed (Telba)',         'slug' => 'flaxseed-telba',    'icon' => '🌱', 'sort_order' => 3],
        ];

        foreach ($rawCategories as $cat) {
            Category::withoutGlobalScopes()->firstOrCreate(
                ['business_id' => $factory->id, 'slug' => $cat['slug']],
                array_merge($cat, [
                    'business_id' => $factory->id,
                    'parent_id'   => $rawMaterialParent->id,
                    'type'        => Category::TYPE_RAW_MATERIAL,
                    'is_active'   => true,
                ])
            );
        }

        // Packing material categories
        $packingParent = Category::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $factory->id, 'slug' => 'packing-materials'],
            [
                'name'      => 'Packing Materials',
                'type'      => Category::TYPE_PACKING_MATERIAL,
                'is_active' => true,
                'icon'      => '📦',
                'sort_order'=> 2,
            ]
        );

        $packingCategories = [
            ['name' => 'Bottles', 'slug' => 'packing-bottles', 'icon' => '🍶'],
            ['name' => 'Caps',    'slug' => 'packing-caps',    'icon' => '🔵'],
            ['name' => 'Labels',  'slug' => 'packing-labels',  'icon' => '🏷️'],
            ['name' => 'Cartons', 'slug' => 'packing-cartons', 'icon' => '📫'],
        ];

        foreach ($packingCategories as $idx => $cat) {
            Category::withoutGlobalScopes()->firstOrCreate(
                ['business_id' => $factory->id, 'slug' => $cat['slug']],
                array_merge($cat, [
                    'business_id' => $factory->id,
                    'parent_id'   => $packingParent->id,
                    'type'        => Category::TYPE_PACKING_MATERIAL,
                    'is_active'   => true,
                    'sort_order'  => $idx + 1,
                ])
            );
        }

        // Finished oil products category
        $oilCategory = Category::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $factory->id, 'slug' => 'finished-oil-products'],
            [
                'name'      => 'Finished Oil Products',
                'type'      => Category::TYPE_OIL,
                'is_active' => true,
                'icon'      => '🫗',
                'sort_order'=> 3,
            ]
        );

        // ── Bookstore Categories ──────────────────────────────────────────
        if ($bookstore) {
            $bookCategories = [
                ['name' => 'General Books',    'slug' => 'books-general',    'icon' => '📚'],
                ['name' => 'Religious Books',  'slug' => 'books-religious',  'icon' => '📖'],
                ['name' => 'Educational',      'slug' => 'books-educational','icon' => '🎓'],
                ['name' => 'Children Books',   'slug' => 'books-children',   'icon' => '🧒'],
                ['name' => 'Stationery',       'slug' => 'stationery',       'icon' => '✏️'],
            ];

            foreach ($bookCategories as $idx => $cat) {
                Category::withoutGlobalScopes()->firstOrCreate(
                    ['business_id' => $bookstore->id, 'slug' => $cat['slug']],
                    array_merge($cat, [
                        'business_id' => $bookstore->id,
                        'type'        => Category::TYPE_BOOK,
                        'is_active'   => true,
                        'sort_order'  => $idx + 1,
                    ])
                );
            }
        }

        $this->command->info('Catalog seeded: raw material categories (Selit, Tukur Azmud, Telba), packing materials, oil products, and bookstore categories.');
    }
}
