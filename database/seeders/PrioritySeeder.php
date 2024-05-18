<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            ['name' => 'Aucune', 'color' => '#aebacc'],
            ['name' => 'Basse', 'color' => '#008000'],
            ['name' => 'Moyenne', 'color' => '#FFA500'],
            ['name' => 'Haute', 'color' => '#FF0000'],
        ];

        foreach ($priorities as $priority) {
            \App\Models\Priority::updateOrCreate([
                'name' => $priority['name'],
            ], $priority);
        }
    }
}
