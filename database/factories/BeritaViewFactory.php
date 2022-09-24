<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BeritaViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'berita_id'=> mt_rand(1,10),
            'ip'=> $this->faker->localIpv4(),
            'agent'=> $this->faker->userAgent(),
        ];
    }
}
