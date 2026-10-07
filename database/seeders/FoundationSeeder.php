<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        // ── Business 1: Lubricating Oil Factory ──────────────────────────
        $factory = Business::firstOrCreate(
            ['code' => 'OIL-FACTORY'],
            [
                'name'        => 'Al-Baraka Lubricating Oil Factory',
                'description' => 'Manufacturing facility for lubricating oil products.',
                'status'      => 'active',
            ]
        );

        $factoryBranch = Branch::firstOrCreate(
            ['code' => 'OIL-HQ'],
            [
                'business_id' => $factory->id,
                'name'        => 'Main Factory',
                'description' => 'Primary manufacturing and distribution hub.',
                'status'      => 'active',
            ]
        );

        Warehouse::firstOrCreate(
            ['business_id' => $factory->id, 'branch_id' => $factoryBranch->id, 'name' => 'Raw Materials Store'],
            ['code' => 'WH-OIL-RAW', 'type' => 'raw_material', 'status' => 'active']
        );

        Warehouse::firstOrCreate(
            ['business_id' => $factory->id, 'branch_id' => $factoryBranch->id, 'name' => 'Finished Goods Store'],
            ['code' => 'WH-OIL-FG', 'type' => 'finished_goods', 'status' => 'active']
        );

        Warehouse::firstOrCreate(
            ['business_id' => $factory->id, 'branch_id' => $factoryBranch->id, 'name' => 'Packing Materials Store'],
            ['code' => 'WH-OIL-PKG', 'type' => 'packing', 'status' => 'active']
        );

        // ── Business 2: Bookstore Network ────────────────────────────────
        $bookstore = Business::firstOrCreate(
            ['code' => 'BOOKSTORE'],
            [
                'name'        => 'Al-Baraka Bookstore Network',
                'description' => 'Multi-branch bookstore retail network.',
                'status'      => 'active',
            ]
        );

        $branch1 = Branch::firstOrCreate(
            ['code' => 'BOOK-BR1'],
            [
                'business_id' => $bookstore->id,
                'name'        => 'Bookstore Branch 1 — Main',
                'description' => 'Main bookstore branch.',
                'status'      => 'active',
            ]
        );

        Warehouse::firstOrCreate(
            ['business_id' => $bookstore->id, 'branch_id' => $branch1->id, 'name' => 'Branch 1 Stock'],
            ['code' => 'WH-BOOK-BR1', 'type' => 'retail', 'status' => 'active']
        );

        $branch2 = Branch::firstOrCreate(
            ['code' => 'BOOK-BR2'],
            [
                'business_id' => $bookstore->id,
                'name'        => 'Bookstore Branch 2',
                'description' => 'Second bookstore branch.',
                'status'      => 'active',
            ]
        );

        Warehouse::firstOrCreate(
            ['business_id' => $bookstore->id, 'branch_id' => $branch2->id, 'name' => 'Branch 2 Stock'],
            ['code' => 'WH-BOOK-BR2', 'type' => 'retail', 'status' => 'active']
        );

        $this->command->info('Foundation data seeded: 2 businesses, 3 branches, 5 warehouses.');
    }
}
