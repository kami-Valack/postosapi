<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (config('roles.definitions', []) as $id => $definition) {
            $rows[] = [
                'id' => (int) $id,
                'name' => $definition['name'],
                'permissions' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('roles')->upsert($rows, ['id'], ['name', 'permissions', 'updated_at']);
    }
}
