<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

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
        $categories_ids = DB::table('categories')->pluck('id')->toArray();

        return [
            'name' => $this->faker->name,
            "description" => $this->faker->paragraph,
            "quantity" => $this->faker->numberBetween(1 , 1000),
            "live" => true,
            "price" => $this->faker->randomFloat(2 , 1 , 5000),
            "priceBefore" => $this->faker->randomFloat(2 , 1 , 5000),
            "category_id" => $this->faker->randomElement($categories_ids),
            "user_id" => 1
        ];
    }
}
