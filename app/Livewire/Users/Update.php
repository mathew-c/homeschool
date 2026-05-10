<?php

namespace App\Livewire\Users;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Livewire\Traits\Alert;
use App\Livewire\Users\Concerns\ManagesUserAccess;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Update extends Component
{
    use AuthorizesRequests;
    use Alert;
    use ManagesUserAccess;

    public ?User $user;

    public string $role = 'parent';

    public ?int $studentId = null;

    public array $permissions = [];

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public ?string $temporaryPassword = null;

    public bool $modal = false;

    public function mount(?User $user = null): void
    {
        if (! $user instanceof User || ! $user->exists) {
            $this->user = null;

            return;
        }

        $this->setUser($user);
    }

    public function render(): View
    {
        return view('livewire.users.update');
    }

    #[On('load::user')]
    public function load(User $user): void
    {
        $this->setUser($user);

        $this->modal = true;
    }

    public function rules(): array
    {
        return [
            'user.name' => [
                'required',
                'string',
                'max:255',
            ],
            'user.email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],
            'role' => [
                'required',
                Rule::in($this->allowedRoleValues()),
            ],
            'studentId' => [
                Rule::requiredIf($this->studentScopeIsRequired()),
                'nullable',
                Rule::exists('students', 'id')->where('household_id', Auth::user()->household_id),
            ],
            'permissions' => [
                'array',
            ],
            'permissions.*' => [
                Rule::in(Permission::values()),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    #[Computed]
    public function roleOptions(): array
    {
        return $this->roleOptionsForForm();
    }

    #[Computed]
    public function studentOptions(): array
    {
        return $this->studentOptionsForForm();
    }

    #[Computed]
    public function permissionGroups(): array
    {
        return $this->permissionGroupsForForm();
    }

    public function updatedRole(): void
    {
        $this->studentId = null;
        $this->resetPermissionsToRoleDefaults();
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $this->validate();

        $this->user->role = $this->role;
        $this->user->permissions = $this->permissionOverridesFor($this->role, $this->permissions);
        $this->user->password = when($this->password !== null, bcrypt($this->password), $this->user->password);
        $this->user->save();

        $this->syncStudentAccess($this->user, $this->studentId);

        $this->dispatch('updated');

        $this->password = null;
        $this->password_confirmation = null;
        $this->temporaryPassword = null;

        $this->success();
    }

    public function resetPassword(): void
    {
        $this->authorize('resetPassword', $this->user);

        $this->temporaryPassword = Str::password(14);
        $this->user->password = bcrypt($this->temporaryPassword);
        $this->user->save();

        $this->password = null;
        $this->password_confirmation = null;

        $this->dispatch('updated');

        $this->success();
    }

    private function setUser(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->role = $user->role?->value ?? UserRole::Parent->value;
        $this->studentId = $this->studentIdForUser($user);
        $this->permissions = $user->permissionValues();
        $this->temporaryPassword = null;
        $this->password = null;
        $this->password_confirmation = null;
    }
}
