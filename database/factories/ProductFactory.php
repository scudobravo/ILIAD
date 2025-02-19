<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'price' => fake()->randomFloat(2, 10, 1000),
        ];
    }
} 