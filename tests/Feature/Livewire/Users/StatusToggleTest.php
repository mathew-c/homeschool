<?php

use App\Livewire\Users\StatusToggle;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;

beforeEach(function () {
    $this->auth = User::factory()->owner()->create();
    $this->user = User::factory()->parent()->inHousehold($this->auth->household)->create();

    actingAs($this->auth);
});

it('renders the status toggle component', function () {
    Livewire::test(StatusToggle::class, ['user' => $this->user])
        ->assertOk()
        ->assertSee('Disable');
});

it('confirms before disabling a user', function () {
    Livewire::test(StatusToggle::class, ['user' => $this->user])
        ->call('confirmDisable')
        ->assertDispatched('ts-ui:dialog');

    expect($this->user->fresh()->disabled_at)->toBeNull();
});

it('disables a user without deleting the account', function () {
    Livewire::test(StatusToggle::class, ['user' => $this->user])
        ->call('disable')
        ->assertDispatched('user-status-updated');

    assertModelExists($this->user);

    expect($this->user->fresh()->disabled_at)->not()->toBeNull();
});

it('re-enables a disabled user', function () {
    $this->user->disable();

    Livewire::test(StatusToggle::class, ['user' => $this->user->fresh()])
        ->call('enable')
        ->assertDispatched('user-status-updated');

    expect($this->user->fresh()->disabled_at)->toBeNull();
});

it('does not let an owner disable their own account', function () {
    Livewire::test(StatusToggle::class, ['user' => $this->auth])
        ->call('disable')
        ->assertForbidden();
});

it('does not let an owner disable another owner account', function () {
    $otherOwner = User::factory()->owner()->inHousehold($this->auth->household)->create();

    Livewire::test(StatusToggle::class, ['user' => $otherOwner])
        ->call('disable')
        ->assertForbidden();
});
