<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
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
        // Generamos el precio base primero para calcular costos coherentes
        $price = $this->faker->randomFloat(2, 10.00, 2000.00);

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(2),
            'price' => $price,
            'currency_id' => Currency::inRandomOrder()->first()?->id ?? Currency::factory(),

            // Simula un impuesto de 16% basado en el precio
            'tax_cost' => round($price * 0.16, 2),

            'manufacturing_cost' => $this->faker->randomFloat(2, $price * 0.3, $price * 0.5),
        ];
    }
}
