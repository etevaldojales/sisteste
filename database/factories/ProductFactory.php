<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->ean13(),
            'name' => fake()->text(20),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 1, 1000),
            'category' => fake()->word(),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
