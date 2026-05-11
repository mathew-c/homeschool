<?php

use App\Livewire\Users\Index;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->auth = User::factory()->owner()->create();

    Auth::login($this->auth);

    User::factory()->count(15)->inHousehold($this->auth->household)->create();
    User::factory()->create();
});

it('renders the users index component', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertViewIs('livewire.users.index');
});

it('initializes with default settings', function () {
    Livewire::test(Index::class)
        ->assertSet('quantity', 5)
        ->assertSet('search', null)
        ->assertSet('sort', [
            'column' => 'created_at',
            'direction' => 'desc',
        ]);
});

it('verifies component headers', function () {
    $component = Livewire::test(Index::class);

    $headers = [
        ['index' => 'name', 'label' => 'Name'],
        ['index' => 'role', 'label' => 'Role'],
        ['index' => 'access', 'label' => 'Access'],
        ['index' => 'permissions', 'label' => 'Permissions'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'created_at', 'label' => 'Created'],
        ['index' => 'action', 'sortable' => false],
    ];

    $component->assertSet('headers', $headers);
});

it('fetches paginated household users including the owner row', function () {
    $rows = Livewire::test(Index::class)->get('rows');

    expect($rows)
        ->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($rows->total())
        ->toBe(16)
        ->and($rows->pluck('id'))->toContain($this->auth->id);

});

it('filters users by search term', function () {
    $user = User::factory()->inHousehold($this->auth->household)->create([
        'name' => 'John Unique Searchable',
        'email' => 'john.unique@example.com',
    ]);

    $component = Livewire::test(Index::class)
        ->set('search', 'John Unique');

    $rows = $component->get('rows');

    expect($rows->total())
        ->toBe(1)
        ->and($rows->first()->id)
        ->toBe($user->id);
});

it('supports searching by email', function () {
    $user = User::factory()->inHousehold($this->auth->household)->create([
        'name' => 'Unique Search User',
        'email' => 'unique.searchable@example.com',
    ]);

    $component = Livewire::test(Index::class)->set('search', 'unique.searchable');

    $rows = $component->get('rows');

    expect($rows->total())
        ->toBe(1)
        ->and($rows->first()->id)
        ->toBe($user->id);
});

it('supports changing pagination quantity', function () {
    $component = Livewire::test(Index::class)->set('quantity', 5);

    $rows = $component->get('rows');

    expect($rows->perPage())
        ->toBe(5)
        ->and($rows->total())
        ->toBe(16);
});

it('supports sorting by different columns', function () {
    $component = Livewire::test(Index::class)
        ->set('sort', [
            'column' => 'name',
            'direction' => 'asc',
        ]);

    $sort = $component->get('rows')->pluck('name')->toArray();

    expect($sort === array_values(Arr::sort($sort)))->toBeTrue();
});

it('handles empty search results', function () {
    $component = Livewire::test(Index::class)->set('search', 'non-existent-user');

    expect($component->get('rows')->total())->toBe(0);
});

it('shows user roles for household members', function () {
    User::factory()->student()->inHousehold($this->auth->household)->create([
        'name' => 'Tor Role Check',
        'email' => 'tor-role@example.com',
    ]);

    $rows = Livewire::test(Index::class)
        ->set('search', 'tor-role@example.com')
        ->get('rows');

    expect($rows->first()->role)->toBe(UserRole::Student);
});

it('blocks parents from the owner-only user list', function () {
    $parent = User::factory()->parent()->create();

    actingAs($parent);

    get(route('users.index'))->assertForbidden();
});
