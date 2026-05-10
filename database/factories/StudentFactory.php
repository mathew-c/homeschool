<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'birth_date' => now()->subYears(fake()->numberBetween(10, 17))->toDateString(),
            'age' => fake()->numberBetween(10, 17),
            'level' => fake()->randomElement(['6th grade', '7th grade', '9th grade', '10th grade']),
            'target_grad_year' => (string) fake()->numberBetween(2028, 2033),
            'weekly_capacity_hours' => fake()->numberBetween(15, 30),
            'photo_path' => null,
            'bio' => fake()->sentence(12),
            'learning_style' => fake()->sentence(10),
            'strengths' => fake()->sentence(10),
            'friction' => fake()->sentence(10),
            'college_direction' => fake()->sentence(10),
            'school_file_notes' => fake()->sentence(14),
            'interests' => ['AI', 'History'],
            'position' => 0,
        ];
    }

    public function inHousehold(Household $household): static
    {
        return $this->state(fn (array $attributes) => [
            'household_id' => $household->id,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'household_id' => $user->household_id,
            'user_id' => $user->id,
        ]);
    }
}
