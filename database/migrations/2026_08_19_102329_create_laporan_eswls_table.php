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
        Schema::create('laporan_eswls', function (Blueprint $table) {
            $table->id();
            $table->string('norm', 50)->nullable()->index();
            $table->string('noreg', 50)->nullable()->index();
            $table->string('kddokter', 50)->nullable();
            $table->text('asisten')->nullable();
            $table->date('tanggal')->nullable();
            
            // Informasi Umum
            $table->integer('berat_badan')->nullable();
            $table->integer('tinggi_badan')->nullable();
            $table->integer('sesi')->nullable();
            $table->string('no_eswl', 100)->nullable();
            $table->text('riwayat')->nullable();
            
            // Informasi Tindakan
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->string('elektrode_sn', 100)->nullable();
            $table->integer('jumlah_tembakan')->nullable();
            $table->integer('td_sistol')->nullable();
            $table->integer('td_diastol')->nullable();
            $table->integer('nadi')->nullable();
            $table->string('posisi', 50)->nullable();
            $table->text('batu_detail')->nullable(); // JSON data (posisi, ukuran)
            $table->string('lokalisasi_type', 50)->nullable();
            $table->integer('lokalisasi_lama')->nullable();
            $table->text('lokalisasi_xray')->nullable(); // JSON data (kv, ma, fluroscopy)
            $table->text('lokalisasi_usg')->nullable(); // JSON data (probe_fokus, probe_batu, perputaran)
            $table->string('lokalisasi_gambar', 50)->nullable();
            
            // Penembakan & Monitor
            $table->string('sinkronisasi', 50)->nullable();
            $table->text('penembakan_detail')->nullable(); // JSON data (batu 1, 2, 3: tembakan, power, energi)
            $table->string('monitor_usg', 255)->nullable();
            $table->string('monitor_rontgen', 255)->nullable();
            $table->string('tingkat_kesakitan', 50)->nullable();
            
            // Penggunaan Obat
            $table->text('obat_pre')->nullable();
            $table->text('obat_durante')->nullable();
            $table->text('obat_post')->nullable();
            
            // Evaluasi
            $table->string('kepecahan_batu', 50)->nullable();
            $table->integer('lama_penembakan')->nullable();
            $table->text('keterangan')->nullable();
            
            // Canvas/Drawing
            $table->longText('alternatif')->nullable(); // base64 webp drawing

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_eswls');
    }
};
