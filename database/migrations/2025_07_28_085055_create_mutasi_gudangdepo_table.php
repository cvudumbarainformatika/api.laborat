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
        Schema::create('mutasi_gudangdepo', function (Blueprint $table) {
            $table->id();
            $table->string('no_permintaan');
            $table->string('nopenerimaan');
            $table->string('kd_obat');
            $table->double('jml', 24, 2)->default(0.00);
            $table->dateTime('tglpenerimaan')->nullable();
            $table->double('harga', 24, 2)->default(0.00);
            $table->dateTime('tglexp')->nullable();
            $table->string('nobatch');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_gudangdepo');
    }
};
