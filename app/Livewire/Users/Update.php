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
use Livewire\Attributes\On;
use Livewire\Component;

class Update extends Component
{
    use AuthorizesRequests;
    use Alert;

    public ?User $user;

    public string $role = 'parent';

    public ?string $password = null;

    public ?string $password_confirmation = null;

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
        return collect($this->allowedRoles())
            ->map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->all();
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $this->validate();

        $this->user->role = $this->role;
        $this->user->password = when($this->password !== null, bcrypt($this->password), $this->user->password);
        $this->user->save();

        $this->dispatch('updated');

        $this->resetExcept('user');

        $this->success();
    }

    private function setUser(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->role = $user->role?->value ?? UserRole::Parent->value;
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
