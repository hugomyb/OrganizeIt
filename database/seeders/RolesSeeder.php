<?php

namespace Database\Seeders;

use App\Models\Permission;
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
            [
                'id' => 1,
                'name' => 'Admin',
                'permissions' => Permission::all()->pluck('id')
            ],
            [
                'id' => 2,
                'name' => 'Utilisateur',
                'permissions' => [
                    Permission::where('key', 'add_comment')->first()->id,
                    Permission::where('key', 'manage_commit')->first()->id,
                    Permission::where('key', 'view_task_creator')->first()->id,
                    Permission::where('key', 'view_assigned_users')->first()->id,
                ]
            ],
            [
                'id' => 3,
                'name' => 'Freelance',
                'permissions' => [
                    Permission::where('key', 'add_comment')->first()->id,
                    Permission::where('key', 'change_status')->first()->id,
                    Permission::where('key', 'change_priority')->first()->id,
                    Permission::where('key', 'assign_user')->first()->id,
                    Permission::where('key', 'reorder_tasks')->first()->id,
                    Permission::where('key', 'manage_commit')->first()->id,
                    Permission::where('key', 'view_task_creator')->first()->id,
                    Permission::where('key', 'view_assigned_users')->first()->id,
                ]
            ],
            [
                'id' => 4,
                'name' => 'Client',
                'permissions' => [
                    Permission::where('key', 'manage_tasks')->first()->id,
                    Permission::where('key', 'add_comment')->first()->id,
                    Permission::where('key', 'change_status')->first()->id,
                    Permission::where('key', 'manage_attachments')->first()->id,
                    Permission::where('key', 'edit_description')->first()->id,
                ]
            ]
        ];

        foreach ($roles as $role) {
            $createdRole = \App\Models\Role::updateOrCreate([
                'name' => $role['name'],
            ], [
                'id' => $role['id'],
                'name' => $role['name'],
            ]);

            $createdRole->permissions()->sync($role['permissions']);
        }
    }
}
