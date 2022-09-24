<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->text('logo')->nullable();
            $table->text('banner')->nullable();
            $table->string('nama')->nullable();
            $table->string('title')->nullable();
            $table->string('alamat')->nullable();
            $table->string('desc')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('link_fb')->nullable();
            $table->text('link_instagram')->nullable();
            $table->text('link_youtube')->nullable();
            $table->text('link_map')->nullable();
            $table->json('section_one')->nullable();
            $table->json('section_two')->nullable();
            $table->json('themes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apps');
    }
}
