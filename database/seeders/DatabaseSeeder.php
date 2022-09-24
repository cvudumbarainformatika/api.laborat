<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\Berita;
use App\Models\BeritaView;
use App\Models\Category;
use App\Models\Moto;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        // User::create([
        //     'name'=> 'Programer',
        //     'email'=> 'admin@app.com',
        //     'password'=> bcrypt('password'),
        // ]);

<<<<<<< HEAD
=======
        Category::create(['nama'=>'Warta RSUD', 'url'=>'warta-rsud']);
        Category::create(['nama'=>'Informasi', 'url'=>'informasi' ]);

        Berita::factory(100)->create();
        $category = Category::all();

        // Populate the pivot table
        Berita::all()->each(function ($berita) use ($category) {
            $berita->categories()->attach(
                $category->random(rand(1, 2))->pluck('id')->toArray()
            );
        });

        BeritaView::factory(10)->create();
>>>>>>> 5a5865f34e9a0866bee773204e2d5343edd57e01

    }
}
