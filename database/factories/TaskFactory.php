<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence,
            'due_date' => now()->addDays($this->faker->numberBetween(0, 7))->format('Y-m-d'),
            'priority' => $this->faker->numberBetween(1, 5),
            'frequency' => $this->faker->numberBetween(1, 5),
        ];
    }
}
