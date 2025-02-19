<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . time() . '-' . fake()->unique()->randomNumber(5),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
        ];
    }
} 