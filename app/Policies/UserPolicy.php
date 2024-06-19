<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can manage tasks.
     *
     * @param User $user
     * @return bool
     */
    public function manageTasks(User $user)
    {
        return $user->hasPermission('manage_tasks');
    }

    /**
     * Determine if the user can manage statuses.
     *
     * @param User $user
     * @return bool
     */
    public function manageStatus(User $user)
    {
        return $user->hasPermission('manage_status');
    }

    /**
     * Determine if the user can change statuses.
     *
     * @param User $user
     * @return bool
     */
    public function changeStatus(User $user)
    {
        return $user->hasPermission('change_status');
    }

    /**
     * Determine if the user can change priorities.
     *
     * @param User $user
     * @return bool
     */
    public function changePriority(User $user)
    {
        return $user->hasPermission('change_priority');
    }

    /**
     * Determine if the user can assign users.
     *
     * @param User $user
     * @return bool
     */
    public function assignUser(User $user)
    {
        return $user->hasPermission('assign_user');
    }

    /**
     * Determine if the user can edit task descriptions.
     *
     * @param User $user
     * @return bool
     */
    public function editDescription(User $user)
    {
        return $user->hasPermission('edit_description');
    }

    /**
     * Determine if the user can manage attachments.
     *
     * @param User $user
     * @return bool
     */
    public function manageAttachments(User $user)
    {
        return $user->hasPermission('manage_attachments');
    }

    /**
     * Determine if the user can add comments.
     *
     * @param User $user
     * @return bool
     */
    public function addComment(User $user)
    {
        return $user->hasPermission('add_comment');
    }

    /**
     * Determine if the user can delete any comments.
     *
     * @param User $user
     * @return bool
     */
    public function deleteAnyComment(User $user)
    {
        return $user->hasPermission('delete_any_comment');
    }

    /**
     * Determine if the user can manage groups.
     *
     * @param User $user
     * @return bool
     */
    public function manageGroups(User $user)
    {
        return $user->hasPermission('manage_groups');
    }

    /**
     * Determine if the user can reorder tasks.
     *
     * @param User $user
     * @return bool
     */
    public function reorderTasks(User $user)
    {
        return $user->hasPermission('reorder_tasks');
    }

    /**
     * Determine if the user can add users to projects.
     *
     * @param User $user
     * @return bool
     */
    public function addUserToProject(User $user)
    {
        return $user->hasPermission('add_user_to_project');
    }

    /**
     * Determine if the user can manage dates.
     *
     * @param User $user
     * @return bool
     */
    public function manageDates(User $user)
    {
        return $user->hasPermission('manage_dates');
    }

    /**
     * Determine if the user can manage commits.
     *
     * @param User $user
     * @return bool
     */
    public function manageCommit(User $user)
    {
        return $user->hasPermission('manage_commit');
    }

    /**
     * Determine if the user can view the task creator.
     *
     * @param User $user
     * @return bool
     */
    public function viewTaskCreator(User $user)
    {
        return $user->hasPermission('view_task_creator');
    }

    /**
     * Determine if the user can view assigned users.
     *
     * @param User $user
     * @return bool
     */
    public function viewAssignedUsers(User $user)
    {
        return $user->hasPermission('view_assigned_users');
    }
}
