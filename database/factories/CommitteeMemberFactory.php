<?php

namespace Database\Factories;

use App\Models\CommitteeMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sort_order' => $this->faker->numberBetween(0, 100),
            'membership_number' => 'L'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->name(),
            'post' => $this->faker->jobTitle(),
            'post_for_badge' => $this->faker->jobTitle(),
            'mobile' => $this->faker->numerify('98########'),
            'photo' => null,
        ];
    }

    public function withoutMobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'mobile' => null,
        ]);
    }
}
