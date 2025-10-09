<?php

namespace Database\Factories;

use App\BookingStatus;
use App\Models\Exhibition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
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
            'brand_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone_code' => '+91',
            'phone_number' => fake()->numerify('##### #####'),
            'email' => fake()->safeEmail(),
            'city' => fake()->city(),
            'gst_number' => fake()->optional()->numerify('##XXXXX####X#X#'),
            'product_profile' => [fake()->randomElement(['4-wheelers', '2-wheelers', 'commercial-vehicles', 'spare-parts'])],
            'has_exhibited_before' => fake()->boolean(),
            'participation_years' => [],
            'is_sgcci_member' => fake()->boolean(),
            'membership_type' => null,
            'selected_stalls' => ['35', '47'],
            'status' => BookingStatus::PendingApproval,
        ];
    }
}
