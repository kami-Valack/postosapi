<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuelTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (config('fuel_types.definitions', []) as $id => $definition) {
            $rows[] = [
                'id' => (int) $id,
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'sort_order' => $definition['sort_order'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('fuel_types')->upsert($rows, ['id'], ['slug', 'name', 'sort_order', 'updated_at']);
    }
}
