<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewStudents)
            && (
                $user->hasRole(UserRole::Owner, UserRole::Parent, UserRole::Student)
                || $user->studentAccessGrants()->exists()
            );
    }

    public function view(User $user, Student $student): bool
    {
        return $user->canAccessStudent($student);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Owner, UserRole::Parent)
            && $user->hasPermission(Permission::ManageStudents);
    }

    public function update(User $user, Student $student): bool
    {
        return $student->household_id === $user->household_id
            && $user->hasRole(UserRole::Owner, UserRole::Parent)
            && $user->hasPermission(Permission::ManageStudents);
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    public function switch(User $user): bool
    {
        return $user->canSwitchStudents();
    }
}
