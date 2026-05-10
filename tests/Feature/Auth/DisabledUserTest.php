<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('does not let a disabled user log in', function () {
    User::factory()->disabled()->create([
        'email' => 'disabled@example.com',
        'password' => bcrypt('password123'),
    ]);

    post(route('login'), [
        'email' => 'disabled@example.com',
        'password' => 'password123',
    ])
        ->assertSessionHasErrors('email');

    assertGuest();
});

it('logs out a disabled authenticated session on protected routes', function () {
    $user = User::factory()->disabled()->create();

    actingAs($user);

    get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    assertGuest();
});
