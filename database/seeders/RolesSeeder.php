<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Utilisateur'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::updateOrCreate([
                'name' => $role['name'],
            ], [
                'id' => $role['id'],
                'name' => $role['name'],
            ]);
        }
    }
}
