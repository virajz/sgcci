<?php

namespace Database\Factories;

use App\Models\Exhibition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExhibitionVisitor>
 */
class ExhibitionVisitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exhibition_id' => Exhibition::factory(),
            'phone_number' => $this->faker->unique()->numerify('##########'),
            'name' => $this->faker->name(),
            'company_name' => $this->faker->optional()->company(),
            'designation' => $this->faker->optional()->jobTitle(),
            'state' => $this->faker->randomElement(['Gujarat', 'Maharashtra', 'Rajasthan', 'Madhya Pradesh', 'Karnataka']),
            'city' => $this->faker->city(),
            'email' => $this->faker->optional()->safeEmail(),
            'business_segment' => $this->faker->randomElement(['Manufacturing', 'Trading', 'Services', 'IT & Technology', 'Healthcare']),
            'sub_business_segment' => $this->faker->word(),
            'source' => $this->faker->optional()->randomElement(['whatsapp', 'flyer', 'email', 'website']),
            'status' => 'confirmed',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function paymentPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'payment_pending',
        ]);
    }
}
