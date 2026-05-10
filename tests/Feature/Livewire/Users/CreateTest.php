<?php

use App\Livewire\Users\Create;
use App\Enums\UserRole;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->auth = User::factory()->owner()->create();

    actingAs($this->auth);
});

it('renders the create user component', function () {
    Livewire::test(Create::class)
        ->assertOk()
        ->assertViewIs('livewire.users.create');
});

it('initializes with a new user', function () {
    Livewire::test(Create::class)
        ->assertSet('user', fn ($user) => $user instanceof User)
        ->assertSet('role', UserRole::Parent->value)
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null);
});

it('validates user creation with valid data', function () {
    $data = [
        'user.name' => 'John Doe',
        'user.email' => 'john@example.com',
        'role' => UserRole::Evaluator->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    Livewire::test(Create::class)
        ->set($data)
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseHas('users', [
        'household_id' => $this->auth->household_id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => UserRole::Evaluator->value,
    ]);
});

it('requires name', function () {
    Livewire::test(Create::class)
        ->set('user.name', '')
        ->set('user.email', 'john@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['user.name' => 'required']);
});

it('requires unique email', function () {
    User::create([
        'household_id' => $this->auth->household_id,
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'role' => UserRole::Parent->value,
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(Create::class)
        ->set('user.name', 'John Doe')
        ->set('user.email', 'existing@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['user.email' => 'unique']);
});

it('validates email format', function () {
    Livewire::test(Create::class)
        ->set('user.name', 'John Doe')
        ->set('user.email', 'invalid-email')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['user.email' => 'email']);
});

it('requires password confirmation', function () {
    Livewire::test(Create::class)
        ->set('user.name', 'John Doe')
        ->set('user.email', 'john@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different-password')
        ->call('save')
        ->assertHasErrors(['password' => 'confirmed']);
});

it('requires minimum password length', function () {
    Livewire::test(Create::class)
        ->set('user.name', 'John Doe')
        ->set('user.email', 'john@example.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('save')
        ->assertHasErrors(['password' => 'min']);
});

it('sets email verified at when creating user', function () {
    $data = [
        'user.name' => 'John Doe',
        'user.email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    Livewire::test(Create::class)
        ->set($data)
        ->call('save');

    $user = User::where('email', 'john@example.com')->first();

    expect($user->email_verified_at)->not()->toBeNull();
});

it('resets form after successful creation', function () {
    $data = [
        'user.name' => 'John Doe',
        'user.email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    Livewire::test(Create::class)
        ->set($data)
        ->call('save')
        ->assertSet('user', fn ($user) => $user instanceof User && $user->name === null)
        ->assertSet('role', UserRole::Parent->value)
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null);
});

it('dispatches created event', function () {
    $data = [
        'user.name' => 'John Doe',
        'user.email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    Livewire::test(Create::class)
        ->set($data)
        ->call('save')
        ->assertDispatched('created');
});
