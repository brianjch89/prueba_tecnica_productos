<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 1. Tomamos un producto al azar de los que ya existen
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();

        // 2. Tomamos una moneda al azar de las 7 que ya existen
        $currency = Currency::inRandomOrder()->first() ?? Currency::factory()->create();

        $calculatedPrice = round($product->price * $currency->exchange_rate, 2);

        return [
            'product_id' => $product->id,
            'currency_id' => $currency->id,
            'price' => $calculatedPrice,
        ];
    }
}
