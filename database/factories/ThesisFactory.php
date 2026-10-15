<?php

namespace Database\Factories;

use App\Models\Thesis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thesis>
 */
class ThesisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'supervisor_id' => User::factory(),
            'title' => fake()->sentence(4),
            'abstract' => fake()->paragraph(),
        ];
    }
}
