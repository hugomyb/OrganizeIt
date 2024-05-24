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
            [
                'name' => 'Aucune',
                'en_name' => 'None',
                'color' => '#aebacc'],
            [
                'name' => 'Basse',
                'en_name' => 'Low',
                'color' => '#008000'
            ],
            [
                'name' => 'Moyenne',
                'en_name' => 'Medium',
                'color' => '#FFA500'
            ],
            [
                'name' => 'Haute',
                'en_name' => 'High',
                'color' => '#FF0000'
            ],
        ];

        foreach ($priorities as $priority) {
            \App\Models\Priority::updateOrCreate([
                'name' => $priority['name'],
            ], $priority);
        }
    }
}
