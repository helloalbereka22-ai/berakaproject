<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AfanOromoLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if Afan Oromo already exists
        $exists = DB::table('languages')->where('code', 'om')->exists();
        
        if (!$exists) {
            DB::table('languages')->insert([
                'code' => 'om',
                'name' => 'Afan Oromo',
                'native_name' => 'Afaan Oromoo',
                'is_rtl' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info('✓ Afan Oromo language added successfully!');
        } else {
            $this->command->info('✓ Afan Oromo language already exists.');
        }
    }
}
