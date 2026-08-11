<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penetapan_Pagu_pak;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Perubahan_pak_header;
use App\Models\Siasik\Anggaran\Perubahan_pak_rinci;
use App\Models\Siasik\Anggaran\Tampung_Pagu_pak;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerubahanBelanjaController extends Controller
{
    public function selectKegiatan()
    {
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $perPage = request('per_page', 50);
        $tahun = request('tahun','Y');
        $query = Penetapan_Pagu_pak::where('penetapan_pagu_pak.tahun',$tahun)
        ->join('kegiatan_blud', 'kegiatan_blud.no', 'penetapan_pagu_pak.kodekegiatan')
        ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', 'penetapan_pagu_pak.kodekegiatan')
        ->select('penetapan_pagu_pak.*', 'kegiatan_blud.no', 'kegiatan_blud.nomenklatur', 'kegiatan_blud.kode',
            'mappingpptkkegiatan.kodepptk',
            'mappingpptkkegiatan.namapptk' 
        );
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('kegiatan_blud.no', 'like', '%' . $cari . '%')
                  ->orWhere('kegiatan_blud.nomenklatur', 'like', '%' . $cari . '%')
                  ->orWhere('mappingpptkkegiatan.namapptk', 'like', '%' . $cari . '%');
            });
        }
        if ($sa !== 'sa' && $sa !== '1619' && $sa !== '38' && $sa !== '39' && $sa !== '81_X' && $sa !== '1215') {
                $query->where('kodepptk', $pegawai);
            }
        if ($perPage <= 0) {
            $data = $query->get();
            return new JsonResponse(['data' => $data]);
        }
        $data = $query->simplePaginate($perPage);
        return new JsonResponse($data);
    }

    public function selectItemlama()
    {
        $q        = request('q');
        $perPage  = request('per_page', 50);
        $idppTerpakai = DB::connection('siasik')
        ->table('usulanHonor_r_pak')
        ->pluck('idpp');

        $ambil = PergeseranPaguRinci::where('tgl', request('tahun'))
        ->where('kodekegiatanblud', request('kodeKegiatan'))
        ->whereNotIn('idpp', $idppTerpakai)
        ->select('t_tampung.*', 
                't_tampung.koders as kode', 
                't_tampung.usulan as keterangan',
                't_tampung.pagu as nilai');

        if($q){
            $ambil->where(function($cari) use ($q) {
                $cari->where('usulan', 'like', "%{$q}%")
                    ->orWhere('koderek108','like', "%{$q}%")
                    ->orWhere('koderek50','like', "%{$q}%")
                    ->orWhere('uraian108','like', "%{$q}%")
                    ->orWhere('uraian50','like', "%{$q}%");
            });
        }
        $data = $ambil->orderBy('koderek50', 'desc')
                      ->simplePaginate($perPage);

        $data->getCollection()->transform(function ($item) {

             $realisasiPanjar = $item->realisasi_spjpanjar->sum('jumlahbelanjapanjar');

            $realisasiNpd = $item->realisasi->sum('nominalpembayaran');

            $contrapost = $item->contrapost->sum('nominalcontrapost');

            $totalRealisasi = ($realisasiPanjar + $realisasiNpd) - $contrapost;

            $item->total_realisasi = $totalRealisasi;
            $item->sisapagu = $item->pagu - $totalRealisasi;

            return $item;

        });

        return new JsonResponse($data);
    }

    public function index(){
        $perPage = request('per_page', 50);
        $tahun = request('tahun', date('Y'));
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $q = request('q');
        $query = Perubahan_pak_header::with('rincian')
        ->withSum('rincian as nilaipengusulan', 'nilai');
        if ($sa !== 'sa' && $sa !== '1619' && $sa !== '38' && $sa !== '39' && $sa !== '81_X' && $sa !== '86_X' && $sa !== '1215') {
                $query->where('kodepptk', $pegawai);
            }
        if ($tahun) {
            $query->whereBetween('tglTransaksi', [
                $tahun . '-01-01',
                $tahun . '-12-31',
            ]);
        }
        if ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('notrans', 'like', "%{$q}%")
                    ->orWhere('ruangan', 'like', "%{$q}%")
                    ->orWhere('kegiatan', 'like', "%{$q}%")
                    ->orWhere('paguanggaran', 'like', "%{$q}%")
                    ->orWhereYear('tglTransaksi', $q);
                })
                ->having(function ($h) use ($q) {
                    $h->havingRaw('nilaipengusulan LIKE ?', ["%{$q}%"])
                    ->orHavingRaw('nilaipengusulan = ?', [(float) $q]);
                });
            }
        return response()->json(
            $query->orderBy('id', 'asc')->get()
        );
    }
    public function cetakData()
    {
        $tahun = request('tahun', date('Y'));
        $kodeKegiatan = request('kodeKegiatan');
        $notrans = request('notrans');

        $dataAwalQuery = PergeseranPaguRinci::query()
            ->leftJoin('akun50_2024', function ($join) {
                $join->on('t_tampung.koderek50', '=', 'akun50_2024.kodeall2')
                    ->orOn('t_tampung.koderek50', '=', 'akun50_2024.kodeall3');
            })
            ->join('penyesesuaianperioritas_heder', 'penyesesuaianperioritas_heder.kodekegiatan', '=', 't_tampung.kodekegiatanblud')
            ->select(
                't_tampung.idpp',
                't_tampung.tgl as tahun',
                't_tampung.notrans',
                't_tampung.kodekegiatanblud',
                't_tampung.bidang as kodebidang',
                't_tampung.usulan',
                't_tampung.koderek50',
                't_tampung.koderek108',
                't_tampung.uraian108',
                't_tampung.volume',
                't_tampung.harga',
                't_tampung.pagu as total',
                't_tampung.satuan',
                't_tampung.koders',
                't_tampung.bidang',
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                'akun50_2024.kodeall3 as kode6',
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 5) LIMIT 1) as uraian5'),
                'akun50_2024.uraian as uraian6'
            );

        if ($tahun) {
            $dataAwalQuery->where(function ($query) use ($tahun) {
                $query->where('tgl', (string) $tahun)
                    ->orWhere('tgl', 'like', (string) $tahun . '%')
                    ->orWhereRaw('YEAR(tgl) = ?', [(int) $tahun]);
            });
        }

        if ($kodeKegiatan) {
            $dataAwalQuery->where('kodekegiatanblud', $kodeKegiatan);
        }

        $dataAwal = $dataAwalQuery->get();

        $headerPak = Perubahan_pak_header::query()
            ->where('notrans', $notrans)
            ->first();

        if (!$headerPak && $kodeKegiatan) {
            $headerPak = Perubahan_pak_header::query()
                ->where('kodeKegiatan', $kodeKegiatan)
                ->first();
        }

        // $capaianProgramLama = null;
        if ($kodeKegiatan) {
            $prioritasHeader = Penyesuaian_Prioritas_Header::query()
                ->join('t_tampung', 'penyesesuaianperioritas_heder.kodekegiatan', '=', 't_tampung.kodekegiatanblud')
                ->where('penyesesuaianperioritas_heder.kodekegiatan', $kodeKegiatan)
                ->select('penyesesuaianperioritas_heder.capaianprogram',
                    'penyesesuaianperioritas_heder.masukan',
                    'penyesesuaianperioritas_heder.keluaran',
                    'penyesesuaianperioritas_heder.hasil',
                    'penyesesuaianperioritas_heder.targetcapaian',
                    'penyesesuaianperioritas_heder.targetkeluaran',
                    'penyesesuaianperioritas_heder.targethasil'
                    )
                ->first();

            // $capaianProgramLama = $prioritasHeader?->capaianprogram;
        }

        // $capaianProgramBaru = $headerPak?->capaianprogram;

        $dataPak = collect();
        if ($headerPak) {
            $dataPak = $headerPak->rincipak()
                ->leftJoin('akun50_2024', function ($join) {
                    $join->on('usulanHonor_r_pak.koderek50', '=', 'akun50_2024.kodeall2')
                        ->orOn('usulanHonor_r_pak.koderek50', '=', 'akun50_2024.kodeall3');
                })
                ->select(
                    'usulanHonor_r_pak.idpp',
                    'usulanHonor_r_pak.notrans',
                    'usulanHonor_r_pak.keterangan as usulan',
                    'usulanHonor_r_pak.volume',
                    'usulanHonor_r_pak.harga',
                    'usulanHonor_r_pak.nilai as total',
                    'usulanHonor_r_pak.satuan',
                    'usulanHonor_r_pak.koderek50',
                    'usulanHonor_r_pak.koderek108',
                    'usulanHonor_r_pak.uraian108',
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                    'akun50_2024.kodeall3 as kode6',
                    DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 1) LIMIT 1) as uraian1'),
                    DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 2) LIMIT 1) as uraian2'),
                    DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 3) LIMIT 1) as uraian3'),
                    DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 4) LIMIT 1) as uraian4'),
                    DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 5) LIMIT 1) as uraian5'),
                    'akun50_2024.uraian as uraian6'
                )
                ->get();
        }

        $dataAwalMap = $dataAwal->map(function ($item) {
            $data = $item->toArray();
            $data['idpp'] = (string) $item->idpp;
            return $data;
        })->keyBy('idpp')->all();

        $dataPakMap = $dataPak->map(function ($item) {
            $data = $item->toArray();
            $data['idpp'] = (string) $item->idpp;
            return $data;
        })->keyBy('idpp')->all();

        $paguAwal = (int) $dataAwal->sum('total');
        $paguBaru = (int) $dataPak->sum('total');

        $hasil = [];

        foreach ($dataAwalMap as $idpp => $awal) {
            $pak = $dataPakMap[$idpp] ?? null;
            $hasil[] = [
                'idpp' => $idpp,
                'notrans' => $awal['notrans'] ?? $pak['notrans'] ?? '',
                'kodekegiatanblud' => $awal['kodekegiatanblud'] ?? $pak['kodeKegiatan'] ?? '',
                'usulan' => $awal['usulan'] ?? '',
                'usulanbaru' => $pak['usulan'] ?? '',
                'volume' => (int) ($awal['volume'] ?? 0),
                'harga' => (int) ($awal['harga'] ?? 0),
                'total' => (int) ($awal['total'] ?? 0),
                'volumebaru' => (int) ($pak['volume'] ?? 0),
                'hargabaru' => (int) ($pak['harga'] ?? 0),
                'totalbaru' => (int) ($pak['total'] ?? 0),
                'satuan' => $awal['satuan'] ?? $pak['satuan'] ?? '',
                'koderek50' => $awal['koderek50'] ?? $pak['koderek50'] ?? '',
                'koderek108' => $awal['koderek108'] ?? $pak['koderek108'] ?? '',
                'uraian108' => $awal['uraian108'] ?? $pak['uraian108'] ?? '',
                'kode1' => $awal['kode1'] ?? $pak['kode1'] ?? '',
                'kode2' => $awal['kode2'] ?? $pak['kode2'] ?? '',
                'kode3' => $awal['kode3'] ?? $pak['kode3'] ?? '',
                'kode4' => $awal['kode4'] ?? $pak['kode4'] ?? '',
                'kode5' => $awal['kode5'] ?? $pak['kode5'] ?? '',
                'kode6' => $awal['kode6'] ?? $pak['kode6'] ?? '',
                'uraian1' => $awal['uraian1'] ?? $pak['uraian1'] ?? '',
                'uraian2' => $awal['uraian2'] ?? $pak['uraian2'] ?? '',
                'uraian3' => $awal['uraian3'] ?? $pak['uraian3'] ?? '',
                'uraian4' => $awal['uraian4'] ?? $pak['uraian4'] ?? '',
                'uraian5' => $awal['uraian5'] ?? $pak['uraian5'] ?? '',
                'uraian6' => $awal['uraian6'] ?? $pak['uraian6'] ?? '',
                'koders' => $awal['koders'] ?? '',
                'bidang' => $awal['bidang'] ?? '',
            ];
        }

        foreach ($dataPakMap as $idpp => $pak) {
            if (!isset($dataAwalMap[$idpp])) {
                $hasil[] = [
                    'idpp' => $idpp,
                    'notrans' => $pak['notrans'] ?? '',
                    'kodekegiatanblud' => '',
                    'usulan' => '',
                    'usulanbaru' => $pak['usulan'] ?? '',
                    'volume' => 0,
                    'harga' => 0,
                    'total' => 0,
                    'volumebaru' => (int) ($pak['volume'] ?? 0),
                    'hargabaru' => (int) ($pak['harga'] ?? 0),
                    'totalbaru' => (int) ($pak['total'] ?? 0),
                    'satuan' => $pak['satuan'] ?? '',
                    'koderek50' => $pak['koderek50'] ?? '',
                    'koderek108' => $pak['koderek108'] ?? '',
                    'uraian108' => $pak['uraian108'] ?? '',
                    'kode1' => $pak['kode1'] ?? '',
                    'kode2' => $pak['kode2'] ?? '',
                    'kode3' => $pak['kode3'] ?? '',
                    'kode4' => $pak['kode4'] ?? '',
                    'kode5' => $pak['kode5'] ?? '',
                    'kode6' => $pak['kode6'] ?? '',
                    'uraian1' => $pak['uraian1'] ?? '',
                    'uraian2' => $pak['uraian2'] ?? '',
                    'uraian3' => $pak['uraian3'] ?? '',
                    'uraian4' => $pak['uraian4'] ?? '',
                    'uraian5' => $pak['uraian5'] ?? '',
                    'uraian6' => $pak['uraian6'] ?? '',
                    'koders' => '',
                    'bidang' => '',
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'header' => $headerPak ? [
                    'notrans' => $headerPak->notrans,
                    'kegiatan' => $headerPak->kegiatan,
                    'kodepptk' => $headerPak->kodepptk,
                    'pptk' => $headerPak->pptk,
                ] : null,
                'hasilperubahan' => $hasil,
                'pagu' => $paguAwal,
                'pagubaru' => $paguBaru,
                'capaianprogramlama' => $prioritasHeader?->capaianprogram,
                'masukanlama' => $prioritasHeader?->masukan,
                'keluaranlama' => $prioritasHeader?->keluaran,
                'hasillama' => $prioritasHeader?->hasil,
                'targetcapaianlama' => $prioritasHeader?->targetcapaian,
                'targetkeluaranlama' => $prioritasHeader?->targetkeluaran,
                'targethasillama' => $prioritasHeader?->targethasil,


                'capaianprogrambaru' => $headerPak?->capaianprogram,
                'masukanbaru' => $headerPak->masukan,
                'keluaranbaru' => $headerPak->keluaran,
                'hasilbaru' => $headerPak->hasil,
                'targetcapaianbaru' => $headerPak->targetcapaian,
                'targetkeluaranbaru' => $headerPak->targetkeluaran,
                'targethasilbaru' => $headerPak->targethasil,

            ],
        ]);
    }

    public function save(Request $request){
        $validated = $request->validate([
            'notrans' => 'nullable',
            'kodeRuangan' => 'required',
            'ruangan' => 'required',
            'kodeKegiatan' => 'required',
            'kodepptk' => 'required',
            'pptk' => 'required',
            'kegiatan' => 'required',
            'kodebagian' => 'nullable',
            'organisasi_nama' => 'nullable',
            'kode50' => 'nullable',
            'uraian' => 'nullable',
            'paguanggaran' => 'required',
            'tglTransaksi' => 'required',
            'capaianprogram' => 'required',
            'idpp' => 'nullable',
            // 'masukan' => 'required',
            'keluaran' => 'required',
            'hasil' => 'required',
            'targetcapaian' => 'required',
            'targetkeluaran' => 'required',
            'targethasil' => 'required',
        ], [
            // 'notrans.required' => 'Nomer Transaksi Gagal Generate.',
            'kodeRuangan.required' => 'Kode Ruangan Harus Di isi.',
            'ruangan.required' => 'Ruangan Harus Di isi.',
            'kodeKegiatan.required' => 'Kode Kegiatan Harus Di isi.',
            'kegiatan.required' => 'Kegiatan Harus Di isi.',
            'paguanggaran.required' => 'Pagu Anggaran Harus Di isi.',
            'tglTransaksi.required' => 'Tanggal Transaksi Harus Di isi.',
            'kodepptk.required' => 'PPTK Tidak ditemukan, Silahkan Hubungi Admin',
            'pptk.required' => 'PPTK Tidak ditemukan, Silahkan Hubungi Admin',
            'capaianprogram.required' => 'Capaianprogram Harus Di isi.',
            // 'masukan.required' => 'Masukan Harus Di isi.',
            'keluaran.required' => 'Keluaran Harus Di isi.',
            'hasil.required' => 'Hasil Harus Di isi.',
            'targetcapaian.required' => 'Target Capaian Harus Di isi.',
            'targetkeluaran.required' => 'Target Keluaran Harus Di isi.',
            'targethasil.required' => 'Target Hasil Harus Di isi.',
        ]);

        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        $noperubahan = round(microtime(true) * 100);
        $labelitem = '-RPAK';

        if ($request->filled('idpp')) {
            $idpp = $request->idpp;
        } else {
            $idpp = $noperubahan . $labelitem;
        }

        if (empty($request->notrans)) {
            DB::connection('siasik')->select('call usulan_honor(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('usulan_honor')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur');
            }
            $nomer = (int)$x->usulan_honor;
            $notrans = FormatingHelper::nonotadinas($nomer, 'PENGUSULAN-PAK');
        } else {
            $notrans = $request->notrans;
        }
        try {
            DB::beginTransaction();

            $header = Tampung_Pagu_pak::where('kodekegiatanblud', $validated['kodeKegiatan'])
                ->first();
            if (!$header) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data Prioritas tidak ditemukan'
                ], 422);
            }
            $paguHeader = (int) $header->pagu;
            $anggaran = Perubahan_pak_header::updateOrCreate(
                [
                    'notrans' => $notrans
                ],
                [
                    'kodeRuangan' => $validated['kodeRuangan'],
                    'ruangan' => $validated['ruangan'],
                    'kodeKegiatan' => $validated['kodeKegiatan'],
                    'tglTransaksi' => $validated['tglTransaksi'],
                    'kegiatan' => $validated['kegiatan'],
                    'kodebagian' => $validated['kodebagian'],
                    'organisasi_nama' => $validated['organisasi_nama'],
                    'kode50' => $validated['kode50'],
                    'uraian' => $validated['uraian'],
                    'paguanggaran' => $validated['paguanggaran'],
                    'kodepptk' => $validated['kodepptk'],
                    'pptk' => $validated['pptk'],
                    'capaianprogram' => $validated['capaianprogram'],
                    // 'masukan' => $validated['masukan'],
                    'masukan' => 'Dana yang Dibutuhkan',
                    'keluaran' => $validated['keluaran'],
                    'hasil' => $validated['hasil'],
                    'targetcapaian' => $validated['targetcapaian'],
                    'targetkeluaran' => $validated['targetkeluaran'],
                    'targethasil' => $validated['targethasil'],
                    'tglEntry' => $time,
                    'userEntry' => $pegawai,
                ]
            );
            if ($anggaran) {
                $exists = Perubahan_pak_rinci::where('notrans', $anggaran->notrans)
                    ->where('kode', $request->kode)
                    ->when($request->filled('idpp'), function ($query) use ($idpp) {
                        $query->where('idpp', '!=', $idpp);
                    })
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    return new JsonResponse([
                        'message' => 'Item Pengusulan Sudah ada di Rincian'
                    ], 422);
                }
                $volume = (int) $request->volume;
                $harga  = (int) $request->harga;
                $nilai  = $volume * $harga;

                $totalNilaiSaatIni = (int) Perubahan_pak_rinci::where('notrans', $anggaran->notrans)
                    ->when($request->filled('idpp'), function ($query) use ($idpp) {
                        $query->where('idpp', '!=', $idpp);
                    })
                    ->sum('nilai');

                $totalSetelahSimpan = $totalNilaiSaatIni + $nilai;
                if ($totalSetelahSimpan > $paguHeader) {
                    DB::rollBack();
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Gagal disimpan! Jumlah melebihi Pagu.'
                    ], 422);
                }

                $rinci = Perubahan_pak_rinci::updateOrCreate(
                    [
                        'notrans' => $anggaran->notrans ?? '',
                        'idpp' => $idpp,
                    ],
                    [
                        'kode' => $request->kode ?? '',
                        'keterangan' => $request->keterangan ?? '',
                        'volume' => $volume ?? 0,
                        'harga' => $harga ?? 0,
                        'nilai' => $nilai ?? 0,
                        'satuan' => $request->satuan ?? '',
                        'jenis' => $request->jenis ?? '',
                        // 'kodebidangpengusul' => $request->kodebidangpengusul ?? '',
                        // 'bidangPengusul' => $request->bidangPengusul ?? '',
                        'paguterakhir' => $request->paguterakhir ?? 0,
                        'realisasi' => $request->realisasi ?? 0,
                        'sisaanggaran' => $request->sisaanggaran ?? 0,
                        'npdbelumcair' => $request->npdbelumcair ?? 0,
                        'pagualokasi' => $request->pagualokasi ?? 0,
                        'koderek50' => $request->koderek50 ?? '',
                        'uraian50' => $request->uraian50 ?? '',
                        'koderek108' => $request->koderek108 ?? '',
                        'uraian108' => $request->uraian108 ?? '',
                        'tglEntry' => $time ?? '',
                        'userEntry' => $pegawai ?? '',
                    ]
                );

                if (!$rinci) {
                    DB::rollBack();
                    return new JsonResponse([
                        'message' => 'Gagal menyimpan rincian'
                    ], 500);
                }
            }

            DB::commit();
            $anggaran = Perubahan_pak_header::with(['rincian'])->find($anggaran->id);
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $anggaran]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }

    public function deleterinci(Request $request)
    {
        $header = Perubahan_pak_header::where('notrans', $request->notrans)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'Data Masih Dikunci'], 500);
        }

        // 1️⃣ ambil 1 rinci (MODEL, bukan collection)
        $rinci = Perubahan_pak_rinci::find($request->id);

        if (!$rinci) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $notrans = $rinci->notrans;

        // 3️⃣ hapus rinci
        $rinci->delete();

        // 4️⃣ cek sisa rinci
        $sisaRinci = Perubahan_pak_rinci::where('notrans', $notrans)->get();

        // 5️⃣ kalau sudah habis, hapus header
        if (count($sisaRinci) === 0) {
            Perubahan_pak_header::where('notrans', $notrans)->delete();

            return response()->json([
                'message' => 'Data Berhasil dihapus',
                'data' => []
            ], 200);
        }

        // 6️⃣ masih ada rinci → kirim ulang
        return response()->json([
            'message' => 'Data Berhasil dihapus',
            'data' => $sisaRinci
        ], 200);
    }

    public function kunci(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'id' => 'required'
            ]);

            DB::beginTransaction();

            $data = Perubahan_pak_header::find($validated['id']);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->kunci = $data->kunci === '1' ? '' : '1';
            $data->save(); 
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $data->kunci === '1'
                ? 'Data berhasil dikunci'
                : 'Kunci berhasil dibuka',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal Kunci Data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function PenetapanAnggaran(Request $request)
    {
        $notrans = $request->notrans;

        if (!$notrans) {
            return response()->json([
                'message' => 'Nomor Usulan Wajib di isi!'
            ], 422);
        }
        $db = DB::connection('siasik');
        // Ambil data dari header + rincian
        $data = $db->table('usulanHonor_r_pak as r')
            ->join(
                'usulanHonor_h_pak as h',
                'h.notrans',
                '=',
                'r.notrans'
            )
            ->where('r.notrans', $notrans)
            ->select([
                'r.idpp',
                // 'r.nousulan as notrans',
                'h.notrans',
                'r.keterangan as usulan',
                'r.nilai as pagu',
                'r.koderek50 as koderek50',
                'r.koderek108 as koderek108',
                'h.kodeKegiatan as kodekegiatanblud',
                'h.tglTransaksi as tgltrans',
                'r.volume',
                'r.harga',
                'r.satuan',
                'r.uraian50',
                'r.uraian108',
                'h.kodebagian as bidang',
                'r.kode as koders',
                'h.paguanggaran as paguhedear'
            ])
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $header = $data->first();

        $tahun = Carbon::parse($header->tgltrans)->format('Y');

        $paguData = [
            'kodekegiatanblud' => $header->kodekegiatanblud,
            'tahun'            => $tahun,
            'pagu'             => $header->paguhedear
        ];
        $insert = [];

        foreach ($data as $row) {
            $insert[] = [
                'notrans'            => $row->notrans,
                'idpp'               => $row->idpp,
                'usulan'             => $row->usulan,
                'pagu'               => $row->pagu,
                'koderek108'         => $row->koderek108,
                'koderek50'          => $row->koderek50,
                'kodekegiatanblud'   => $row->kodekegiatanblud,
                // ambil tahun saja
                'tgl'                => Carbon::parse($row->tgltrans)->format('Y'),
                'volume'             => $row->volume,
                'harga'              => $row->harga,
                'satuan'             => $row->satuan,
                'uraian50'           => $row->uraian50,
                'uraian108'          => $row->uraian108,
                'bidang'             => $row->bidang,
                'koders'             => $row->koders,
                'flag'               => '1'
            ];
        }

        // Optional: hapus dulu jika notrans sudah ada
        $db->transaction(function () use ($db, $notrans, $insert, $paguData) {
            // =========================
            // DETAIL TAMPUNG
            // =========================
            foreach (['t_tampung'] as $table) {

                $db->table($table)
                    ->where('notrans', $notrans)
                    ->delete();

                if (!empty($insert)) {
                    $db->table($table)->insert($insert);
                }
            }

            // =========================
            // PAGU HEADER (UPSERT)
            // =========================
            foreach (['t_tampung_pagu'] as $table) {

                $db->table($table)->updateOrInsert(
                    [
                        'kodekegiatanblud' => trim($paguData['kodekegiatanblud']),
                    ],
                    [
                        'tahun' => $paguData['tahun'], // tetap boleh disimpan
                        'pagu'  => $paguData['pagu']
                    ]
                );
            }
        });
        return response()->json([
            'message' => 'Berhasil Penetapan Anggaran',
            'total'   => count($insert)
        ]);
    }
    public function PenetapanPAK(Request $request)
    {
        $kodeKegiatan = $request->kodekegiatanblud;

        if (empty($kodeKegiatan)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode kegiatan BLUD tidak boleh kosong.'
            ], 422);
        }

        // Semua proses menggunakan database SIASIK
        $db = DB::connection('siasik');

        $db->beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. AMBIL DATA T_TAMPUNG LAMA
            |--------------------------------------------------------------------------
            */

            $dataLama = $db->table('t_tampung')
                ->where('kodekegiatanblud', $kodeKegiatan)
                ->get();

            if ($dataLama->isEmpty()) {
                throw new \Exception(
                    'Data Anggaran untuk kode kegiatan ' . $kodeKegiatan . ' tidak ditemukan.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 2. BACKUP DATA LAMA KE T_TAMPUNG_PINDAHAN
            |--------------------------------------------------------------------------
            */

            $dataPindahan = [];

            foreach ($dataLama as $item) {

                $data = (array) $item;

                // ID t_tampung jangan ikut,
                // karena t_tampung_pindahan mempunyai ID sendiri
                unset($data['id']);

                $dataPindahan[] = $data;
            }

            if (!empty($dataPindahan)) {
                $db->table('t_tampung_pindahan')
                    ->insert($dataPindahan);
            }


            /*
            |--------------------------------------------------------------------------
            | 3. AMBIL DATA USULAN HONOR
            |    HEADER : usulanHonor_h_pak
            |    RINCIAN: usulanHonor_r_pak
            |--------------------------------------------------------------------------
            */

            $dataHonor = $db->table('usulanHonor_r_pak as r')
                ->join(
                    'usulanHonor_h_pak as h',
                    'h.notrans',
                    '=',
                    'r.notrans'
                )
                ->where('h.kodeKegiatan', $kodeKegiatan)
                ->select([
                    'r.idpp',

                    // Header
                    'h.notrans',
                    'h.kodeKegiatan as kodekegiatanblud',
                    'h.tglTransaksi as tgltrans',
                    'h.kodebagian as bidang',
                    'h.paguanggaran as paguhedear',

                    // Rincian
                    'r.keterangan as usulan',
                    'r.nilai as pagu',
                    'r.koderek50',
                    'r.koderek108',
                    'r.volume',
                    'r.harga',
                    'r.satuan',
                    'r.uraian50',
                    'r.uraian108',
                    'r.kode as koders',
                ])
                ->get();

            if ($dataHonor->isEmpty()) {
                throw new \Exception(
                    'Data Pengusulan PAK untuk kode kegiatan ' .
                    $kodeKegiatan .
                    ' tidak ditemukan.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 4. HAPUS T_TAMPUNG LAMA
            |--------------------------------------------------------------------------
            |
            | Backup SUDAH dilakukan di langkah sebelumnya.
            | Jadi sekarang aman untuk menghapus data lama.
            |
            */

            $jumlahDihapus = $db->table('t_tampung')
                ->where('kodekegiatanblud', $kodeKegiatan)
                ->delete();

            if ($jumlahDihapus === 0) {
                throw new \Exception(
                    'Data t_tampung gagal dihapus.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 5. SIAPKAN DATA BARU DARI USULAN HONOR
            |--------------------------------------------------------------------------
            */

            $dataBaru = [];

            foreach ($dataHonor as $honor) {

                $dataBaru[] = [
                    'notrans'          => $honor->notrans,
                    'idpp'             => $honor->idpp,
                    'usulan'            => $honor->usulan,
                    'pagu'             => $honor->pagu,

                    'koderek108'       => $honor->koderek108,
                    'koderek50'        => $honor->koderek50,

                    'kodekegiatanblud' => $honor->kodekegiatanblud,

                    // tgl di t_tampung hanya menyimpan tahun
                    'tgl'              => !empty($honor->tgltrans)
                        ? Carbon::parse($honor->tgltrans)->format('Y')
                        : null,

                    'volume'           => $honor->volume,
                    'harga'            => $honor->harga,
                    'satuan'           => $honor->satuan,

                    'uraian50'         => $honor->uraian50,
                    'uraian108'        => $honor->uraian108,

                    'bidang'           => $honor->bidang,
                    'koders'           => $honor->koders,

                    'flag'             => '1',

                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | 6. INSERT DATA BARU KE T_TAMPUNG
            |--------------------------------------------------------------------------
            */

            if (!empty($dataBaru)) {

                $db->table('t_tampung')
                    ->insert($dataBaru);
            }
  

            /*
            |--------------------------------------------------------------------------
            | 7. COMMIT
            |--------------------------------------------------------------------------
            */

            $db->commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil ditetapkan Perubahan PAK.',
                'kodekegiatanblud' => $kodeKegiatan,
                'jumlah_data_lama' => $dataLama->count(),
                'jumlah_data_baru' => count($dataBaru),
            ]);


        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            |
            | Jika backup / delete / insert gagal,
            | seluruh proses dibatalkan.
            |
            */

            $db->rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
