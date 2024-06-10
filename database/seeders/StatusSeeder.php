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
            [
                'name' => 'À faire',
                'en_name' => 'To do',
                'color' => '#aebacc'
            ],
            [
                'name' => 'En cours',
                'en_name' => 'In progress',
                'color' => '#FFA500'
            ],
            [
                'name' => 'Terminé',
                'en_name' => 'Done',
                'color' => '#008000'
            ]
        ];

        foreach ($statuses as $status) {
            \App\Models\Status::updateOrCreate([
                'name' => $status['name']
            ], [
                'name' => $status['name'],
                'en_name' => $status['en_name'],
                'color' => $status['color']
            ]);
        }
    }
}
