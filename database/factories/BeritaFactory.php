<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BeritaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'judul'=> $this->faker->sentence(mt_rand(2,8)),
            'slug'=> $this->faker->slug(),
            'content'=> $this->faker->paragraph(mt_rand(5,10)),
            'thumbnail'=> 'thumbnail/zaf94TfRbnxndaycyb3Bm45w92Ih7N4W84sw5FST.jpg',
            'status'=> 2
        ];
    }
}
