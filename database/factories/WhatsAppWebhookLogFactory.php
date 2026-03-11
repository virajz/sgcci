<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsAppWebhookLog>
 */
class WhatsAppWebhookLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'method' => $this->faker->randomElement(['POST', 'GET']),
            'path' => 'webhook/whatsapp',
            'headers' => ['content-type' => ['application/json']],
            'payload' => ['from' => $this->faker->numerify('91##########'), 'message' => $this->faker->sentence()],
            'ip' => $this->faker->ipv4(),
        ];
    }
}
