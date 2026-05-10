<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Livewire\Traits\Alert;
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

    public User $user;

    public string $role = 'parent';

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public bool $modal = false;

    public function mount(): void
    {
        $this->authorize('create', User::class);

        $this->user = new User();
        $this->role = UserRole::Parent->value;
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
        return collect($this->allowedRoles())
            ->map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->all();
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $this->validate();

        $this->user->household_id = Auth::user()->household_id;
        $this->user->role = $this->role;
        $this->user->permissions = null;
        $this->user->password = bcrypt($this->password);
        $this->user->email_verified_at = now();
        $this->user->save();

        $this->dispatch('created');

        $this->reset();
        $this->user = new User();
        $this->role = UserRole::Parent->value;

        $this->success();
    }

    private function allowedRoles(): array
    {
        return collect(UserRole::cases())
            ->reject(fn (UserRole $role): bool => $role === UserRole::Owner && ! Auth::user()->hasRole(UserRole::Owner))
            ->values()
            ->all();
    }

    private function allowedRoleValues(): array
    {
        return collect($this->allowedRoles())
            ->map(fn (UserRole $role): string => $role->value)
            ->all();
    }
}
