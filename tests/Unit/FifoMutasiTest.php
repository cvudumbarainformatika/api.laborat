<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FifoMutasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jalankan seeder setiap kali test jalan
        $this->artisan('db:seed', ['--class' => 'TestMutasiSeeder']);
    }
    public function test_fifo_mutasi_mengambil_dari_batch_lama_terlebih_dahulu()
    {
        // Kita misalkan sistem ingin mutasi sebanyak 20 dari kd_obat OB001
        $jumlahYangDiminta = 20;
        $kd_obat = 'OB001';

        // Ambil stok FIFO
        $stok = DB::table('mutasi_gudangdepo')
            ->where('kd_obat', $kd_obat)
            ->where('jml', '>', 0)
            ->orderBy('tglpenerimaan')
            ->get();

        $sisa = $jumlahYangDiminta;
        $pengambilan = [];

        foreach ($stok as $baris) {
            if ($sisa <= 0) break;

            $ambil = min($baris->jml, $sisa);
            $pengambilan[] = [
                'nobatch' => $baris->nobatch,
                'diambil' => $ambil,
            ];
            $sisa -= $ambil;
        }

        // Assert: Batch pertama (B001) habis, sisanya dari B002
        $this->assertCount(2, $pengambilan);

        $this->assertEquals('B001', $pengambilan[0]['nobatch']);
        $this->assertEquals(10.00, $pengambilan[0]['diambil']);

        $this->assertEquals('B002', $pengambilan[1]['nobatch']);
        $this->assertEquals(10.00, $pengambilan[1]['diambil']);
    }
}
