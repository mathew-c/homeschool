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
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;
    use Alert;
    use ManagesUserAccess;

    public User $user;

    public string $role = 'parent';

    public ?int $studentId = null;

    public array $permissions = [];

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public bool $modal = false;

    public function mount(): void
    {
        $this->authorize('create', User::class);

        $this->user = new User();
        $this->role = UserRole::Parent->value;
        $this->resetPermissionsToRoleDefaults();
    }

    public function render(): View
    {
        return view('livewire.users.create');
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
                Rule::unique('users', 'email'),
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
                'required',
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
        $this->authorize('create', User::class);

        $this->validate();

        $this->user->household_id = Auth::user()->household_id;
        $this->user->role = $this->role;
        $this->user->permissions = $this->permissionOverridesFor($this->role, $this->permissions);
        $this->user->password = bcrypt($this->password);
        $this->user->email_verified_at = now();
        $this->user->save();

        $this->syncStudentAccess($this->user, $this->studentId);

        $this->dispatch('created');

        $this->reset();
        $this->user = new User();
        $this->role = UserRole::Parent->value;
        $this->resetPermissionsToRoleDefaults();

        $this->success();
    }
}
