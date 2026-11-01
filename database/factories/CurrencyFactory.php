<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lista de monedas reales listas para pruebas
        $currencies = [
            ['name' => 'US Dollar', 'symbol' => 'USD'],
            ['name' => 'Euro', 'symbol' => 'EUR'],
            ['name' => 'British Pound', 'symbol' => 'GBP'],
            ['name' => 'Japanese Yen', 'symbol' => 'JPY'],
            ['name' => 'Canadian Dollar', 'symbol' => 'CAD'],
            ['name' => 'Australian Dollar', 'symbol' => 'AUD'],
            ['name' => 'Mexican Peso', 'symbol' => 'MXN'],
        ];

        // Selección al azar de una moneda en cada iteración
        $selected = $this->faker->unique()->randomElement($currencies);

        return [
            'name' => $selected['name'],
            'symbol' => $selected['symbol'],
            'exchange_rate' => $this->faker->randomFloat(2, 0.2, 1000.0),
        ];
    }
}
