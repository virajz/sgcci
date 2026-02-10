<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exhibition>
 */
class ExhibitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence;

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph,
            'start_date' => $this->faker->date,
            'end_date' => $this->faker->date,
            'entry_type' => 'free',
            'entry_amount' => null,
            'created_by' => null,
        ];
    }

    public function paid(float $amount = 100.00): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_type' => 'paid',
            'entry_amount' => $amount,
        ]);
    }
}
