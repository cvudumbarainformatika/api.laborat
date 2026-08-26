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
        Schema::connection('farmasi')->create('pelayanan_penilaian_obat_luars', function (Blueprint $table) {
            $table->id();
            $table->string('norm', 50)->nullable()->index();
            $table->string('noreg', 50)->nullable()->index();
            $table->date('tanggal')->nullable();
            $table->longText('lembar_resep')->nullable();
            $table->text('detail')->nullable();
            $table->string('check_1', 100)->nullable()->index();
            $table->string('double_check_2', 100)->nullable()->index();
            $table->string('user_input', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('farmasi')->dropIfExists('pelayanan_penilaian_obat_luars');
    }
};
