<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewAssignments);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->hasPermission(Permission::ViewAssignments)
            && $user->canAccessStudent($assignment->student);
    }

    public function create(User $user, Student $student): bool
    {
        return $user->hasPermission(Permission::ManageAssignments)
            && $user->canAccessStudent($student);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->hasPermission(Permission::ManageAssignments)
            && $user->canAccessStudent($assignment->student);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function move(User $user, Assignment $assignment): bool
    {
        return $user->canAccessStudent($assignment->student)
            && (
                $user->hasPermission(Permission::ManageAssignments)
                || $user->hasPermission(Permission::MoveAssignments)
            );
    }

    public function submitEvidence(User $user, Assignment $assignment): bool
    {
        return $user->canAccessStudent($assignment->student)
            && (
                $user->hasPermission(Permission::ManageAssignments)
                || $user->hasPermission(Permission::SubmitEvidence)
            );
    }

    public function submitReflection(User $user, Assignment $assignment): bool
    {
        return $user->canAccessStudent($assignment->student)
            && (
                $user->hasPermission(Permission::ManageAssignments)
                || $user->hasPermission(Permission::SubmitReflections)
            );
    }

    public function grade(User $user, Assignment $assignment): bool
    {
        return $user->hasPermission(Permission::ManageGrades)
            && $user->canAccessStudent($assignment->student);
    }

    public function viewGrade(User $user, Assignment $assignment): bool
    {
        return $user->hasPermission(Permission::ViewGrades)
            && $user->canAccessStudent($assignment->student);
    }
}
