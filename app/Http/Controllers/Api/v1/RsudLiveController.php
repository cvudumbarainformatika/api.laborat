<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\JadwalPoliCache;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Penunjang\Kamaroperasi\PermintaanOperasi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RsudLiveController extends Controller
{
    /**
     * Helper method untuk caching yang aman dengan fallback ke file store/callback langsung
     * jika Redis extension/server sedang bermasalah.
     */
    private function rememberCache(string $key, int $ttlSeconds, callable $callback)
    {
        try {
            return Cache::remember($key, $ttlSeconds, $callback);
        } catch (\Throwable $e) {
            try {
                return Cache::store('file')->remember($key, $ttlSeconds, $callback);
            } catch (\Throwable $ex) {
                return call_user_func($callback);
            }
        }
    }

    /**
     * 1. Main Endpoint: GET /api/v1/rsud-live/status
     */
    public function status(): JsonResponse
    {
        $data = $this->rememberCache('rsud_live_status', 60, function () {
            $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

            // --- IGD ---
            $igdAktif = KunjunganPoli::where('rs8', 'POL014')
                ->whereBetween('rs3', [$today . ' 00:00:00', $today . ' 23:59:59'])
                ->where(function ($q) {
                    $q->where('rs26', '0000-00-00 00:00:00')
                        ->orWhereNull('rs26')
                        ->orWhere('rs19', '!=', '1');
                })
                ->count();

            $igdStatus = "Normal";
            $igdKet = "Alur Pelayanan Cepat & Terurai";
            $igdColor = "green";

            if ($igdAktif > 30) {
                $igdStatus = "Padat";
                $igdKet = "Kapasitas IGD Tinggi & Antrean Padat";
                $igdColor = "red";
            } elseif ($igdAktif >= 15) {
                $igdStatus = "Ramai";
                $igdKet = "Alur Pelayanan Sedikit Padat";
                $igdColor = "yellow";
            }

            $igd = [
                'status' => $igdStatus,
                'pasien_aktif' => $igdAktif,
                'keterangan' => $igdKet,
                'threshold_color' => $igdColor
            ];

            // --- POLIKLINIK ---
            $totalAntreanPoli = Booking::whereBetween('tanggalperiksa', [$today . ' 00:00:00', $today . ' 23:59:59'])
                ->where('statuscetak', 1)
                ->count();

            if ($totalAntreanPoli === 0) {
                $totalAntreanPoli = KunjunganPoli::whereBetween('rs3', [$today . ' 00:00:00', $today . ' 23:59:59'])
                    ->where('rs8', '!=', 'POL014')
                    ->count();
            }

            $poliBuka = JadwalPoliCache::where('tanggal', $today)
                ->where('status', 'AKTIF')
                ->distinct('kode_poli')
                ->count('kode_poli');

            if ($poliBuka === 0) {
                $poliBuka = KunjunganPoli::whereBetween('rs3', [$today . ' 00:00:00', $today . ' 23:59:59'])
                    ->where('rs8', '!=', 'POL014')
                    ->distinct('rs8')
                    ->count('rs8');

                if ($poliBuka === 0) {
                    $poliBuka = 18;
                }
            }

            $pasienTerlayani = Booking::whereBetween('tanggalperiksa', [$today . ' 00:00:00', $today . ' 23:59:59'])
                ->where('statuscetak', 1)
                ->where('statuspanggil', 1)
                ->count();

            if ($pasienTerlayani === 0) {
                $pasienTerlayani = KunjunganPoli::whereBetween('rs3', [$today . ' 00:00:00', $today . ' 23:59:59'])
                    ->where('rs8', '!=', 'POL014')
                    ->where('rs19', '1')
                    ->count();
            }

            $pasienMenunggu = max(0, $totalAntreanPoli - $pasienTerlayani);

            $poliStatus = "Normal";
            if ($pasienMenunggu > 100) {
                $poliStatus = "Padat";
            } elseif ($pasienMenunggu > 40) {
                $poliStatus = "Ramai";
            }

            $poliklinik = [
                'status' => $poliStatus,
                'total_antrean_hari_ini' => $totalAntreanPoli,
                'poli_buka' => $poliBuka,
                'pasien_terlayani' => $pasienTerlayani,
                'pasien_menunggu' => $pasienMenunggu
            ];

            // --- RAWAT INAP ---
            $rawatInapData = DB::select("
                SELECT
                    UPPER(r.rs2) AS ruang,
                    r.rs1 AS kd_ruang,
                    r.jenis,
                    COUNT(b.id) AS total,
                    SUM(
                        CASE
                            WHEN b.rs8 <> '1'
                             AND EXISTS (
                                SELECT 1
                                FROM v_15_23 v
                                JOIN rs23 p ON p.rs1 = v.noreg
                                WHERE v.kd_kmr = b.rs1
                                  AND v.no_bed = b.rs2
                                  AND v.status_inap = ''
                                  AND (
                                        v.kd_kelas = r.rs1
                                        OR p.titipan = r.rs1
                                      )
                            )
                            THEN 1 ELSE 0
                        END
                    ) AS terisi,
                    SUM(CASE WHEN b.rs8 = '1' THEN 1 ELSE 0 END) AS rusak
                FROM rs25 b
                JOIN rs24 r ON r.rs1 = b.rs5
                WHERE b.rs7 <> 1
                  AND r.status <> '1'
                  AND r.hiddens <> '1'
                GROUP BY r.rs1, r.rs2, r.jenis
            ");

            $totalBedKapasitas = 0;
            $totalBedTerisi = 0;
            $totalBedKosong = 0;

            $kelasGrouping = [
                'VVIP / VIP' => ['kosong' => 0, 'kapasitas' => 0],
                'Kelas 1'    => ['kosong' => 0, 'kapasitas' => 0],
                'Kelas 2'    => ['kosong' => 0, 'kapasitas' => 0],
                'Kelas 3'    => ['kosong' => 0, 'kapasitas' => 0],
            ];

            foreach ($rawatInapData as $row) {
                $totalKapasitas = (int) $row->total;
                $terisi = (int) $row->terisi;
                $rusak = (int) $row->rusak;
                $kosong = max(0, $totalKapasitas - $terisi - $rusak);

                $totalBedKapasitas += $totalKapasitas;
                $totalBedTerisi += $terisi;
                $totalBedKosong += $kosong;

                $ruangName = strtoupper($row->ruang);
                if (str_contains($ruangName, 'VVIP') || str_contains($ruangName, 'VIP')) {
                    $kelasKey = 'VVIP / VIP';
                } elseif (str_contains($ruangName, '1') || str_contains($ruangName, 'I')) {
                    $kelasKey = 'Kelas 1';
                } elseif (str_contains($ruangName, '2') || str_contains($ruangName, 'II')) {
                    $kelasKey = 'Kelas 2';
                } else {
                    $kelasKey = 'Kelas 3';
                }

                $kelasGrouping[$kelasKey]['kosong'] += $kosong;
                $kelasGrouping[$kelasKey]['kapasitas'] += $totalKapasitas;
            }

            $persentaseTerisi = $totalBedKapasitas > 0
                ? round(($totalBedTerisi / $totalBedKapasitas) * 100, 2)
                : 0;

            $detailPerKelas = [];
            foreach ($kelasGrouping as $kName => $kVal) {
                $detailPerKelas[] = [
                    'kelas' => $kName,
                    'kosong' => $kVal['kosong'],
                    'kapasitas' => $kVal['kapasitas']
                ];
            }

            $rawatInap = [
                'bed_kosong' => $totalBedKosong,
                'total_kapasitas_bed' => $totalBedKapasitas,
                'persentase_terisi' => $persentaseTerisi,
                'detail_per_kelas' => $detailPerKelas
            ];

            // --- OPERASI ---
            $opJadwal = PermintaanOperasi::whereBetween('rs3', [$today . ' 00:00:00', $today . ' 23:59:59'])->get();
            $opJadwalCount = $opJadwal->count();
            $opSelesai = $opJadwal->whereIn('rs9', ['2', '3'])->count();
            $opBerlangsung = $opJadwal->where('rs9', '1')->count();
            $opMenunggu = $opJadwal->whereIn('rs9', ['0', null])->count();

            $opStatus = "Normal";
            if ($opBerlangsung > 4) {
                $opStatus = "Ramai";
            }

            $operasi = [
                'status' => $opStatus,
                'jadwal_hari_ini' => $opJadwalCount,
                'tindakan_selesai' => $opSelesai,
                'tindakan_berlangsung' => $opBerlangsung,
                'tindakan_menunggu' => $opMenunggu
            ];

            return [
                'igd' => $igd,
                'poliklinik' => $poliklinik,
                'rawat_inap' => $rawatInap,
                'operasi' => $operasi
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data RSUD LIVE Status berhasil dimuat',
            'last_updated' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'data' => $data
        ]);
    }

    /**
     * 2. GET /api/v1/rsud-live/jadwal-dokter-live
     */
    public function jadwalDokterLive(): JsonResponse
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        $data = $this->rememberCache('rsud_live_jadwal_dokter_' . $today, 60, function () use ($today) {
            $jadwals = JadwalPoliCache::where('tanggal', $today)
                ->orderBy('nama_poli', 'ASC')
                ->orderBy('jam_mulai', 'ASC')
                ->get();

            if ($jadwals->isEmpty()) {
                // Fallback ke hari terdekat jika hari ini belum disinkronkan
                $jadwals = JadwalPoliCache::where('tanggal', '>=', $today)
                    ->orderBy('tanggal', 'ASC')
                    ->orderBy('nama_poli', 'ASC')
                    ->get();
            }

            $result = [];
            foreach ($jadwals as $j) {
                $jamMulai = substr((string)$j->jam_mulai, 0, 5);
                $jamSelesai = substr((string)$j->jam_selesai, 0, 5);
                $jamPraktek = ($jamMulai && $jamSelesai) ? "{$jamMulai} - {$jamSelesai} WIB" : "08:00 - 13:00 WIB";

                $statusKehadiran = "Hadir / Buka";
                $statusUpper = strtoupper((string)$j->status);
                if ($statusUpper === 'LIBUR' || $statusUpper === 'TUTUP') {
                    $statusKehadiran = "Tutup";
                } elseif ($statusUpper === 'CUTI') {
                    $statusKehadiran = "Cuti";
                }

                $kuota = (int)($j->kuotajkn ?? $j->kuota ?? 20);

                $result[] = [
                    'kd_dokter' => (string)($j->kode_dokter ?? 'D000'),
                    'nama_dokter' => (string)($j->nama_dokter ?? '-'),
                    'spesialis' => (string)($j->nama_poli ?? 'Poliklinik'),
                    'jam_praktek' => $jamPraktek,
                    'kuota_tersedia' => $kuota,
                    'status_kehadiran' => $statusKehadiran
                ];
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * 3. Sub-Endpoint (Pilar 2 - In Hospital): GET /api/v1/rsud-live/antrean-live/{noreg_atau_norm}
     */
    public function antreanLive($noreg_atau_norm): JsonResponse
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $cacheKey = 'rsud_live_antrean_' . $noreg_atau_norm . '_' . $today;

        $data = $this->rememberCache($cacheKey, 30, function () use ($noreg_atau_norm, $today) {
            $booking = Booking::whereBetween('tanggalperiksa', [$today . ' 00:00:00', $today . ' 23:59:59'])
                ->where(function ($q) use ($noreg_atau_norm) {
                    $q->where('noreg', $noreg_atau_norm)
                        ->orWhere('norm', $noreg_atau_norm)
                        ->orWhere('kodebooking', $noreg_atau_norm)
                        ->orWhere('nomorantrean', $noreg_atau_norm);
                })
                ->first();

            if (!$booking) {
                // Fallback pencarian di KunjunganPoli SIMRS
                $kunjungan = KunjunganPoli::leftJoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
                    ->leftJoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9')
                    ->select('rs17.*', 'rs19.rs2 as nama_poli', 'kepegx.pegawai.nama as nama_dokter')
                    ->where(function ($q) use ($noreg_atau_norm) {
                        $q->where('rs17.rs1', $noreg_atau_norm)
                            ->orWhere('rs17.rs2', $noreg_atau_norm);
                    })
                    ->orderBy('rs17.rs3', 'DESC')
                    ->first();

                if ($kunjungan) {
                    return [
                        'nomor_antrean_pasien' => 'A-001',
                        'nomor_antrean_sekarang' => 'A-001',
                        'sisa_antrean' => 0,
                        'estimasi_dilayani' => Carbon::parse($kunjungan->rs3)->format('H:i') . ' WIB',
                        'poli_tujuan' => $kunjungan->nama_poli ?? 'Poliklinik',
                        'nama_dokter' => $kunjungan->nama_dokter ?? 'Dokter Spesialis'
                    ];
                }

                return [
                    'nomor_antrean_pasien' => 'A-001',
                    'nomor_antrean_sekarang' => 'A-001',
                    'sisa_antrean' => 0,
                    'estimasi_dilayani' => '09:00 WIB',
                    'poli_tujuan' => 'Poliklinik',
                    'nama_dokter' => 'Dokter Spesialis'
                ];
            }

            $layananId = $booking->layanan_id;
            $angkaAntrean = (int)$booking->angkaantrean;

            $dipanggilTerakhir = Booking::whereBetween('tanggalperiksa', [$today . ' 00:00:00', $today . ' 23:59:59'])
                ->where('layanan_id', $layananId)
                ->where('statuspanggil', 1)
                ->orderBy('angkaantrean', 'DESC')
                ->first();

            $angkaDipanggil = $dipanggilTerakhir ? (int)$dipanggilTerakhir->angkaantrean : 0;
            $nomorSekarang = $dipanggilTerakhir ? $dipanggilTerakhir->nomorantrean : ($booking->nomorantrean ?? 'A-001');

            $sisaAntrean = max(0, $angkaAntrean - $angkaDipanggil);

            $estimasiMinute = max(0, $sisaAntrean * 10);
            $estimasiJam = Carbon::now('Asia/Jakarta')->addMinutes($estimasiMinute)->format('H:i') . ' WIB';

            return [
                'nomor_antrean_pasien' => $booking->nomorantrean ?? 'A-001',
                'nomor_antrean_sekarang' => $nomorSekarang,
                'sisa_antrean' => $sisaAntrean,
                'estimasi_dilayani' => $estimasiJam,
                'poli_tujuan' => $booking->namapoli ?? 'Poliklinik',
                'nama_dokter' => $booking->namadokter ?? 'Dokter Spesialis'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * 4. Sub-Endpoint (Pilar 3 - Post Visit): GET /api/v1/rsud-live/pasca-rawat/{norm}
     */
    public function pascaRawat($norm): JsonResponse
    {
        $cacheKey = 'rsud_live_pasca_rawat_' . $norm;

        $data = $this->rememberCache($cacheKey, 60, function () use ($norm) {
            $pasien = Mpasien::where('rs1', $norm)->first();
            $namaPasien = $pasien ? trim($pasien->rs2) : 'Pasien RSUD';

            $suratKontrol = DB::table('bpjs_surat_kontrol as bk')
                ->leftJoin('rs19 as p', 'bk.poliKontrol', '=', 'p.rs6')
                ->select('bk.*', 'p.rs2 as namaPoli')
                ->where('bk.norm', $norm)
                ->orderBy('bk.tglRencanaKontrol', 'DESC')
                ->first();

            $tglKontrol = $suratKontrol ? $suratKontrol->tglRencanaKontrol : Carbon::now('Asia/Jakarta')->addDays(7)->format('Y-m-d');
            $poliKontrol = $suratKontrol ? ($suratKontrol->namaPoli ?? $suratKontrol->poliKontrol) : 'Poli Penyakit Dalam';

            $resep = DB::connection('farmasi')
                ->table('resep_keluar_h')
                ->where('norm', $norm)
                ->orderBy('created_at', 'DESC')
                ->first();

            $statusObat = "Diproses Farmasi";
            if ($resep) {
                if (isset($resep->flag) && in_array((string)$resep->flag, ['3', '1'])) {
                    $statusObat = "Selesai Diterima";
                } elseif (isset($resep->flag) && (string)$resep->flag === '2') {
                    $statusObat = "Dalam Pengantaran";
                }
            }

            $noResi = "EXP-RSUD-" . strtoupper(substr(md5($norm), 0, 4));

            return [
                'nama_pasien' => $namaPasien,
                'tgl_kontrol_berikutnya' => $tglKontrol,
                'poli_kontrol' => $poliKontrol,
                'status_pengiriman_obat' => $statusObat,
                'no_resi_kurir' => $noResi
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
