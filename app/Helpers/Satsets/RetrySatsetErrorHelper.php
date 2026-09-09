<?php

namespace App\Helpers\Satsets;

use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetrySatsetErrorHelper
{
    /**
     * Deteksi jenis modul kunjungan (rajal, ranap, igd, hd) berdasarkan data riil SIMRS
     *
     * @param string $noreg
     * @return string|null
     */
    public static function detectJenis($noreg)
    {
        $noregStr = trim((string)$noreg);
        if (empty($noregStr)) {
            return null;
        }

        // 1. Cek di tabel rs23 (Rawat Inap)
        $isRanap = DB::table('rs23')->where('rs1', $noregStr)->exists();
        if ($isRanap) {
            return 'ranap';
        }

        // 2. Cek di tabel rs17 (Rawat Jalan / IGD / HD)
        $rajal = DB::table('rs17')->where('rs1', $noregStr)->first(['rs1', 'rs8']);
        if ($rajal) {
            if ($rajal->rs8 === 'PEN005') {
                return 'hd';
            }
            if ($rajal->rs8 === 'POL014' || str_ends_with(strtolower($rajal->rs1), '/x')) {
                return 'igd';
            }
            return 'rajal';
        }

        // 3. Fallback berdasarkan Suffix Noreg
        if (str_ends_with(strtolower($noregStr), '/i')) {
            return 'ranap';
        } elseif (str_ends_with(strtolower($noregStr), '/x')) {
            return 'igd';
        } elseif (str_ends_with(strtolower($noregStr), '/j')) {
            return 'rajal';
        }

        return null;
    }

    /**
     * Sinkronisasi kolom 'jenis' yang masih NULL pada tabel satset_error_respon
     *
     * @param int $limit
     * @return int Jumlah baris yang berhasil diupdate
     */
    public static function syncMissingJenis($limit = 500)
    {
        $records = SatsetErrorRespon::whereNull('jenis')
            ->orWhere('jenis', '')
            ->limit($limit)
            ->get(['id', 'uuid']);

        $updated = 0;
        foreach ($records as $row) {
            $detected = self::detectJenis($row->uuid);
            if ($detected) {
                DB::table('satset_error_respon')
                    ->where('id', $row->id)
                    ->update(['jenis' => $detected]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Kirim ulang satu data error berdasarkan UUID/Noreg
     *
     * @param string $uuid
     * @param string|null $jenis
     * @return array
     */
    public static function retrySingle($uuid, $jenis = null)
    {
        $uuid = trim((string)$uuid);
        if (empty($uuid)) {
            return ['status' => 'failed', 'message' => 'Noreg/UUID kosong'];
        }

        // 1. Cek apakah sudah pernah sukses terkirim di tabel satsets
        $isAlreadySuccess = Satset::where('uuid', $uuid)->exists();
        if ($isAlreadySuccess) {
            // Hapus dari satset_error_respon karena sudah resolved
            SatsetErrorRespon::where('uuid', $uuid)->delete();
            return [
                'status' => 'already_success',
                'uuid' => $uuid,
                'message' => 'Data sudah berhasil terkirim di tabel satsets. Record error dibersihkan.'
            ];
        }

        // 2. Deteksi jenis jika belum ada
        if (!$jenis) {
            $jenis = self::detectJenis($uuid);
        }

        if (!$jenis) {
            // Sentuh updated_at agar pindah ke belakang antrean
            $err = SatsetErrorRespon::where('uuid', $uuid)->first();
            if ($err) {
                $err->touch();
            }
            return [
                'status' => 'failed',
                'uuid' => $uuid,
                'message' => 'Jenis modul tidak dapat dideteksi (bukan format noreg SIMRS valid)'
            ];
        }

        // 3. Eksekusi pengiriman sesuai helper masing-masing modul
        $res = null;
        try {
            switch ($jenis) {
                case 'ranap':
                    $res = PostKunjunganRanapHelper::cobaRanap($uuid);
                    break;
                case 'hd':
                    $res = PostKunjunganHDHerlper::cobarajal($uuid);
                    break;
                case 'igd':
                    $res = PostKunjunganIgdHelper::cobaIgd($uuid);
                    break;
                case 'rajal':
                default:
                    $res = PostKunjunganRajalHelper::cobarajal($uuid);
                    break;
            }
        } catch (\Throwable $e) {
            $res = ['message' => 'failed', 'error' => $e->getMessage()];
        }

        // 4. Evaluasi Hasil Pengiriman
        $isSuccess = isset($res['message']) && $res['message'] === 'success';

        if ($isSuccess) {
            // Bersihkan baris error dari satset_error_respon
            SatsetErrorRespon::where('uuid', $uuid)->delete();
            return [
                'status' => 'success',
                'uuid' => $uuid,
                'jenis' => $jenis,
                'data' => $res
            ];
        } else {
            // Jika masih gagal: Update jenis jika sebelumnya kosong & geser ke antrean belakang (touch)
            $err = SatsetErrorRespon::where('uuid', $uuid)->first();
            if ($err) {
                if (empty($err->jenis) && $jenis) {
                    $err->jenis = $jenis;
                }
                $err->touch(); // Perbarui updated_at agar cron berikutnya mengambil data lain (Anti-Stuck)
            }

            return [
                'status' => 'failed',
                'uuid' => $uuid,
                'jenis' => $jenis,
                'data' => $res
            ];
        }
    }

    /**
     * Proses batch retry untuk antrean data error
     *
     * @param array $options [limit, jenis, from, to, cooldown_hours]
     * @return array Ringkasan hasil eksekusi batch
     */
    public static function retryBatch($options = [])
    {
        $limit = isset($options['limit']) ? (int)$options['limit'] : 10;
        $jenisFilter = $options['jenis'] ?? null;
        $from = $options['from'] ?? null;
        $to = $options['to'] ?? null;
        $cooldownHours = isset($options['cooldown_hours']) ? (int)$options['cooldown_hours'] : 1;

        $query = SatsetErrorRespon::query()
            ->whereNotNull('uuid')
            ->where('uuid', '!=', '');

        // Filter jenis modul jika dispesifikasikan
        if (!empty($jenisFilter)) {
            $query->where('jenis', $jenisFilter);
        }

        // Filter rentang tanggal dibuatnya error (created_at)
        if (!empty($from)) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if (!empty($to)) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        // Cooldown Guard: Jangan coba lagi data yang baru saja gagal dicoba dalam X jam terakhir
        if ($cooldownHours > 0) {
            $cooldownLimit = Carbon::now()->subHours($cooldownHours);
            $query->where('updated_at', '<=', $cooldownLimit);
        }

        // Saring keluar kunjungan yang sebenarnya sudah sukses di tabel satsets (Menggunakan whereNotExists agar safe dari NULL)
        $query->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('satsets')
                ->whereColumn('satsets.uuid', 'satset_error_respon.uuid');
        });

        // Anti-Stuck: Urutkan berdasarkan updated_at ASC (FIFO Queue Berputar)
        $query->orderBy('updated_at', 'ASC');

        $items = $query->limit($limit)->get(['id', 'uuid', 'jenis', 'created_at', 'updated_at']);

        $summary = [
            'total_processed' => count($items),
            'success' => 0,
            'failed' => 0,
            'already_success' => 0,
            'details' => []
        ];

        foreach ($items as $item) {
            $res = self::retrySingle($item->uuid, $item->jenis);
            $status = $res['status'] ?? 'failed';

            if ($status === 'success') {
                $summary['success']++;
            } elseif ($status === 'already_success') {
                $summary['already_success']++;
            } else {
                $summary['failed']++;
            }

            $summary['details'][] = [
                'uuid' => $item->uuid,
                'jenis' => $res['jenis'] ?? $item->jenis,
                'status' => $status,
                'message' => $res['message'] ?? ($res['data']['message'] ?? 'OK')
            ];
        }

        return $summary;
    }
}
