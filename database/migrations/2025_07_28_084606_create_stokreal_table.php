<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stokreal', function (Blueprint $table) {
            $table->id();
            $table->string('nopenerimaan')->nullable();
            $table->date('tglpenerimaan');
            $table->string('kdobat');
            $table->integer('jumlah');
            $table->string('kdruang');
            $table->decimal('harga', 15, 2)->nullable();
            $table->string('flag', 1)->default('')->nullable();
            $table->date('tglexp')->nullable();
            $table->string('nobatch')->nullable();
            $table->string('nodistribusi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stokreal');
    }
};
