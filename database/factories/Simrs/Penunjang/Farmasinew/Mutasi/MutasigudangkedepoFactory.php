<?php

namespace Database\Factories\Simrs\Penunjang\Farmasinew\Mutasi;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mutasigudangkedepo>
 */
class MutasigudangkedepoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_permintaan' => 'PM-' . $this->faker->unique()->numerify('####'),
            'nopenerimaan' => 'PN-' . $this->faker->unique()->numerify('####'),
            'kd_obat' => $this->faker->randomElement(['OB001', 'OB002', 'OB003']),
            'jml' => $this->faker->randomFloat(2, 1, 100), // jumlah antara 1–100
            'tglpenerimaan' => Carbon::now()->subDays(rand(1, 30)),
            'harga' => $this->faker->randomFloat(2, 5000, 50000), // harga antara 5rb–50rb
            'tglexp' => Carbon::now()->addMonths(rand(6, 24)),
            'nobatch' => 'BATCH-' . $this->faker->unique()->numerify('###'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
