<?php

namespace App\Console\Commands;

use App\Helpers\Satsets\RetrySatsetErrorHelper;
use Illuminate\Console\Command;

class RetrySatsetErrorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'satset:retry-error 
                            {--limit=10 : Jumlah kunjungan yang akan di-retry per eksekusi}
                            {--year= : Filter tahun nomor registrasi (default: tahun berjalan, atau all)}
                            {--jenis= : Filter jenis modul (rajal, ranap, igd, hd)}
                            {--from= : Tanggal awal error (YYYY-MM-DD)}
                            {--to= : Tanggal akhir error (YYYY-MM-DD)}
                            {--cooldown=1 : Jam jeda cooldown untuk data yang baru dicoba dan gagal}
                            {--sync-jenis : Sinkronkan kolom jenis pada satset_error_respon tanpa mengirim}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim ulang kunjungan error SatuSehat secara terkontrol dengan mekanisme Anti-Stuck';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 1. Jika hanya ingin sinkronisasi kolom jenis
        if ($this->option('sync-jenis')) {
            $this->info("Menjalankan sinkronisasi kolom jenis pada satset_error_respon...");
            $updated = RetrySatsetErrorHelper::syncMissingJenis(1000);
            $this->info("Selesai. Berhasil mengupdate {$updated} data jenis error.");
            return Command::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $year = $this->option('year') ?: date('Y'); // Dinamis otomatis mengikuti tahun saat ini (2026, 2027, 2028, dst)
        $jenis = $this->option('jenis');
        $from = $this->option('from');
        $to = $this->option('to');
        $cooldown = (int) $this->option('cooldown');

        $this->info("=== Memulai Retry Kunjungan Error SatuSehat ===");
        $this->info("Tahun Kunjungan: {$year} | Limit: {$limit} | Jenis: " . ($jenis ?: 'Semua') . " | Cooldown: {$cooldown} Jam");

        $result = RetrySatsetErrorHelper::retryBatch([
            'limit' => $limit,
            'year' => $year,
            'jenis' => $jenis,
            'from' => $from,
            'to' => $to,
            'cooldown_hours' => $cooldown,
        ]);

        $this->info("Total Diproses: {$result['total_processed']}");
        $this->info("Sukses: {$result['success']}");
        $this->info("Sudah Pernah Sukses (Dibersihkan): {$result['already_success']}");
        $this->info("Gagal Lagi (Digeser ke Antrean Belakang): {$result['failed']}");

        if (!empty($result['details'])) {
            $tableData = [];
            foreach ($result['details'] as $item) {
                $tableData[] = [
                    $item['uuid'],
                    $item['jenis'] ?? '-',
                    $item['status'],
                    is_string($item['message']) ? substr($item['message'], 0, 50) : json_encode($item['message'])
                ];
            }
            $this->table(['UUID / Noreg', 'Jenis', 'Status', 'Keterangan'], $tableData);
        }

        $this->info("=== Selesai ===");
        return Command::SUCCESS;
    }
}
