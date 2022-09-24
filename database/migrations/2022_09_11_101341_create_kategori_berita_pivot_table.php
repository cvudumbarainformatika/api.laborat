<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKategoriBeritaPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kategori_berita', function (Blueprint $table) {
            // $table->id();
            $table->foreignId('category_id')->index();
            $table->foreign('category_id')->on('categories')->nullable()->references('id')->cascadeOnDelete();
            $table->foreignId('berita_id')->index();
            $table->foreign('berita_id')->on('beritas')->nullable()->references('id')->cascadeOnDelete();
            $table->primary(['berita_id', 'category_id']);
            // $table->foreignId('category_id')->nullable()->references('id')->on('categories')->onDelete('cascade');
            // $table->foreignId('berita_id')->nullable()->references('id')->on('beritas')->onDelete('cascade');
            // $table->foreign('category_id')
            //     ->nullable()
            //     ->references('id')->on('categories')
            //     ->onUpdate('cascade')
            //     ->onDelete('cascade');
            // $table->foreign('berita_id')
            //     ->nullable()
            //     ->references('id')->on('beritas')
            //     ->onUpdate('cascade')
            //     ->onDelete('cascade');
            //     $table->primary(['berita_id', 'category_id']);
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kategori_berita');
    }
}
