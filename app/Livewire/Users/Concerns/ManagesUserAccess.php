<?php

namespace App\Livewire\Users\Concerns;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\StudentAccessGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

trait ManagesUserAccess
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function roleOptionsForForm(): array
    {
        return collect($this->allowedRoles())
            ->map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    public function studentOptionsForForm(): array
    {
        return $this->householdStudents()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'label' => $student->name.' · '.$student->level,
            ])
            ->all();
    }

    /**
     * @return array<string, array<int, array{value: string, label: string, default: bool}>>
     */
    public function permissionGroupsForForm(): array
    {
        $defaults = $this->defaultPermissionValues($this->role);

        return collect(Permission::cases())
            ->map(fn (Permission $permission): array => [
                'value' => $permission->value,
                'label' => $permission->label(),
                'group' => $permission->group(),
                'default' => in_array($permission->value, $defaults, true),
            ])
            ->groupBy('group')
            ->map(fn ($permissions) => $permissions->values()->all())
            ->all();
    }

    public function resetPermissionsToRoleDefaults(): void
    {
        $this->permissions = $this->defaultPermissionValues($this->role);
    }

    /**
     * @return array<int, UserRole>
     */
    protected function allowedRoles(): array
    {
        return collect(UserRole::cases())
            ->reject(fn (UserRole $role): bool => $role === UserRole::Owner && ! Auth::user()->hasRole(UserRole::Owner))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function allowedRoleValues(): array
    {
        return collect($this->allowedRoles())
            ->map(fn (UserRole $role): string => $role->value)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function defaultPermissionValues(string $role): array
    {
        return array_values(config("homeschool.role_permissions.{$role}", []));
    }

    protected function studentScopeIsRequired(): bool
    {
        return in_array($this->role, [UserRole::Student->value, UserRole::Evaluator->value], true);
    }

    /**
     * @param  array<int, string>  $selectedPermissions
     * @return array<string, bool>|null
     */
    protected function permissionOverridesFor(string $role, array $selectedPermissions): ?array
    {
        if ($role === UserRole::Owner->value) {
            return null;
        }

        $selectedPermissions = array_values(array_unique(array_intersect($selectedPermissions, Permission::values())));
        $defaults = $this->defaultPermissionValues($role);
        $overrides = [];

        foreach (Permission::values() as $permission) {
            $isDefault = in_array($permission, $defaults, true);
            $isSelected = in_array($permission, $selectedPermissions, true);

            if ($isDefault !== $isSelected) {
                $overrides[$permission] = $isSelected;
            }
        }

        return $overrides === [] ? null : $overrides;
    }

    protected function syncStudentAccess(User $user, ?int $studentId): void
    {
        $householdId = Auth::user()->household_id;

        Student::query()
            ->where('household_id', $householdId)
            ->where('login_user_id', $user->id)
            ->update(['login_user_id' => null]);

        StudentAccessGrant::query()
            ->where('household_id', $householdId)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        if ($this->role === UserRole::Student->value && $studentId !== null) {
            Student::query()
                ->where('household_id', $householdId)
                ->whereKey($studentId)
                ->update(['login_user_id' => $user->id]);
        }

        if ($this->role === UserRole::Evaluator->value && $studentId !== null) {
            StudentAccessGrant::create([
                'household_id' => $householdId,
                'student_id' => $studentId,
                'user_id' => $user->id,
                'created_by_user_id' => Auth::id(),
            ]);
        }
    }

    protected function studentIdForUser(User $user): ?int
    {
        if ($user->hasRole(UserRole::Student)) {
            return $user->studentProfile?->id;
        }

        if ($user->hasRole(UserRole::Evaluator)) {
            return $user->studentAccessGrants()->first()?->student_id;
        }

        return null;
    }

    /**
     * @return Collection<int, Student>
     */
    private function householdStudents(): Collection
    {
        return Student::query()
            ->where('household_id', Auth::user()->household_id)
            ->orderBy('position')
            ->orderByDesc('age')
            ->orderBy('name')
            ->get();
    }
}
