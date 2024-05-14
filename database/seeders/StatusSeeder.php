<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'À faire', 'color' => '#808080'],
            ['name' => 'En cours', 'color' => '#FFA500'],
            ['name' => 'Terminé', 'color' => '#008000']
        ];

        foreach ($statuses as $status) {
            \App\Models\Status::updateOrCreate([
                'name' => $status['name']
            ], [
                'name' => $status['name'],
                'color' => $status['color']
            ]);
        }
    }
}
