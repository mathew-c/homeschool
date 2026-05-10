<?php

namespace App\Livewire\Users;

use App\Livewire\Traits\Alert;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class StatusToggle extends Component
{
    use AuthorizesRequests;
    use Alert;

    public User $user;

    public function render(): string
    {
        return <<<'HTML'
        <div>
            @if ($user->isDisabled())
                <button class="user-action-button reset" type="button" wire:click="enable">Enable</button>
            @else
                <button class="user-action-button danger" type="button" wire:click="confirmDisable">Disable</button>
            @endif
        </div>
        HTML;
    }

    #[Renderless]
    public function confirmDisable(): void
    {
        $this->authorize('disable', $this->user);

        $this->question()
            ->confirm(method: 'disable')
            ->cancel()
            ->send();
    }

    public function disable(): void
    {
        $this->authorize('disable', $this->user);

        $this->user->disable();

        $this->dispatch('user-status-updated');

        $this->success();
    }

    public function enable(): void
    {
        $this->authorize('enable', $this->user);

        $this->user->enable();

        $this->dispatch('user-status-updated');

        $this->success();
    }
}
