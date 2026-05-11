<?php

use App\Livewire\Users\Update;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\StudentAccessGrant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->auth = User::factory()->owner()->create();
    $this->student = Student::factory()->ownedBy($this->auth)->create([
        'name' => 'Tor',
        'level' => '9th grade',
    ]);

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
        ->assertSet('studentId', null)
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null);
});

it('renders existing default permissions as checked', function () {
    $html = Livewire::test(Update::class, ['user' => $this->original])->html();

    expect($html)
        ->toMatch('/value="'.Permission::ManageStudents->value.'"[^>]*checked/')
        ->toMatch('/value="'.Permission::ManageCourses->value.'"[^>]*checked/');
});

it('load the correct use', function () {
    Livewire::test(Update::class)
        ->call('load', $this->original)
        ->assertSet('user.name', 'Original Name')
        ->assertSet('user.email', 'original@example.com')
        ->assertSet('role', UserRole::Parent->value)
        ->assertSet('studentId', null)
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null);
});

it('updates user name and email', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('user.name', 'Updated Name')
        ->set('user.email', 'updated@example.com')
        ->set('role', UserRole::Evaluator->value)
        ->set('studentId', $this->student->id)
        ->call('save')
        ->assertHasNoErrors();

    $updated = User::find($this->original->id);

    expect($updated->name)
        ->toBe('Updated Name')
        ->and($updated->email)
        ->toBe('updated@example.com')
        ->and($updated->role)
        ->toBe(UserRole::Evaluator);

    expect(StudentAccessGrant::query()
        ->where('user_id', $updated->id)
        ->where('student_id', $this->student->id)
        ->whereNull('revoked_at')
        ->exists())->toBeTrue();
});

it('requires a scoped student when changing to evaluator', function () {
    Livewire::test(Update::class, ['user' => $this->original])
        ->set('role', UserRole::Evaluator->value)
        ->call('save')
        ->assertHasErrors(['studentId' => 'required']);
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

it('generates a temporary password for a user', function () {
    $old = $this->original->password;

    $component = Livewire::test(Update::class, ['user' => $this->original])
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertSet('password', null)
        ->assertSet('password_confirmation', null)
        ->assertDispatched('updated');

    $temporaryPassword = $component->get('temporaryPassword');
    $updated = $this->original->fresh();

    expect($temporaryPassword)->toBeString()
        ->and($updated->password)->not()->toBe($old)
        ->and(Hash::check($temporaryPassword, $updated->password))->toBeTrue();
});

it('stores permission removals as explicit overrides', function () {
    $selected = array_values(array_diff($this->original->permissionValues(), [Permission::MoveAssignments->value]));

    Livewire::test(Update::class, ['user' => $this->original])
        ->set('permissions', $selected)
        ->call('save')
        ->assertHasNoErrors();

    $updated = $this->original->fresh();

    expect($updated->permissions)->toHaveKey(Permission::MoveAssignments->value)
        ->and($updated->permissions[Permission::MoveAssignments->value])->toBeFalse()
        ->and($updated->hasPermission(Permission::MoveAssignments))->toBeFalse();
});

it('closes the edit modal and shows success after saving custom permissions', function () {
    $selected = array_values(array_diff($this->original->permissionValues(), [Permission::ManageStudents->value]));

    Livewire::test(Update::class)
        ->call('load', $this->original)
        ->set('permissions', $selected)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('modal', false)
        ->assertDispatched('updated')
        ->assertDispatched('ts-ui:dialog', function (string $event, array $params) {
            return $event === 'ts-ui:dialog' &&
                $params['type'] === 'success' &&
                $params['title'] === 'Done!' &&
                $params['description'] === 'Task completed successfully.';
        });
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
