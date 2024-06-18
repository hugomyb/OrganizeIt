<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'key' => 'manage_tasks',
                'name' => 'Gérer les tâches',
                'description' => 'Créer, modifier et supprimer des tâches (et sous-tâches)',
            ],
            [
                'key' => 'manage_status',
                'name' => 'Gérer les statuts',
                'description' => 'Créer, modifier et supprimer des statuts',
            ],
            [
                'key' => 'change_status',
                'name' => 'Changer les statuts',
                'description' => 'Changer le statut d\'une tâche',
            ],
            [
                'key' => 'change_priority',
                'name' => 'Changer la priorité',
                'description' => 'Changer la priorité d\'une tâche',
            ],
            [
                'key' => 'assign_user',
                'name' => 'Assigner des utilisateurs',
                'description' => 'Assigner des utilisateurs à une tâche',
            ],
            [
                'key' => 'edit_description',
                'name' => 'Modifier description d\'une tâche',
                'description' => 'Modifier la description d\'une tâche',
            ],
            [
                'key' => 'manage_attachments',
                'name' => 'Gérer les pièces jointes',
                'description' => 'Ajouter et supprimer des pièces jointes sur une tâche',
            ],
            [
                'key' => 'add_comment',
                'name' => 'Ajouter un commentaire',
                'description' => 'Ajouter un commentaire à une tâche',
            ],
            [
                'key' => 'delete_any_comment',
                'name' => 'Supprimer n\'importe quel commentaire',
                'description' => 'Supprimer n\'importe quel commentaire sur une tâche',
            ],
            [
                'key' => 'manage_groups',
                'name' => 'Gérer les groupes',
                'description' => 'Créer, modifier et supprimer des groupes de tâches',
            ],
            [
                'key' => 'reorder_tasks',
                'name' => 'Réorganiser les tâches',
                'description' => 'Réorganiser les tâches',
            ],
            [
                'key' => 'add_user_to_project',
                'name' => 'Ajouter utilisateur au projet',
                'description' => 'Ajouter un utilisateur au projet',
            ],
            [
                'key' => 'export_tasks',
                'name' => 'Exporter les tâches',
                'description' => 'Exporter les tâches dans un projet',
            ],
            [
                'key' => 'manage_commit',
                'name' => 'Gérer les commits',
                'description' => 'Ajouter/Supprimer un commit d\'une tâche',
            ],
            [
                'key' => 'manage_dates',
                'name' => 'Gérer les dates',
                'description' => 'Ajouter/Modifier les dates d\'une tâche',
            ]
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::updateOrCreate([
                'key' => $permission['key'],
            ], [
                'key' => $permission['key'],
                'name' => $permission['name'],
                'description' => $permission['description'],
            ]);
        }
    }
}
