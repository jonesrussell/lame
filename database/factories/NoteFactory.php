<?php

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Note::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->words(5, true),
            'done' => fake()->boolean(30), // 30% chance of being done
        ];
    }

    /**
     * Indicate that the note is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'done' => true,
        ]);
    }

    /**
     * Indicate that the note is not done.
     */
    public function undone(): static
    {
        return $this->state(fn (array $attributes) => [
            'done' => false,
        ]);
    }

    /**
     * Indicate that the note has long content.
     */
    public function longContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->words(20, true),
        ]);
    }
}
