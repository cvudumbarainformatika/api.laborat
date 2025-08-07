<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestMutasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('mutasi_gudangdepo')->truncate();

        DB::table('mutasi_gudangdepo')->insert([
            [
                'no_permintaan' => 'PRM001',
                'nopenerimaan'  => 'PNM001',
                'kd_obat'       => 'OB001',
                'jml'           => 10.00,
                'harga'         => 1000.00,
                'tglpenerimaan' => Carbon::parse('2024-01-01 08:00:00'),
                'tglexp'        => Carbon::parse('2025-01-01'),
                'nobatch'       => 'B001',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'no_permintaan' => 'PRM002',
                'nopenerimaan'  => 'PNM002',
                'kd_obat'       => 'OB001',
                'jml'           => 15.00,
                'harga'         => 1100.00,
                'tglpenerimaan' => Carbon::parse('2024-02-01 08:00:00'),
                'tglexp'        => Carbon::parse('2025-02-01'),
                'nobatch'       => 'B002',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        ]);
    }
}
