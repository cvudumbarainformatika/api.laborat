<?php

namespace App\Console\Commands;

use App\Helpers\BridgingbpjsHelper;
use App\Models\Antrean\JadwalPoliCache;
use App\Models\Poli;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncJadwalPoli extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'antrean:sync-jadwal-poli';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi Jadwal Poli dari BPJS ke tabel jadwal_poli_cache selama 1 minggu kedepan (Senin-Minggu)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi jadwal poli dari BPJS...');
        Log::info('Start antrean:sync-jadwal-poli command');

        // Ambil daftar poli yang valid (bukan NULL, bukan kosong, dan bukan Penunjang)
        $polis = Poli::whereNotNull('rs6')
            ->where('rs6', '!=', '')
            ->where('rs4', '!=', 'Penunjang')
            ->get();

        if ($polis->isEmpty()) {
            $this->warn('Tidak ada data poli yang memenuhi kriteria.');
            return Command::SUCCESS;
        }

        // Tanggal diawali dari hari Senin minggu ini
        $startOfWeek = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY);

        $totalSynced = 0;

        // Loop selama 7 hari (Senin s/d Minggu)
        for ($i = 0; $i < 7; $i++) {
            $tanggal = $startOfWeek->copy()->addDays($i);
            $tglStr = $tanggal->format('Y-m-d');
            $hariStr = strtoupper($tanggal->locale('id')->isoFormat('dddd'));

            $this->info("Menyingkronkan tanggal: {$tglStr} ({$hariStr})...");

            foreach ($polis as $poli) {
                $kodePoli = trim($poli->rs6);

                try {
                    $response = BridgingbpjsHelper::get_url(
                        'antrean',
                        'jadwaldokter/kodepoli/' . $kodePoli . '/tanggal/' . $tglStr
                    );

                    if (
                        isset($response['metadata']['code']) &&
                        $response['metadata']['code'] == 200 &&
                        !empty($response['result'])
                    ) {
                        $results = is_array($response['result']) ? $response['result'] : [$response['result']];

                        foreach ($results as $dokterObj) {
                            $dokter = (array) $dokterObj;

                            $jadwalRaw = $dokter['jadwal'] ?? '';
                            $jam = explode('-', $jadwalRaw);
                            $jamMulai = isset($jam[0]) && trim($jam[0]) !== '' ? trim($jam[0]) : '00:00';
                            $jamSelesai = isset($jam[1]) && trim($jam[1]) !== '' ? trim($jam[1]) : '00:00';

                            // Pastikan format time (H:i:s)
                            if (strlen($jamMulai) == 5) {
                                $jamMulai .= ':00';
                            }
                            if (strlen($jamSelesai) == 5) {
                                $jamSelesai .= ':00';
                            }

                            $status = (isset($dokter['libur']) && $dokter['libur'] == 1) ? 'LIBUR' : 'AKTIF';
                            $namaHari = !empty($dokter['namahari']) ? strtoupper(trim($dokter['namahari'])) : $hariStr;

                            JadwalPoliCache::updateOrCreate(
                                [
                                    'tanggal' => $tglStr,
                                    'kode_poli' => $kodePoli,
                                    'kode_dokter' => (string) ($dokter['kodedokter'] ?? ''),
                                    'jam_mulai' => $jamMulai,
                                ],
                                [
                                    'hari' => $namaHari,
                                    'nama_poli' => $dokter['namapoli'] ?? $poli->rs2,
                                    'nama_dokter' => $dokter['namadokter'] ?? '',
                                    'jam_selesai' => $jamSelesai,
                                    'kuota' => (int) ($dokter['kapasitaspasien'] ?? 0),
                                    'status' => $status,
                                ]
                            );

                            $totalSynced++;
                        }
                    }

                    // Jeda 0.1 detik antar request agar ramah terhadap server BPJS & terhindar dari rate limit
                    usleep(100000);
                } catch (\Throwable $e) {
                    Log::error("Gagal sync jadwal poli {$kodePoli} tanggal {$tglStr}: " . $e->getMessage());
                    $this->error("Error sync {$kodePoli} tgl {$tglStr}: " . $e->getMessage());
                }
            }
        }

        $this->info("Sinkronisasi selesai. Total {$totalSynced} data jadwal disimpan/diperbarui.");
        Log::info("Finished antrean:sync-jadwal-poli command. Total records: {$totalSynced}");

        return Command::SUCCESS;
    }
}
