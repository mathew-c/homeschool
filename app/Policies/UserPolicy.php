<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Owner)
            && $user->hasPermission(Permission::ManageUsers);
    }

    public function view(User $user, User $target): bool
    {
        return $this->sameHousehold($user, $target)
            && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->sameHousehold($user, $target)
            && $this->viewAny($user)
            && (! $target->hasRole(UserRole::Owner) || $user->hasRole(UserRole::Owner));
    }

    public function delete(User $user, User $target): bool
    {
        return $this->sameHousehold($user, $target)
            && $user->isNot($target)
            && $this->viewAny($user)
            && (! $target->hasRole(UserRole::Owner) || $user->hasRole(UserRole::Owner));
    }

    private function sameHousehold(User $user, User $target): bool
    {
        return $user->household_id !== null
            && $target->household_id === $user->household_id;
    }
}
