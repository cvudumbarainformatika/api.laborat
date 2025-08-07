<?php

namespace Database\Factories\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stokreal>
 */
class StokrealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kdobat' => 'OB001',
            'kdruang' => 'G001',
            'nopenerimaan' => $this->faker->unique()->numerify('PN###'),
            'nobatch' => $this->faker->bothify('BATCH-####'),
            'tglpenerimaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'tglexp' => $this->faker->dateTimeBetween('now', '+1 year'),
            'jumlah' => $this->faker->numberBetween(5, 50),
            'harga' => $this->faker->randomFloat(2, 1000, 5000),
        ];
    }
}
