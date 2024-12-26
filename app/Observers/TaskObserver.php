<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Support\Facades\Auth;

class TaskObserver
{
    public function created(Task $task)
    {
        $this->logHistory($task, 'task.history.created', [
            'user' => $this->getUserName($task->created_by),
        ]);
    }

    public function updated(Task $task)
    {
        $changes = $task->getChanges();
        $original = $task->getOriginal();

        foreach ($changes as $attribute => $newValue) {
            $oldValue = $original[$attribute] ?? null;

            switch ($attribute) {
                case 'status_id':
                    $this->logHistory($task, 'task.history.status_changed', [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ]);
                    break;
                case 'priority_id':
                    $this->logHistory($task, 'task.history.priority_changed', [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ]);
                    break;
                case 'start_date':
                case 'due_date':
                    $this->logHistory($task, 'task.history.date_changed', [
                        'field' => $attribute,
                        'new' => $newValue,
                    ]);
                    break;
                case 'description':
                    $this->logHistory($task, 'task.history.description_changed', [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ]);
                    break;
                default:
                    break;
            }
        }
    }

    public function logHistory(Task $task, $action, $parameters = [])
    {
        $userId = Auth::check() ? Auth::id() : null;

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'action' => $action,
            'parameters' => json_encode($parameters),
        ]);
    }

    private function getUserName($userId)
    {
        return \App\Models\User::find($userId)?->name ?? 'Unknown User';
    }

    public function deleted(Task $task)
    {
        $this->logHistory($task, 'task.history.deleted', [
            'user' => $this->getUserName(\auth()->id()),
        ]);
    }
}
