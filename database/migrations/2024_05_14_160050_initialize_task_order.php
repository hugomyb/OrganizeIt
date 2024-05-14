<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // On suppose que tu as une colonne 'group_id' pour identifier le groupe de chaque tâche
        $groups = DB::table('tasks')->distinct()->pluck('group_id');

        foreach ($groups as $groupId) {
            $tasks = DB::table('tasks')
                ->where('group_id', $groupId)
                ->where('order', 0)  // Seulement les tâches qui n'ont pas encore d'ordre défini
                ->orderBy('created_at', 'asc')  // ou tout autre critère logique
                ->get();

            foreach ($tasks as $index => $task) {
                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['order' => $index + 1]);  // Commence l'ordre à 1
            }
        }
    }

    public function down()
    {
        // Réinitialiser l'ordre à 0 lors d'un rollback, facultatif selon le cas d'usage
        DB::table('tasks')->update(['order' => 0]);
    }
};
