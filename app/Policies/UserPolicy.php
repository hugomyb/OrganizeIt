<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Cache;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Cache user permissions for optimization.
     *
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    private function getUserPermissions(User $user)
    {
        return Cache::remember('user_permissions_' . $user->id, 60, function () use ($user) {
            return $user->role()
                ->join('permission_role', 'roles.id', '=', 'permission_role.role_id')
                ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
                ->pluck('permissions.key');
        });
    }

    /**
     * Check if the user has a specific permission.
     *
     * @param User $user
     * @param string $permission
     * @return bool
     */
    private function hasPermission(User $user, $permission)
    {
        $permissions = $this->getUserPermissions($user);
        return $permissions->contains($permission);
    }

    public function manageTasks(User $user)
    {
        return $this->hasPermission($user, 'manage_tasks');
    }

    public function manageStatus(User $user)
    {
        return $this->hasPermission($user, 'manage_status');
    }

    public function changeStatus(User $user)
    {
        return $this->hasPermission($user, 'change_status');
    }

    public function changePriority(User $user)
    {
        return $this->hasPermission($user, 'change_priority');
    }

    public function assignUser(User $user)
    {
        return $this->hasPermission($user, 'assign_user');
    }

    public function editDescription(User $user)
    {
        return $this->hasPermission($user, 'edit_description');
    }

    public function manageAttachments(User $user)
    {
        return $this->hasPermission($user, 'manage_attachments');
    }

    public function addComment(User $user)
    {
        return $this->hasPermission($user, 'add_comment');
    }

    public function deleteAnyComment(User $user)
    {
        return $this->hasPermission($user, 'delete_any_comment');
    }

    public function manageGroups(User $user)
    {
        return $this->hasPermission($user, 'manage_groups');
    }

    public function reorderTasks(User $user)
    {
        return $this->hasPermission($user, 'reorder_tasks');
    }

    public function addUserToProject(User $user)
    {
        return $this->hasPermission($user, 'add_user_to_project');
    }

    public function manageDates(User $user)
    {
        return $this->hasPermission($user, 'manage_dates');
    }

    public function manageCommit(User $user)
    {
        return $this->hasPermission($user, 'manage_commit');
    }

    public function viewTaskCreator(User $user)
    {
        return $this->hasPermission($user, 'view_task_creator');
    }

    public function viewAssignedUsers(User $user)
    {
        return $this->hasPermission($user, 'view_assigned_users');
    }

    public function viewHistory(User $user)
    {
        return $this->hasPermission($user, 'view_history');
    }
}

