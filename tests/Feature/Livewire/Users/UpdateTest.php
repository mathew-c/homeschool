<?php

use App\Livewire\Users\Update;
use App\Enums\UserRole;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->auth = User::factory()->owner()->create();

    actingAs($this->auth);

    $this->original = User::factory()->parent()->inHousehold($this->auth->household)->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
});

it('renders the update user component', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->assertOk()
        ->assertViewIs('livewire.users.update');
});

it('initializes with existing user data', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->assertSet('user.name', 'Original Name')
        ->assertSet('user.email', 'original@example.com')
        ->assertSet('role', UserRole::Parent->value)
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null);
});

it('load the correct use', function () {
    Livewire::test(Update::class)
        ->call('load', $this->original)
        ->assertSet('user.name', 'Original Name')
        ->assertSet('user.email', 'original@example.com')
        ->assertSet('role', UserRole::Parent->value)
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null);
});

it('updates user name and email', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.name', 'Updated Name')
        ->set('user.email', 'updated@example.com')
        ->set('role', UserRole::Evaluator->value)
        ->call('save')
        ->assertHasNoErrors();

    $updated = User::find($this->original->id);

    expect($updated->name)
        ->toBe('Updated Name')
        ->and($updated->email)
        ->toBe('updated@example.com')
        ->and($updated->role)
        ->toBe(UserRole::Evaluator);
});

it('requires name', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.name', '')
        ->set('user.email', 'updated@example.com')
        ->call('save')
        ->assertHasErrors(['user.name' => 'required']);
});

it('validates unique email with ignore', function () {
    User::factory()->inHousehold($this->auth->household)->create([
        'email' => 'existing@example.com',
    ]);

    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.email', 'existing@example.com')
        ->call('save')
        ->assertHasErrors(['user.email' => 'unique']);
});

it('updates password when provided', function () {
    $old = $this->original->password;

    Livewire::test(Update::class, ['user' => $this->original])
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('save')
        ->assertHasNoErrors();

    $updated = User::find($this->original->id);

    expect($updated->password)->not()->toBe($old);
});

it('does not update password when not provided', function () {
    $old = $this->original->password;

    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    $updated = User::find($this->original->id);

    expect($updated->password)->toBe($old);
});

it('requires password confirmation', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'different-password')
        ->call('save')
        ->assertHasErrors(['password' => 'confirmed']);
});

it('requires minimum password length', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('save')
        ->assertHasErrors(['password' => 'min']);
});

it('dispatches updated event', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.name', 'Updated Name')
        ->call('save')
        ->assertDispatched('updated');
});

it('resets form after successful update', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.name', 'Updated Name')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('save')
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null);
});

it('validates email format', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.email', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['user.email' => 'email']);
});
