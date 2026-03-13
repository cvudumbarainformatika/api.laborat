<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\JenisPegawai;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\PegawaiTanpaAppendFoto;
use App\Models\Pegawai\Prota;
use App\Models\Pegawai\Ruangan;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class TransaksiAbsenController extends Controller
{
    //

    public function rekap()
    {
        $thisYear = request('tahun') ?: date('Y');
        $thisMonth = request('bulan') ?: date('m');
        $per_page = request('per_page') ?: 10;
        $user = User::where('id', '>', 1)
            ->filter(request(['q']))
            ->oldest('id')
            ->with(['absens' => function ($query) use ($thisMonth, $thisYear) {
                $query->whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
                    ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31');
            }])
            // ->simplePaginate($per_page);
            ->paginate($per_page);
        $userCollections = collect($user);

        $dataUser = $userCollections->only('data');
        $dataUser->all();
        $meta = $userCollections->except('data');
        $meta->all();
        $data = [];
        foreach ($user as $key) {
            $absen = $key->absens;
            foreach ($absen as $value) {
                // return new JsonResponse($value);
                // if ($value['masuk'] === null || $value['masuk'] === '') {
                //     return new JsonResponse($value);
                // }
                if ($value['masuk'] !== null) {
                    $temp = explode('-', $value['tanggal']);
                    $day = $temp[2];
                    $value['day'] = $day;
                    if ($value['kategory']) {
                        $value->setRelation('kategory', clone $value['kategory']);
                        $value['kategory']->setReferenceDate($value['tanggal']); // Option A
                    }
                    $toIn = explode(':', $value['kategory']->masuk);
                    $act = explode(':', $value['masuk']);
                    $jam = (int)$act[0] - (int)$toIn[0];
                    $menit =  (int)$act[1] - (int)$toIn[1];
                    $detik =  (int)$act[2] - (int)$toIn[2];

                    if ($jam > 0 || $menit > 00) {
                        $value['terlambat'] = 'yes';
                    } else {
                        $value['terlambat'] = 'no';
                    }
                    $dMenit = $menit >= 10 ? $menit : '0' . $menit;
                    $dDetik = $detik >= 10 ? $detik : '0' . $detik;
                    $diff = $jam . ':' . $dMenit . ':' . $dDetik;
                    $value['diff'] = $diff;
                }
            }

            $data[$key['id']] = $absen;
        }
        // return new JsonResponse($data);

        $apem = [];
        foreach ($data as $key => $value) {
            // return new JsonResponse($value);
            $telat = $value->where('terlambat', 'yes')->count();
            $total = $value->where('terlambat')->count();
            $userapem = null;
            foreach ($value as $ni) {
                $userapem = $ni->user_id;
            }
            // $userapem->all();
            // $userapem->only('user_id');
            // $key['value'] = $key;
            array_push($apem, ['total' => $total, 'telat' => $telat, 'user_id' => $userapem]);
        }
        $data['apem'] = $apem;
        $data['meta'] = $meta;
        $data['user'] = $dataUser;
        return new JsonResponse($data);
    }

    public function rekap2()
    {
        $thisYear = request('tahun') ?: date('Y');
        $thisMonth = request('bulan') ?: date('m');
        $per_page = request('per_page') ?: 10;

        $startDate = "$thisYear-$thisMonth-01";
        // Menggunakan library Carbon untuk endOfMonth akan lebih akurat,
        // tapi mengikuti logic lama (hardcode 31) agar konsisten kalau tgl 31 tidak valid di bulan tertentu (mysql handle / php handle).
        // Code lama: whereDate <= ...-31.
        $endDate = "$thisYear-$thisMonth-31";

        $users = User::where('id', '>', 1)
            ->filter(request(['q']))
            ->oldest('id')
            ->with(['absens' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal', [$startDate, $endDate])
                    ->with('kategory'); // Eager load kategory untuk menghindari N+1 problem
            }])
            ->paginate($per_page);

        $data = [];
        $apem = [];

        foreach ($users as $user) {
            // Mengakses relation yang sudah di-eager load
            $absens = $user->absens;

            $telatCount = 0;
            $totalCount = 0;
            $userIdForApem = null; // Default null jika tidak ada absen

            // Mengambil user_id dari user object langsung lebih aman daripada loop absen
            // Tapi logic lama mengambil dari iteration absen (ni->user_id).
            // Jika absen kosong, userapem null. Kita ikut logic itu.
            if ($absens->isNotEmpty()) {
                $userIdForApem = $absens->first()->user_id;
            }

            foreach ($absens as $absen) {
                if ($absen->masuk !== null) {
                    // Logic Parse Tanggal & Day (Optimized)
                    // Format YYYY-MM-DD, ambil 2 karakter terakhir
                    $absen->day = substr($absen->tanggal, -2);

                    // Logic Diff & Terlambat
                    // Pastikan kategory ada
                    if ($absen->kategory) {
                        $absen->setRelation('kategory', clone $absen->kategory);
                        $absen->kategory->setReferenceDate($absen->tanggal); // Option A
                        $toIn = explode(':', $absen->kategory->masuk);
                        $act = explode(':', $absen->masuk);

                        $jam = (int)$act[0] - (int)$toIn[0];
                        $menit = (int)$act[1] - (int)$toIn[1];
                        $detik = (int)$act[2] - (int)$toIn[2];

                        // Logic lama: ($jam > 0 || $menit > 00)
                        $isTerlambat = ($jam > 0 || $menit > 0);

                        $absen->terlambat = $isTerlambat ? 'yes' : 'no';

                        $dMenit = $menit >= 10 ? $menit : '0' . $menit;
                        $dDetik = $detik >= 10 ? $detik : '0' . $detik;
                        $absen->diff = $jam . ':' . $dMenit . ':' . $dDetik;

                        // Hitung rekap on-the-fly
                        $totalCount++;
                        if ($isTerlambat) {
                            $telatCount++;
                        }
                    }
                }
            }

            $data[$user->id] = $absens;

            // Masukkan ke array apem
            $apem[] = [
                'total' => $totalCount,
                'telat' => $telatCount,
                'user_id' => $userIdForApem
            ];
        }

        // Menyusun Response agar SAMA PERSIS dengan rekap()

        // 1. Meta Pagination
        $paginationArray = $users->toArray();
        $meta = collect($users)->except('data'); // Menggunakan cara lama agar output struktur object-nya mirip

        // 2. User Data
        // Logic lama: $userCollections->only('data') -> returns ['data' => [...]]
        $dataUser = ['data' => $paginationArray['data']];

        $finalData = $data;
        $finalData['apem'] = $apem;
        $finalData['meta'] = $meta;
        $finalData['user'] = $dataUser;

        return new JsonResponse($finalData);
    }

    public function dashboard(Request $request)
    {
        // Mendapatkan tanggal hari ini
        $today = Carbon::today();
        $todayString = $today->toDateString();

        // Menghitung ringkasan
        // Menggunakan Pegawai::where('nama', '!=', 'Programmer')->where('aktif', 'AKTIF')->count(); sebagai total pegawai sesuai implementasi terakhir
        $totalPegawai = Pegawai::where('nama', '!=', 'Programmer')->where('aktif', 'AKTIF')->count();

        // Cuti: Hanya menghitung 'CUTI' untuk HARI INI
        $cutiCount = Libur::where('flag', 'CUTI')
            ->whereDate('tanggal', $todayString)
            ->distinct('user_id')
            ->count();

        // Asumsi: Aktif adalah total pegawai dikurangi yang cuti HARI INI.
        $aktifCount = $totalPegawai - $cutiCount;

        // Mendapatkan data absensi untuk HARI INI
        $absensiHariIni = TransaksiAbsen::whereDate('tanggal', $todayString)
            ->with('kategory')
            ->get();

        $hadirTepatWaktuCount = 0;
        $terlambatCount = 0;

        foreach ($absensiHariIni as $absen) {
            if ($absen->masuk !== null && $absen->kategory) {
                $absen->setRelation('kategory', clone $absen->kategory);
                $absen->kategory->setReferenceDate($todayString); // Option A
                $toIn = explode(':', $absen->kategory->masuk);
                $act = explode(':', $absen->masuk);

                $jam = (int)$act[0] - (int)$toIn[0];
                $menit = (int)$act[1] - (int)$toIn[1];

                if ($jam > 0 || $menit > 0) { // Logic terlambat
                    $terlambatCount++;
                } else {
                    $hadirTepatWaktuCount++;
                }
            }
        }

        // Alpha: Menghitung alpha untuk HARI INI
        $alphaCount = Alpha::whereDate('tanggal', $todayString)
            ->distinct('pegawai_id')
            ->count();

        // Data summary
        $summary = [
            "total_pegawai" => number_format($totalPegawai, 0, ',', '.'),
            "aktif" => $aktifCount,
            "cuti" => $cutiCount
        ];

        // Data admin_stats
        $adminStats = [
            [
                "label" => "Hadir Tepat Waktu",
                "value" => (string)$hadirTepatWaktuCount,
                "icon" => "icon-mat-check_circle",
                "color" => "green",
                "trendIcon" => "icon-mat-trending_up",
                // Perbaikan: Tambahkan pengecekan $totalPegawai > 0
                "trendText" => ($totalPegawai > 0) ? round(($hadirTepatWaktuCount / $totalPegawai) * 100) . "% Hadir" : "0% Hadir",
                "trendColor" => "text-green"
            ],
            [
                "label" => "Terlambat",
                "value" => (string)$terlambatCount,
                "icon" => "icon-mat-history",
                "color" => "orange",
                "trendIcon" => "icon-mat-trending_down",
                // Perbaikan: Tambahkan pengecekan $totalPegawai > 0
                "trendText" => ($totalPegawai > 0) ? round(($terlambatCount / $totalPegawai) * 100) . "% Terlambat" : "0% Terlambat",
                "trendColor" => "text-orange"
            ],
            [
                "label" => "Cuti / Libur",
                "value" => (string)$cutiCount,
                "icon" => "icon-mat-beach_access",
                "color" => "blue",
                "trendIcon" => "icon-mat-remove",
                "trendText" => "Sesuai Prota", // Placeholder, bisa disesuaikan
                "trendColor" => "text-grey-7"
            ],
            [
                "label" => "Tanpa Keterangan",
                "value" => (string)$alphaCount,
                "icon" => "icon-mat-warning",
                "color" => "negative",
                "trendIcon" => "icon-mat-trending_up",
                "trendText" => "Perlu Follow Up", // Placeholder, bisa disesuaikan
                "trendColor" => "text-red"
            ]
        ];

        // START: Implementasi weekly_trend secara dinamis dan optimal
        $weeklyData = [];
        $weeklyCategories = [];
        // $today didefinisikan di awal metode

        $startDateOfWeek = $today->copy()->subDays(6)->startOfDay();
        $endDateOfWeek = $today->copy()->endOfDay();

        // 1. Ambil semua ID pegawai yang relevan hanya sekali
        $relevantUserIds = User::where('id', '>', 1)->pluck('id');
        $totalRelevantUsers = $relevantUserIds->count();

        // 2. Ambil semua data Alpha untuk rentang 7 hari terakhir
        $allAlphas = Alpha::whereBetween('tanggal', [$startDateOfWeek, $endDateOfWeek])
            ->get()
            ->groupBy('tanggal'); // Group by date for easy access

        // 3. Ambil semua data Libur untuk rentang 7 hari terakhir
        $allLeaves = Libur::whereBetween('tanggal', [$startDateOfWeek, $endDateOfWeek])
            ->get()
            ->groupBy('tanggal'); // Group by date for easy access

        // 4. Ambil semua data TransaksiAbsen untuk rentang 7 hari terakhir
        $allAttendances = TransaksiAbsen::whereBetween('tanggal', [$startDateOfWeek, $endDateOfWeek])
            ->with('kategory') // Load kategory relation once
            ->get()
            ->groupBy('tanggal'); // Group by date for easy access

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dateString = $date->toDateString();
            $dayName = $date->shortDayName;

            // Dapatkan ID pegawai yang alpha pada tanggal ini
            $alphaUsersToday = $allAlphas->has($dateString)
                ? $allAlphas[$dateString]->pluck('user_id')->unique()
                : collect();

            // Dapatkan ID pegawai yang libur pada tanggal ini
            $leaveUsersToday = $allLeaves->has($dateString)
                ? $allLeaves[$dateString]->pluck('user_id')->unique()
                : collect();

            // Pegawai yang tidak hadir (alpha atau libur)
            $notAttendingUsers = $alphaUsersToday->merge($leaveUsersToday)->unique();

            // Pegawai yang diharapkan hadir (total - yang tidak hadir)
            $expectedToAttendCount = $totalRelevantUsers - $notAttendingUsers->count();

            // Hitung absensi yang valid (hadir) pada hari ini
            $actualAttendedCount = 0;
            if ($allAttendances->has($dateString)) {
                $dailyAttendances = $allAttendances[$dateString];
                // Filter hanya absensi dari user_id yang relevan
                $actualAttendedCount = $dailyAttendances->whereIn('user_id', $relevantUserIds)->count();
            }

            // Persentase kehadiran
            // Perbaikan: Tambahkan pengecekan $expectedToAttendCount > 0
            $attendancePercentage = ($expectedToAttendCount > 0) ? round(($actualAttendedCount / $expectedToAttendCount) * 100) : 0;

            $weeklyData[] = $attendancePercentage;
            $weeklyCategories[] = $dayName;
        }

        $weeklyTrend = [
            "series" => [
                ["name" => "Persentase Kehadiran", "data" => $weeklyData]
            ],
            "categories" => $weeklyCategories
        ];
        // END: Implementasi weekly_trend secara dinamis dan optimal

        // Data absence_composition
        // Menghitung Izin/Sakit dari flag DISPEN, DL, SAKIT, IJIN untuk HARI INI
        $izinSakitCount = Libur::whereIn('flag', ['DISPEN', 'DL', 'SAKIT', 'IJIN'])
            ->whereDate('tanggal', $todayString)
            ->distinct('user_id')
            ->count();

        $absenceComposition = [
            "series" => [$hadirTepatWaktuCount, $terlambatCount, $izinSakitCount, $alphaCount],
            "labels" => ["Hadir", "Terlambat", "Izin/Sakit", "Alpha"]
        ];

        $responseData = [
            "status" => "success",
            "data" => [
                "summary" => $summary,
                "admin_stats" => $adminStats,
                "weekly_trend" => $weeklyTrend,
                "absence_composition" => $absenceComposition
            ]
        ];

        return new JsonResponse($responseData);
    }

    public function reportV2(Request $request): JsonResponse
    {
        // 1. Dapatkan parameter request
        $periodeParam = $request->input('periode', Carbon::now()->format('Y-m'));
        $q = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $flag = $request->input('flag');
        $ruang = $request->input('ruang');

        // 2. Validasi periode
        if (!preg_match('/^\d{4}-\d{2}$/', $periodeParam)) {
            return new JsonResponse(['message' => 'Format periode tidak valid. Gunakan YYYY-MM.'], 400);
        }

        // 3. Definisikan tanggal mulai dan akhir periode
        $startOfMonth = Carbon::createFromFormat('Y-m', $periodeParam)->startOfMonth();
        $endOfMonth = Carbon::createFromFormat('Y-m', $periodeParam)->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        // Ambil data Libur Nasional / Cuti Bersama (Prota)
        $protaDates = Prota::whereBetween('tgl_libur', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->pluck('tgl_libur')
            ->toArray();

        // 4. Query Utama Pegawai
        $pegawaiQuery = Pegawai::select([
            'id',
            'nip',
            'nik',
            'nama',
            'kelamin',
            'foto', // Ini akan menjadi foto_pegawai
            'ttdpegawai', // Penting untuk accessor ttdpegawai_url
            'alamat', // Perlu digabungkan untuk alamat_detil, kelurahan, kecamatan, kota
            'flag', // flag pegawai (P01, P02, dll)
            'kdpegsimrs', // Digunakan untuk relasi user
            'aktif', // Pastikan hanya pegawai aktif
            'jenispegawai', // Raw column, untuk relasi jenis_pegawai
            'jabatan', // Raw column & foreign key, untuk relasi relasi_jabatan
            'ruang' // Raw column & foreign key, untuk relasi ruangan
        ])
            ->where('aktif', '=', 'AKTIF')
            ->when($q, function ($query, $q) {
                return $query->where('nama', 'like', '%' . $q . '%')
                    ->orWhere('nip', 'like', '%' . $q . '%');
            })
            ->when($flag, function ($query, $flag) {
                return $query->where('flag', $flag);
            })
            ->when($ruang, function ($query, $ruang) {
                return $query->where('ruang', $ruang);
            })
            ->with([
                'jenis_pegawai' => function ($query) {
                    $query->select('id', 'keterangan', 'kode_jenis', 'jenispegawai');
                },
                'relasi_jabatan' => function ($query) {
                    $query->select('id', 'kode_jabatan', 'jabatan');
                },
                'ruangan' => function ($query) {
                    $query->select('id', 'koderuangan', 'namaruang');
                },
                'user' => function ($query) {
                    $query->select('id', 'pegawai_id', 'email');
                },
            ]);

        $paginatedPegawai = $pegawaiQuery->paginate($perPage, ['*'], 'page', $page);

        // Ambil ID untuk pre-load
        $paginatedPegawaiIds = $paginatedPegawai->pluck('id')->toArray();
        $paginatedUserIds = $paginatedPegawai->whereNotNull('user')->map(fn($p) => $p->user->id)->unique()->toArray();


        // 5. Pre-load semua data TransaksiAbsen, Libur, dan Alpha
        $allAttendances = TransaksiAbsen::whereIn('pegawai_id', $paginatedPegawaiIds)
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with(['kategory'])
            ->get()
            ->groupBy('pegawai_id');

        $allLeaves = Libur::whereIn('user_id', $paginatedUserIds)
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->groupBy('user_id');

        $allAlphas = Alpha::whereIn('pegawai_id', $paginatedPegawaiIds)
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->groupBy('pegawai_id');


        // 6. Loop melalui setiap pegawai untuk agregasi summary dan formatting
        $formattedData = $paginatedPegawai->map(function ($pegawai) use ($allAttendances, $allLeaves, $allAlphas, $startOfMonth, $endOfMonth, $daysInMonth, $protaDates) {
            $summary = [
                "ijin" => 0,
                "sakit" => 0,
                "dl" => 0,
                "dispen" => 0,
                "cuti" => 0,
                "alpha" => 0,
                "terlambat_hari" => 0,
                "jam_masuk" => "0j 0m",
                "hari_masuk" => 0,
                "presentase" => 0,
                "terlambat_menit" => "0 jam 0 mnt",
                "tap" => 0,
                "potongan_persen" => 0
            ];

            $pegawaiAttendances = $allAttendances->get($pegawai->id, collect());
            $pegawaiLeaves = $pegawai->user ? $allLeaves->get($pegawai->user->id, collect()) : collect();
            $pegawaiAlphas = $allAlphas->get($pegawai->id, collect());

            // A. Perhitungan Ijin/Libur (Unique Days per Flag)
            $summary['ijin'] = $pegawaiLeaves->where('flag', 'IJIN')->pluck('tanggal')->unique()->count();
            $summary['sakit'] = $pegawaiLeaves->where('flag', 'SAKIT')->pluck('tanggal')->unique()->count();
            $summary['dl'] = $pegawaiLeaves->where('flag', 'DL')->pluck('tanggal')->unique()->count();
            $summary['dispen'] = $pegawaiLeaves->where('flag', 'DISPEN')->pluck('tanggal')->unique()->count();
            $summary['cuti'] = $pegawaiLeaves->where('flag', 'CUTI')->pluck('tanggal')->unique()->count();

            // Total ijin semua flag untuk rumus presentase
            $totalIjinSemuaFlag = $pegawaiLeaves->pluck('tanggal')->unique()->count();

            // B. Perhitungan Alpha
            $totalAlpha = 0;
            $totalHariStatusL = 0;

            // Cari tahu apakah pegawai ini shift normal (1 atau 2)
            $hasShiftKategory = $pegawaiAttendances->pluck('kategory_id')->unique();
            $isShiftNormal = $hasShiftKategory->isEmpty() || $hasShiftKategory->every(fn($id) => in_array($id, [1, 2]));

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $currentDate = $startOfMonth->copy()->addDays($d - 1);
                $dateStr = $currentDate->toDateString();

                $hasAbsen = $pegawaiAttendances->firstWhere('tanggal', $dateStr);
                $hasIjin = $pegawaiLeaves->firstWhere('tanggal', $dateStr);
                $hasAlphaRecord = $pegawaiAlphas->firstWhere('tanggal', $dateStr);

                $isWeekend = $currentDate->isWeekend();
                $isProta = in_array($dateStr, $protaDates);
                $isPublicHoliday = ($isWeekend || $isProta);

                // Logika Alpha sesuai Requirement
                if ($hasAbsen && !$hasIjin) {
                    // Hadir
                } elseif ($hasIjin) {
                    // Ijin
                } elseif ($isPublicHoliday && $isShiftNormal) {
                    $totalHariStatusL++;
                } elseif ($hasAlphaRecord) {
                    $totalAlpha++;
                } else {
                    $totalHariStatusL++;
                }
            }
            $summary['alpha'] = $totalAlpha;

            // C, D, F, G. Perhitungan Jam Masuk, Hari Masuk, Keterlambatan, TAP
            $totalMinutesLate = 0;
            $totalMinutesWorking = 0;
            $daysPresent = $pegawaiAttendances->count();
            $daysLate = 0;
            $tapCount = 0;

            foreach ($pegawaiAttendances as $attendance) {
                if ($attendance->kategory) {
                    $attendance->setRelation('kategory', clone $attendance->kategory);
                    $attendance->kategory->setReferenceDate($attendance->tanggal);
                }
                // Keterlambatan (F)
                if ($attendance->masuk && $attendance->kategory && $attendance->kategory->masuk) {
                    $scheduledInStr = $attendance->kategory->masuk;
                    $actualInTime = Carbon::parse($attendance->created_at)->subMinute();
                    $limitInTime = Carbon::parse($attendance->tanggal . ' ' . $scheduledInStr);

                    if ($actualInTime->greaterThan($limitInTime)) {
                        $lateMin = $actualInTime->diffInMinutes($limitInTime);
                        $totalMinutesLate += $lateMin;
                        $daysLate++;
                    }
                }

                // Jam Masuk (C)
                if ($attendance->pulang) {
                    $catMasuk = $attendance->kategory->masuk ?? "00:00:00";
                    $catPulang = $attendance->kategory->pulang ?? "00:00:00";

                    $actualIn = Carbon::parse($attendance->created_at);
                    $actualOut = Carbon::parse($attendance->updated_at);

                    $limitIn = Carbon::parse($attendance->tanggal . ' ' . $catMasuk);
                    $limitOut = Carbon::parse($attendance->tanggal . ' ' . $catPulang);

                    $effectiveIn = $actualIn->greaterThan($limitIn) ? $actualIn : $limitIn;
                    $effectiveOut = $actualOut->lessThan($limitOut) ? $actualOut : $limitOut;

                    if ($effectiveOut->greaterThan($effectiveIn)) {
                        $totalMinutesWorking += $effectiveOut->diffInMinutes($effectiveIn);
                    }
                } else {
                    // Cek apakah ada ijin/dispen di hari ini
                    $hasIjinOnThisDay = $pegawaiLeaves->firstWhere('tanggal', $attendance->tanggal);
                    if (!$hasIjinOnThisDay) {
                        $tapCount++;
                    }
                }
            }

            $summary['hari_masuk'] = $daysPresent;
            $summary['terlambat_hari'] = $daysLate;
            $summary['tap'] = $tapCount;

            $hoursLate = floor($totalMinutesLate / 60);
            $minsLate = $totalMinutesLate % 60;

            $lateStr = "";
            if ($hoursLate > 0) {
                $lateStr .= "{$hoursLate} jam ";
            }
            if ($minsLate > 0) {
                $lateStr .= "{$minsLate} mnt";
            }
            $summary['terlambat_menit'] = trim($lateStr);

            $workHours = floor($totalMinutesWorking / 60);
            $workMins = $totalMinutesWorking % 60;

            $workStr = "";
            if ($workHours > 0) {
                $workStr .= "{$workHours}j ";
            }
            if ($workMins > 0 || $workHours == 0) {
                $workStr .= "{$workMins}m";
            }
            $summary['jam_masuk'] = trim($workStr);

            // E. Perhitungan Presentase
            $rumusTerkecil = ($daysInMonth - $totalIjinSemuaFlag - $totalHariStatusL) * 7.5;
            $totalHoursWorking = $totalMinutesWorking / 60;
            $presentase = $rumusTerkecil > 0 ? ($totalHoursWorking / $rumusTerkecil) : 0;
            $summary['presentase'] = round($presentase, 2);

            // H. Potongan Persen (Berdasarkan Total Menit Keterlambatan)
            if ($totalMinutesLate == 0) {
                $summary['potongan_persen'] = 0;
            } elseif ($totalMinutesLate <= 60) {
                $summary['potongan_persen'] = 5;
            } elseif ($totalMinutesLate <= 120) {
                $summary['potongan_persen'] = 10;
            } else {
                $summary['potongan_persen'] = 20;
            }

            // Gabungkan data sesuai struktur 'lama'
            $pegawaiData = $pegawai->toArray();

            // Tambahkan/Update fields
            $pegawaiData['summary'] = $summary;
            $pegawaiData['transaksi_absen'] = $pegawaiAttendances->values()->toArray();
            $pegawaiData['alpha'] = $pegawaiAlphas->values()->toArray();

            // Ensure user libur is consistent
            if (isset($pegawaiData['user'])) {
                $pegawaiData['user']['libur'] = $pegawaiLeaves->values()->toArray();
            }

            // Pruning Null foto_pegawai if needed, but accessor will handle it
            return $pegawaiData;
        });

        $paginationMeta = [
            'total' => $paginatedPegawai->total(),
            'per_page' => $paginatedPegawai->perPage(),
            'current_page' => $paginatedPegawai->currentPage(),
            'last_page' => $paginatedPegawai->lastPage(),
            'from' => $paginatedPegawai->firstItem(),
            'to' => $paginatedPegawai->lastItem(),
        ];

        return new JsonResponse([
            "status" => "success",
            "data" => $formattedData,
            "meta" => $paginationMeta
        ]);
    }

    public function index()
    {
        $thisYear = request('tahun') ?: date('Y');
        $thisMonth = request('bulan') ?: date('m');
        $per_page = request('per_page') ?: 10;
        $user = User::where('id', '>', 3)->oldest('id')->filter(request(['q']))->paginate($per_page);
        $userCollections = collect($user);
        $meta = $userCollections->except('data');
        $meta->all();
        $users = $userCollections->only('data');
        $users->all();
        // $temp = [
        //     'data' => $users,
        //     'meta' => $meta,
        // ];
        // return new JsonResponse($temp);
        $data = [];
        foreach ($users['data'] as $key) {
            // return new JsonResponse($key);
            $absen = TransaksiAbsen::whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
                ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31')
                ->where('user_id', $key['id'])
                ->with('user', 'kategory')
                ->get();
            // return new JsonResponse($absen);
            $tanggals = [];
            foreach ($absen as $value) {
                if ($value['kategory']) {
                    $value->setRelation('kategory', clone $value['kategory']);
                    $value['kategory']->setReferenceDate($value['tanggal']);
                }
                // return new JsonResponse($value);
                $temp = explode('-', $value['tanggal']);
                // $temp = explode('-', $value->tanggal);
                $day = $temp[2];
                // $day = $this->getDayName($temp[2]);
                $value['day'] = $day;

                $toIn = explode(':', $value['kategory']->masuk);
                $act = explode(':', $value['masuk']);
                $jam = (int)$act[0] - (int)$toIn[0];
                $menit =  (int)$act[1] - (int)$toIn[1];
                $detik =  (int)$act[2] - (int)$toIn[2];

                if ($jam > 0 || $menit > 40) {
                    $value['terlambat'] = 'yes';
                } else {
                    $value['terlambat'] = 'no';
                }
                $dMenit = $menit >= 10 ? $menit : '0' . $menit;
                $dDetik = $detik >= 10 ? $detik : '0' . $detik;
                $diff = $jam . ':' . $dMenit . ':' . $dDetik;
                $value['diff'] = $diff;
            }

            $data[$key['id']] = $absen;
        }
        // return new JsonResponse($data);
        // $tanggals = [];
        // foreach ($data as $key) {
        //     return new JsonResponse($key);
        //     $temp = explode('-', $key['tanggal']);
        //     // $temp = explode('-', $key->tanggal);
        //     $day = $temp[2];
        //     // $day = $this->getDayName($temp[2]);
        // $key['day'] = $day;

        //     $toIn = explode(':', $key['kategory']->masuk);
        //     $act = explode(':', $key['masuk']);
        //     $jam = (int)$act[0] - (int)$toIn[0];
        //     $menit =  (int)$act[1] - (int)$toIn[1];
        //     $detik =  (int)$act[2] - (int)$toIn[2];

        //     if ($jam > 0 || $menit > 40) {
        //         $key['terlambat'] = 'yes';
        //     } else {
        //         $key['terlambat'] = 'no';
        //     }
        //     $dMenit = $menit >= 10 ? $menit : '0' . $menit;
        //     $dDetik = $detik >= 10 ? $detik : '0' . $detik;
        //     $diff = $jam . ':' . $dMenit . ':' . $dDetik;
        //     $value['diff'] = $diff;

        // }

        // $collects = collect($data);
        // $userGroup = $collects->groupBy('user_id');
        $apem = [];
        foreach ($data as $key => $value) {
            // return new JsonResponse($value);
            $telat = $value->where('terlambat', 'yes')->count();
            $total = $value->where('terlambat')->count();
            $userapem = null;
            foreach ($value as $ni) {
                $userapem = $ni->user_id;
            }
            // $userapem->all();
            // $userapem->only('user_id');
            // $key['value'] = $key;
            array_push($apem, ['total' => $total, 'telat' => $telat, 'user_id' => $userapem]);
        }
        $data['apem'] = $apem;
        // foreach ($apem as &$key) {
        //     array_push($data[$key['user_id']], $key);
        // }




        return new JsonResponse($data, 200);
        // return new JsonResponse([
        //     'data' => $userGroup,
        //     'telat' => $telat,
        // ], 200);
    }

    public function getAbsenToday()
    {
        $user = JWTAuth::user();
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '=', date('Y-m-d'))
            ->first();
        if (!$data) {

            return new JsonResponse(['message' => 'tidak ada data'], 500);
        }

        return new JsonResponse($data, 200);
    }

    public function getRekapByUser()
    {
        $user = JWTAuth::user();
        $thisYear = request('tahun') ?: date('Y');
        $thisMonth = request('bulan') ?: date('m');
        $per_page = request('per_page') ?: 10;
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31')
            ->with('kategory')
            ->latest()
            ->get();

        $data->each(function ($item) {
            if ($item->kategory) {
                $item->setRelation('kategory', clone $item->kategory);
                $item->kategory->setReferenceDate($item->tanggal);
            }
        });

        return new JsonResponse($data);
    }
    public function getRekapByUserLibur()
    {
        $user = JWTAuth::user();
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $thisMonth = request('bulan') ? request('bulan') : date('m');
        $per_page = request('per_page') ? request('per_page') : 10;
        $masuk = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31')
            ->with('kategory')
            ->latest()
            ->get();

        $masuk->each(function ($item) {
            if ($item->kategory) {
                $item->setRelation('kategory', clone $item->kategory);
                $item->kategory->setReferenceDate($item->tanggal);
            }
        });


        $data['masuk'] = $masuk;
        $libur = Libur::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31')
            ->latest()
            ->get();

        $data['libur'] = $libur;
        return new JsonResponse($data);
    }


    public function getRekapPerUser()
    {
        $user = User::find(request('id'));
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $thisMonth = request('bulan') ? request('bulan') : date('m');
        $from = $thisYear . '-' . $thisMonth . '-01';
        $to = $thisYear . '-' . $thisMonth . '-31';
        // $per_page = request('per_page') ? request('per_page') : 10;
        $prota = Prota::where('tgl_libur', '>=', $from)
            ->where('tgl_libur', '<=', $to)
            ->get();
        $libur = Libur::where('tanggal', '>=', $from)
            ->where('tanggal', '<=', $to)
            ->where('user_id', $user->id)
            ->with('user')
            ->get();
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $from)
            ->whereDate('tanggal', '<=', $to)
            ->orderBy(request('order_by'), request('sort'))
            ->with('kategory')
            ->get();
        $tanggals = [];
        foreach ($data as $key) {
            $temp = date('Y/m/d', strtotime($key['tanggal']));
            $week = date('W', strtotime($key['tanggal']));
            if ($key['kategory']) {
                $key->setRelation('kategory', clone $key['kategory']);
                $key['kategory']->setReferenceDate($key['tanggal']); // Option A
            }
            $toIn = explode(':', $key['kategory']->masuk);
            $act = explode(':', $key['masuk']);
            $jam = (int)$act[0] - (int)$toIn[0];
            $menit =  (int)$act[1] - (int)$toIn[1];
            $detik =  (int)$act[2] - (int)$toIn[2];

            if ($jam > 0 || $menit > 10) {
                $key['terlambat'] = 'yes';
            } else {
                $key['terlambat'] = 'no';
            }
            $dMenit = $menit >= 10 ? $menit : '0' . $menit;
            $dDetik = $detik >= 10 ? $detik : '0' . $detik;
            $diff = $jam . ':' . $dMenit . ':' . $dDetik;
            $key['diff'] = $diff;
            $key['week'] = $week;
            array_push($tanggals, $temp);
        };
        $collects = collect($data);
        $grouped = $collects->groupBy('week');
        $telat = $collects->where('terlambat', 'yes')->count();
        return new JsonResponse([
            'libur' => $libur,
            'prota' => $prota,
            'telat' => $telat,
            'weeks' => $grouped,
            'tanggals' => $tanggals,
            'data' => $data,
        ], 200);
    }

    public function getDayName($day)
    {
        $temp = '';
        switch ($day) {
            case '01':
                $temp = 'satu';
                break;
            case '02':
                $temp = 'dua';
                break;
            case '03':
                $temp = 'tiga';
                break;
            case '04':
                $temp = 'empat';
                break;
            case '05':
                $temp = 'lima';
                break;
            case '06':
                $temp = 'enam';
                break;
            case '07':
                $temp = 'tujuh';
                break;
            case '08':
                $temp = 'delapan';
                break;
            case '09':
                $temp = 'sembilan';
                break;
            case '10':
                $temp = 'sepuluh';
                break;
            case '11':
                $temp = 'sebelas';
                break;
            case '12':
                $temp = 'duabelas';
                break;
            case '13':
                $temp = 'tigabelas';
                break;
            case '14':
                $temp = 'empatbelas';
                break;
            case '15':
                $temp = 'limabelas';
                break;
            case '16':
                $temp = 'enambelas';
                break;
            case '17':
                $temp = 'tujuhbelas';
                break;
            case '18':
                $temp = 'delapanbelas';
                break;
            case '19':
                $temp = 'sembilanbelas';
                break;
            case '20':
                $temp = 'duapuluh';
                break;
            case '21':
                $temp = 'duapuluhsatu';
                break;
            case '22':
                $temp = 'duapuluhdua';
                break;
            case '23':
                $temp = 'duapuluhtiga';
                break;
            case '24':
                $temp = 'duapuluhempat';
                break;
            case '25':
                $temp = 'duapuluhlima';
                break;
            case '26':
                $temp = 'duapuluhenam';
                break;
            case '27':
                $temp = 'duapuluhtujuh';
                break;
            case '28':
                $temp = 'duapuluhdelapan';
                break;
            case '29':
                $temp = 'duapuluhsembilan';
                break;
            case '30':
                $temp = 'tigapuluh';
                break;
            case '31':
                $temp = 'tigapuluhsatu';
                break;

            default:
                'enol';
                break;
        }
        return $temp;
    }


    public function autocomplete()
    {
        $ruangan = Ruangan::all();
        $jenis = JenisPegawai::all();
        $data = [
            'ruangan' => $ruangan,
            'jenis_pegawai' => $jenis
        ];
        return response()->json($data);
    }
    public function prota()
    {
        $periode = request('periode');
        $split = explode("-", $periode);
        $year = $split[0];
        $month = $split[1];
        $prota = Prota::whereMonth('tgl_libur', $month)
            ->whereYear('tgl_libur', $year)->get();
        return response()->json($prota);
    }
    public function rekapan_absen_perbulan()
    {
        $periode = request('periode');

        // return $periode;

        $data = Pegawai::select('id', 'nip', 'nik', 'nama', 'kelamin', 'foto', 'ttdpegawai', 'kdpegsimrs', 'jenispegawai', 'jabatan', 'ruang', 'flag', 'alamat', 'aktif')
            ->where('aktif', '=', 'AKTIF')
            ->where(function ($query) {
                $query->when(request('flag') ?? false, function ($search, $q) {
                    return $search->where('flag', '=', $q);
                });
                $query->when(request('ruang') ?? false, function ($search, $q) {
                    return $search->where('ruang', '=', $q);
                });
            })
            ->filter(request(['q']))
            ->with([
                "transaksi_absen.kategory",
                "jenis_pegawai",
                "relasi_jabatan",
                "ruangan",
                "transaksi_absen" => function ($q) use ($periode) {
                    // $split = explode("-", $periode);
                    // $year = $split[0];
                    // $month = $split[1];
                    // $q->whereMonth('created_at', $month)
                    //     ->whereYear('created_at', $year);
                    $q->where('created_at', 'like', $periode . '-%');
                },
                "user.libur" => function ($q) use ($periode) {
                    // $split = explode("-", $periode);
                    // $year = $split[0];
                    // $month = $split[1];
                    // $q->whereMonth('tanggal', $month)
                    //     ->whereYear('tanggal', $year);
                    $q->where('tanggal', 'like', $periode . '-%');
                },
                "alpha" => function ($q) use ($periode) {
                    // $split = explode("-", $periode);
                    // $year = $split[0];
                    // $month = $split[1];
                    // $q->whereMonth('tanggal', $month)
                    //     ->whereYear('tanggal', $year);
                    $q->where('tanggal', 'like', $periode . '-%');
                }
            ])
            // ->orderBy(request('order_by'), request('sort'))
            ->orderBy('flag', 'ASC')
            ->orderBy('nama', 'ASC')
            ->paginate(request('per_page'));

        $data->getCollection()->each(function ($pegawai) {
            $pegawai->transaksi_absen->each(function ($absen) {
                if ($absen->kategory) {
                    $absen->setRelation('kategory', clone $absen->kategory);
                    $absen->kategory->setReferenceDate($absen->tanggal);
                }
            });
        });

        // $data->setAppends([]);
        return response()->json($data);
    }

    public function print_absen_perbulan()
    {
        $periode = request('periode');

        $data = Pegawai::where('aktif', '=', 'AKTIF')
            ->where(function ($query) {
                $query->when(request('flag') ?? false, function ($search, $q) {
                    return $search->where('flag', '=', $q);
                });
                $query->when(request('ruang') ?? false, function ($search, $q) {
                    return $search->where('ruang', '=', $q);
                });
            })
            ->filter(request(['q']))
            ->with([
                "transaksi_absen.kategory",
                "jenis_pegawai",
                "relasi_jabatan",
                "ruangan",
                "transaksi_absen" => function ($q) use ($periode) {
                    $split = explode("-", $periode);
                    $year = $split[0];
                    $month = $split[1];
                    $q->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year);
                },
                "user.libur" => function ($q) use ($periode) {
                    $split = explode("-", $periode);
                    $year = $split[0];
                    $month = $split[1];
                    $q->whereMonth('tanggal', $month)
                        ->whereYear('tanggal', $year);
                },
                "alpha" => function ($q) use ($periode) {
                    $split = explode("-", $periode);
                    $year = $split[0];
                    $month = $split[1];
                    $q->whereMonth('tanggal', $month)
                        ->whereYear('tanggal', $year);
                }
            ])
            // ->orderBy(request('order_by'), request('sort'))
            ->orderBy('flag', 'ASC')
            ->orderBy('nama', 'ASC')
            ->get();

        $data->each(function ($pegawai) {
            $pegawai->transaksi_absen->each(function ($absen) {
                if ($absen->kategory) {
                    $absen->setRelation('kategory', clone $absen->kategory);
                    $absen->kategory->setReferenceDate($absen->tanggal);
                }
            });
        });

        return response()->json($data);
    }
}
