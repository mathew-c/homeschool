<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::Parent->value,
            'permissions' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inHousehold(Household $household): static
    {
        return $this->state(fn (array $attributes) => [
            'household_id' => $household->id,
        ]);
    }

    public function owner(): static
    {
        return $this->role(UserRole::Owner);
    }

    public function parent(): static
    {
        return $this->role(UserRole::Parent);
    }

    public function student(): static
    {
        return $this->role(UserRole::Student);
    }

    public function evaluator(): static
    {
        return $this->role(UserRole::Evaluator);
    }

    public function role(UserRole|string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role instanceof UserRole ? $role->value : $role,
        ]);
    }
}
