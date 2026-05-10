<?php

use App\Livewire\Users\Create;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\StudentAccessGrant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->auth = User::factory()->owner()->create();
    $this->student = Student::factory()->ownedBy($this->auth)->create([
        'name' => 'Tor',
        'level' => '9th grade',
    ]);

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
        'studentId' => $this->student->id,
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

    $user = User::where('email', 'john@example.com')->firstOrFail();

    expect(StudentAccessGrant::query()
        ->where('user_id', $user->id)
        ->where('student_id', $this->student->id)
        ->whereNull('revoked_at')
        ->exists())->toBeTrue();
});

it('requires a scoped student for evaluator users', function () {
    Livewire::test(Create::class)
        ->set('user.name', 'Independent Evaluator')
        ->set('user.email', 'evaluator@example.com')
        ->set('role', UserRole::Evaluator->value)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['studentId' => 'required']);
});

it('links student users to their student profile', function () {
    Livewire::test(Create::class)
        ->set('user.name', 'Tor Cornelisen')
        ->set('user.email', 'tor@example.com')
        ->set('role', UserRole::Student->value)
        ->set('studentId', $this->student->id)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'tor@example.com')->firstOrFail();

    expect($this->student->fresh()->login_user_id)->toBe($user->id);
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
        ->assertSet('studentId', null)
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
