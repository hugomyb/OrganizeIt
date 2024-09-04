<?php

namespace App\Concerns;

use App\Jobs\SendEmailJob;
use App\Mail\AssignToTaskMail;
use App\Mail\ChangeTaskPriorityMail;
use App\Mail\ChangeTaskStatusMail;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;

trait CanManageTasksInfo
{
    public function toggleUserToTask($userId)
    {
        $task = $this->task;
        $user = User::find($userId);
        if ($task) {
            if ($task->users()->where('user_id', $userId)->exists()) {
                $task->users()->detach($userId);

                $this->showNotification(__('user.unassigned'));
            } else {
                $task->users()->attach($userId);

                if (!auth()->user()->hasRole('Client')) {
                    SendEmailJob::dispatch(AssignToTaskMail::class, $user, $task, auth()->user());
                }

                $this->showNotification(__('user.assigned'));
            }

            $this->task = $task->fresh('users');

            $this->dispatch('modal-closed:' . $this->task->id);
        }
    }


    public function setTaskStatus($statusId)
    {
        $task = $this->task;

        $oldStatusId = $task->status_id;
        $oldStatus = Status::find($oldStatusId);

        if ($statusId != $task->status_id) {
            if ($statusId === Status::getCompletedStatusId()) {
                $task->update(['status_id' => $statusId, 'completed_at' => now()]);
            } else {
                $task->update(['status_id' => $statusId, 'completed_at' => null]);
            }

            $task->refresh();

            $users = $task->project->users;

            foreach ($users as $user) {
                if ($user->hasRole('Client')) {
                    if ($task->status->id === Status::getCompletedStatusId()) {
                        SendEmailJob::dispatch(ChangeTaskStatusMail::class, $user, $task, auth()->user(), $oldStatus, $user);
                    }
                } else {
                    SendEmailJob::dispatch(ChangeTaskStatusMail::class, $user, $task, auth()->user(), $oldStatus, $user);
                }
            }

            if ($task->parent_id) {
                $this->dispatch('modal-closed:' . $this->task->parent_id);
            }

            $this->showNotification(__('status.status_updated'));
        }
    }

    public function setTaskPriority($priorityId)
    {
        $task = $this->task;

        $oldPriorityId = $task->priority_id;
        $oldPriority = Priority::find($oldPriorityId);

        if ($priorityId != $task->priority_id) {
            $task->update(['priority_id' => $priorityId]);

            $task->refresh();

            $users = $task->project->users;

            foreach ($users as $user) {
                if (!$user->hasRole('Client')) {
                    SendEmailJob::dispatch(ChangeTaskPriorityMail::class, $user, $task, auth()->user(), $oldPriority);
                }
            }

            $this->showNotification(__('priority.priority_updated'));
        }
    }

    public function assignUserToTask($userId)
    {
        $task = $this->task;
        $user = User::find($userId);
        if ($task) {
            if (!$task->users()->where('user_id', $userId)->exists()) {
                $task->users()->attach($userId);

                if (!auth()->user()->hasRole('Client')) {
                    SendEmailJob::dispatch(AssignToTaskMail::class, $user, $task, auth()->user());
                }

                $this->showNotification(__('user.assigned'));
            }

            $this->dispatch('modal-closed:' . $this->task->id);
        }
    }
}
