<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\Pergeseran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Perubahan_pak_header;
use App\Models\Siasik\Anggaran\Perubahan_RincianBelanja;
use App\Models\Siasik\Anggaran\Tampung_Pagu;
use App\Models\Siasik\Anggaran\Tampungcopy;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PergeseranAnggaranController extends Controller
{
    public function index(){
        $perPage = request('per_page', 50);
        $tahun = request('tahun', date('Y'));
        $q = request('q');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sebagai_sa = $pg->kdpegsimrs === '1215' || $pg->kdpegsimrs === 'sa';
        $query = Penyesuaian_Prioritas_Header::with(['penetapancopy' => function($q) {
            $q->with(['jurnal','realisasi_spjpanjar'=> function ($realisasi) {
                    $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                        'spjpanjar_rinci.jumlahbelanjapanjar');
                    },'realisasi'=> function ($realisasi) {
                    $realisasi->select('npdls_rinci.idserahterima_rinci',
                                        'npdls_rinci.nominalpembayaran')
                                        // ->sum('nominalpembayaran')
                                        // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                        ;
                    },'contrapost'=> function ($realisasi) {
                    $realisasi->select('contrapost.idpp',
                                        'contrapost.nominalcontrapost');
                    }]);
        }])
        ->withSum('penetapancopy as nilaipengusulan', 'pagu');

        if ($tahun) {
            $query->whereBetween('tgltrans', [
                $tahun . '-01-01',
                $tahun . '-12-31',
            ]);
        }
        if ($sebagai_sa !== true) {
            $query->where('kodepptk', $pegawai);
        }
        if ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('notrans', 'like', "%{$q}%")
                    ->orWhere('namabidang', 'like', "%{$q}%")
                    ->orWhere('kegiatan', 'like', "%{$q}%")
                    ->orWhere('pptk', 'like', "%{$q}%");
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
    public function save(Request $request){
        $validated = $request->validate([
            'noperubahan' => 'nullable',
            'notrans' => 'nullable',
            'tglperubahan' => 'required',
            'usulan' => 'required',
            'volume' => 'required',
            'harga' => 'required',
            'nilai' => 'required',
            'volumebaru' => 'required',
            'hargabaru' => 'required',
            'totalbaru' => 'required',
            'selisih' => 'nullable',
            'koderek108' => 'nullable',
            'uraian108' => 'nullable',
            'koderek50' => 'nullable',
            'uraian50' => 'nullable',
            'satuan' => 'nullable',
            'nousulan' => 'nullable',
            'kodekegiatanblud' => 'required',
            'uraianblud' => 'required',
            'kodebidang' => 'required',
            'bidang' => 'required',
            'namabidang' => 'nullable',
            'idpp' => 'nullable',
            'koders' => 'nullable',
            'jumlahacc' => 'nullable',
        ], [
            // 'notrans.required' => 'Nomer Transaksi Gagal Generate.',
            'tglperubahan.required' => 'Tanggal Perubahan Harus Di isi.',
            'usulan.required' => 'Usulan Harus Di isi.',
            'volume.required' => 'Volume Harus Di isi.',
            'harga.required' => 'Harga Harus Di isi.',
            'nilai.required' => 'Nilai Harus Di isi.',
            'volumebaru.required' => 'Volume Baru Harus Di isi.',
            'hargabaru.required' => 'Harga Baru Harus Di isi.',
            'totalbaru.required' => 'Total Baru Harus Di isi.',
            'kodekegiatanblud.required' => 'Kode Kegiatan BLUD Harus Di isi.',
            'uraianblud.required' => 'Uraian Kegiatan BLUD Harus Di isi.',
            'kodebidang.required' => 'Kode Bidang Harus Di isi.',
            'bidang.required' => 'Bidang Harus Di isi.'
        ]);

        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        $noperubahan = round(microtime(true) * 100);
        $label = '/P-RINCIANBELANJA';
        $labelitem = 'XX';

        if ($request->idpp) {
            // edit
            $idpp = $request->idpp;
        } else {
            // baru
            $idpp = $noperubahan . $labelitem;
        }

        if ($request->tgl){
            $tgl = $request->tgl;
        }   else {
            $tgl = date('Y');
        }

        $volumebaru = (int) $validated['volumebaru'];
        $hargabaru  = (int) $validated['hargabaru'];
        $totalbaru  = $volumebaru * $hargabaru;
        $selisih = $totalbaru - $validated['nilai'];

        try {
            DB::beginTransaction();

            // $header = Penyesuaian_Prioritas_Header::where('notrans', $validated['notrans'])
            //     ->first();
            $header = Tampung_Pagu::where('kodekegiatanblud', $validated['kodekegiatanblud'])
                ->first();
            if (!$header) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data Prioritas tidak ditemukan'
                ], 422);
            }

            $paguHeader = (int) $header->pagu;
            // $totalPenetapan = PergeseranPaguRinci::where('notrans', $validated['notrans'])
            // $totalPenetapan = Tampungcopy::where('notrans', $validated['notrans'])
            //     ->where('kodekegiatanblud', $validated['kodekegiatanblud'])
            //     ->where('bidang', $validated['kodebidang'])
            //     ->when($idpp, function ($q) use ($idpp) {
            //         // jika edit jangan ikut dihitung data lama
            //         $q->where('idpp', '!=', $idpp);
            //     })
            //     ->sum('pagu');
            $query = Tampungcopy::where('notrans', $validated['notrans'])
                ->where('kodekegiatanblud', $validated['kodekegiatanblud'])
                ->where('bidang', $validated['kodebidang']);
            $totalSemua = $query->sum('pagu');
            if ($request->filled('idpp')) {
                $totalPenetapan = $query->whereNot('idpp', $idpp)->sum('pagu');
            } else {
                $totalPenetapan = $totalSemua;
            }
            $totalSetelahSimpan = $totalPenetapan + $totalbaru;

            if ($totalSetelahSimpan > $paguHeader) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal disimpan! Jumlah Melebihi Pagu.'
                ], 422);
            }

            $anggaran = Perubahan_RincianBelanja::create(
                [
                    'noperubahan' => $noperubahan . $label,
                    'notrans' => $validated['notrans'],
                    'tglperubahan' => $validated['tglperubahan'],
                    'usulan' => $validated['usulan'],
                    'volume' => $validated['volume'],
                    'harga' => $validated['harga'],
                    'nilai' => $validated['nilai'],
                    'volumebaru' => $volumebaru,
                    'hargabaru' => $hargabaru,
                    'totalbaru' => $totalbaru,
                    'selisih' => $selisih,
                    'koderek108' => $validated['koderek108'] ?? '',
                    'uraian108' => $validated['uraian108'] ?? '',
                    'koderek50' => $validated['koderek50'] ?? '',
                    'uraian50' => $validated['uraian50'] ?? '',
                    'jumlahacc' => $validated['jumlahacc'] ?? 0,
                    'satuan' => $validated['satuan'] ?? '',
                    'nousulan' => $validated['nousulan'] ?? '',
                    'kodekegiatanblud' => $validated['kodekegiatanblud'],
                    'uraianblud' => $validated['uraianblud'],
                    'kodebidang' => $validated['kodebidang'],
                    'bidang' => $validated['namabidang'],
                    'koders' => $validated['koders'],
                    'idpp' => $idpp,
                    'tgl_entry' => $time,
                    'user_entry' => $pegawai,
                ]
            );
            if ($anggaran) {
                // $tampung = PergeseranPaguRinci::where('notrans', $anggaran->notrans)
                //     ->where('idpp', $request->idpp)
                //     ->first();
                // PergeseranPaguRinci::updateOrCreate(
                Tampungcopy::updateOrCreate(
                    [
                        'notrans' => $anggaran->notrans,
                        'idpp'    => $idpp,
                    ],
                    [
                        'volume' => $volumebaru,
                        'harga'  => $hargabaru,
                        'pagu'   => $totalbaru,
                        'usulan' => $validated['usulan'] ?? '',
                        'koderek108' => $validated['koderek108'] ?? '',
                        'koderek50' => $validated['koderek50'] ?? '',
                        'kodekegiatanblud' => $validated['kodekegiatanblud'],
                        'satuan' => $validated['satuan'] ?? '',
                        'uraian108' => $validated['uraian108'] ?? '',
                        'uraian50' => $validated['uraian50'] ?? '',
                        'bidang' => $validated['bidang'],
                        'koders' => $validated['koders'],
                        'flag' => '1',
                        'tgl' => $tgl,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            DB::commit();
            $data = Penyesuaian_Prioritas_Header::with(['penetapancopy' => function($q) {
            $q->with(['jurnal','realisasi_spjpanjar'=> function ($realisasi) {
                    $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                        'spjpanjar_rinci.jumlahbelanjapanjar');
                    },'realisasi'=> function ($realisasi) {
                    $realisasi->select('npdls_rinci.idserahterima_rinci',
                                        'npdls_rinci.nominalpembayaran')
                                        // ->sum('nominalpembayaran')
                                        // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                        ;
                    },'contrapost'=> function ($realisasi) {
                    $realisasi->select('contrapost.idpp',
                                        'contrapost.nominalcontrapost');
                        }]);
            }])
                ->when($anggaran->notrans, function ($q) use ($anggaran) {
                    $q->where('notrans', $anggaran->notrans);
                })
                ->first();
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $data]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Data gagal disimpan: ' . $e->getMessage()
            ], 500);
        }
    }


    public function verifikasi(Request $request)
    {
        $request->validate([
            'notrans' => 'required',
        ]);

        DB::beginTransaction();
        try {

            // ambil data dari staging
            $datas = Tampungcopy::where('notrans', $request->notrans)
                ->get();

            if ($datas->isEmpty()) {
                return response()->json([
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            foreach ($datas as $data) {

                PergeseranPaguRinci::updateOrInsert(
                    [
                        'notrans' => $data->notrans,
                        'idpp' => $data->idpp,
                    ],
                    [
                        'usulan' => $data->usulan,
                        'pagu' => $data->pagu,
                        'koderek108' => $data->koderek108,
                        'koderek50' => $data->koderek50,
                        'kodekegiatanblud' => $data->kodekegiatanblud,
                        'tgl' => $data->tgl,
                        'volume' => $data->volume,
                        'harga' => $data->harga,
                        'satuan' => $data->satuan,
                        'uraian50' => $data->uraian50,
                        'uraian108' => $data->uraian108,
                        'flag' => $data->flag,
                        'bidang' => $data->bidang,
                        'koders' => $data->koders,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil di verifikasi'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cetakData()
    {
        $rkaawal = Penyesuaian_Prioritas_Header::where('penyesesuaianperioritas_heder.notrans', request('notrans'))
            ->with(['rinciantampung' => function($query) {
                $query->join('akun50_2024', 'akun50_2024.kodeall2', '=', 't_tampung.koderek50')
                    ->select(
                        't_tampung.idpp',
                        't_tampung.notrans',
                        't_tampung.usulan',
                        't_tampung.koderek108',
                        't_tampung.uraian108',
                        't_tampung.volume',
                        't_tampung.harga',
                        't_tampung.pagu as total',
                        't_tampung.satuan',
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
            }, 'rincianpergeseran' => function($query) {
                $query->join('akun50_2024', 'akun50_2024.kodeall2', '=', 'perubahanrincianbelanja.koderek50')
                    ->select(
                        'perubahanrincianbelanja.id',
                        'perubahanrincianbelanja.idpp',
                        'perubahanrincianbelanja.notrans',
                        'perubahanrincianbelanja.usulan',
                        'perubahanrincianbelanja.koderek108',
                        'perubahanrincianbelanja.uraian108',
                        'perubahanrincianbelanja.satuan',
                        'perubahanrincianbelanja.volumebaru',
                        'perubahanrincianbelanja.hargabaru',
                        'perubahanrincianbelanja.totalbaru',
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                        'akun50_2024.kodeall3 as kode6',
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 1) LIMIT 1) as uraian1'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 2) LIMIT 1) as uraian2'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 3) LIMIT 1) as uraian3'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 4) LIMIT 1) as uraian4'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 5) LIMIT 1) as uraian5'),
                        'akun50_2024.uraian as uraian6'
                    )
                    ->havingRaw('perubahanrincianbelanja.id = (SELECT MAX(id) FROM perubahanrincianbelanja prb2 WHERE prb2.idpp = perubahanrincianbelanja.idpp)');
            }])
            ->get();

        $combinedData = $rkaawal->map(function ($item) {
            // Ambil idpp langsung dari properti model sebelum konversi
            $rincianData = $item->rinciantampung->isNotEmpty() ? $item->rinciantampung->map(function ($rincian) {
                $data = $rincian->toArray(); // Gunakan toArray() untuk konversi yang lebih aman
                $data['idpp'] = (string) $rincian->idpp; // Ambil idpp dari properti model
                return $data;
            })->keyBy('idpp')->all() : [];

            $pergeseranData = $item->rincianpergeseran->isNotEmpty() ? $item->rincianpergeseran->map(function ($pergeseran) {
                $data = $pergeseran->toArray();
                return $data;
            })->keyBy('idpp')->all() : [];

            $hasilpergeseran = [];

            // Tambahkan semua rincian sebagai dasar
            if (!empty($rincianData)) {
                foreach ($rincianData as $idpp => $rincian) {
                    $pergeseran = $pergeseranData[$idpp] ?? null;
                    $hasilpergeseran[] = [
                        'idpp' => $idpp,
                        'usulan' => $rincian['usulan'] ?? '',
                        'koderek108' => $rincian['koderek108'] ?? '',
                        'uraian108' => $rincian['uraian108'] ?? '',
                        'volume' => $rincian['volume'] ?? 0,
                        'harga' => $rincian['harga'] ?? 0,
                        'total' => $rincian['total'] ?? 0,
                        'satuan' => $rincian['satuan'] ?? '',
                        'volumebaru' => $pergeseran['volumebaru'] ?? $rincian['volume'] ?? 0,
                        'hargabaru' => $pergeseran['hargabaru'] ?? $rincian['harga'] ?? 0,
                        'totalbaru' => $pergeseran['totalbaru'] ?? $rincian['total'] ?? 0,
                        'kode1' => $rincian['kode1'] ?? '',
                        'kode2' => $rincian['kode2'] ?? '',
                        'kode3' => $rincian['kode3'] ?? '',
                        'kode4' => $rincian['kode4'] ?? '',
                        'kode5' => $rincian['kode5'] ?? '',
                        'kode6' => $rincian['kode6'] ?? '',
                        'uraian1' => $rincian['uraian1'] ?? '',
                        'uraian2' => $rincian['uraian2'] ?? '',
                        'uraian3' => $rincian['uraian3'] ?? '',
                        'uraian4' => $rincian['uraian4'] ?? '',
                        'uraian5' => $rincian['uraian5'] ?? '',
                        'uraian6' => $rincian['uraian6'] ?? '',
                    ];
                }
            }

            // Tambahkan rincianpergeseran yang tidak ada di rincian
            if (!empty($pergeseranData)) {
                foreach ($pergeseranData as $idpp => $pergeseran) {
                    if (!isset($rincianData[$idpp])) {
                        $hasilpergeseran[] = [
                            'idpp' => $idpp,
                            'usulan' => $pergeseran['usulan'] ?? '',
                            'koderek108' => $pergeseran['koderek108'] ?? '',
                            'uraian108' => $pergeseran['uraian108'] ?? '',
                            'volume' => 0,
                            'harga' => 0,
                            'total' => 0,
                            'satuan' => $pergeseran['satuan'] ?? '',
                            'volumebaru' => $pergeseran['volumebaru'] ?? 0,
                            'hargabaru' => $pergeseran['hargabaru'] ?? 0,
                            'totalbaru' => $pergeseran['totalbaru'] ?? 0,
                            'kode1' => $pergeseran['kode1'] ?? '',
                            'kode2' => $pergeseran['kode2'] ?? '',
                            'kode3' => $pergeseran['kode3'] ?? '',
                            'kode4' => $pergeseran['kode4'] ?? '',
                            'kode5' => $pergeseran['kode5'] ?? '',
                            'kode6' => $pergeseran['kode6'] ?? '',
                            'uraian1' => $pergeseran['uraian1'] ?? '',
                            'uraian2' => $pergeseran['uraian2'] ?? '',
                            'uraian3' => $pergeseran['uraian3'] ?? '',
                            'uraian4' => $pergeseran['uraian4'] ?? '',
                            'uraian5' => $pergeseran['uraian5'] ?? '',
                            'uraian6' => $pergeseran['uraian6'] ?? '',
                        ];
                    }
                }
            }

            return [
                'id' => $item->id,
                'notrans' => $item->notrans,
                'kodepptk' => $item->kodepptk,
                'pptk' => $item->pptk,
                'kodebidang' => $item->kodebidang,
                'namabidang' => $item->namabidang,
                'kodekegiatan' => $item->kodekegiatan,
                'kegiatan' => $item->kegiatan,
                'capaianprogram' => $item->capaianprogram,
                'masukan' => $item->masukan,
                'keluaran' => $item->keluaran,
                'hasil' => $item->hasil,
                'targetcapaian' => $item->targetcapaian,
                'targetkeluaran' => $item->targetkeluaran,
                'targethasil' => $item->targethasil,
                'hasilpergeseran' => $hasilpergeseran,
            ];
        })->all();


        $datapak = Perubahan_pak_header::where('usulanHonor_h_pak.notrans', request('notrans'))
            ->with(['rincipak' => function($query){
                $query->join('akun50_2024', 'akun50_2024.kodeall2', '=', 'usulanHonor_r_pak.koderek50')
                    ->select(
                        'usulanHonor_r_pak.idpp',
                        'usulanHonor_r_pak.notrans',
                        'usulanHonor_r_pak.keterangan as usulan',
                        'usulanHonor_r_pak.koderek108',
                        'usulanHonor_r_pak.uraian108',
                        'usulanHonor_r_pak.volume',
                        'usulanHonor_r_pak.harga',
                        'usulanHonor_r_pak.nilai as total',
                        'usulanHonor_r_pak.satuan',
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
                    );
            }, 'pergeseranpak' => function($query) {
                $query->join('akun50_2024', 'akun50_2024.kodeall2', '=', 'perubahanrincianbelanja.koderek50')
                    ->select(
                        'perubahanrincianbelanja.id',
                        'perubahanrincianbelanja.idpp',
                        'perubahanrincianbelanja.notrans',
                        'perubahanrincianbelanja.usulan',
                        'perubahanrincianbelanja.koderek108',
                        'perubahanrincianbelanja.uraian108',
                        'perubahanrincianbelanja.satuan',
                        'perubahanrincianbelanja.volumebaru',
                        'perubahanrincianbelanja.hargabaru',
                        'perubahanrincianbelanja.totalbaru',
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                        'akun50_2024.kodeall3 as kode6',
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 1) LIMIT 1) as uraian1'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 2) LIMIT 1) as uraian2'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 3) LIMIT 1) as uraian3'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 4) LIMIT 1) as uraian4'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 5) LIMIT 1) as uraian5'),
                        'akun50_2024.uraian as uraian6'
                    )
                    ->havingRaw('perubahanrincianbelanja.id = (SELECT MAX(id) FROM perubahanrincianbelanja prb2 WHERE prb2.idpp = perubahanrincianbelanja.idpp)');
            }])
            ->get();

        // Menggabungkan rincian dan rincianpergeseran ke hasilpergeseran
       

    // Membuat objek baru perubahanpak berdasarkan rincipak dan hasilpergeseran
    $finalData = collect($combinedData)->map(function ($item) use ($datapak) {
       
        $rincianData = collect($item['hasilpergeseran'])->keyBy('idpp')->all();

    // --- Data rincipak
    $rincipakData = $datapak->isNotEmpty() ? $datapak->flatMap(function ($pakItem) {
        return $pakItem->rincipak->map(function ($rincipak) {
            $data = $rincipak->toArray();
            $data['idpp'] = (string) $rincipak->idpp;
            return $data;
        });
    })->keyBy('idpp')->all() : [];

    // --- Data pergeseranpak (RELASI BARU)
    $pergeseranPakData = $datapak->isNotEmpty() ? $datapak->flatMap(function ($pakItem) {
        return $pakItem->pergeseranpak->map(function ($pgs) {
            $data = $pgs->toArray();
            $data['idpp'] = (string) $pgs->idpp;
            return $data;
        });
    })->keyBy('idpp')->all() : [];


    $perubahanpak = [];

    foreach ($rincianData as $idpp => $rincian) {

        $rincipak  = $rincipakData[$idpp] ?? null;
        $pergespak = $pergeseranPakData[$idpp] ?? null;

        $perubahanpak[] = [
            'idpp' => $idpp,
            'usulan' => $rincian['usulan'] ?? '',

            'koderek108' => $rincian['koderek108'] ?? '',
            'uraian108' => $rincian['uraian108'] ?? '',

            // nilai dasar RKA/hasilpergeseran
            'volume' => $rincian['volumebaru'] ?? 0,
            'harga'  => $rincian['hargabaru'] ?? 0,
            'total'  => $rincian['totalbaru'] ?? 0,
            'satuan' => $rincian['satuan'] ?? '',

            // NILAI HASIL PERUBAHAN PAK (PRIORITAS: pergeseranpak → rincipak)
            'volumebaru' => $pergespak['volumebaru'] ?? $rincipak['volume'] ?? 0,
            'hargabaru'  => $pergespak['hargabaru']  ?? $rincipak['harga']  ?? 0,
            'totalbaru'  => $pergespak['totalbaru']  ?? $rincipak['total']  ?? 0,

            // mapping kode akun
            'kode1' => $rincian['kode1'] ?? '',
            'kode2' => $rincian['kode2'] ?? '',
            'kode3' => $rincian['kode3'] ?? '',
            'kode4' => $rincian['kode4'] ?? '',
            'kode5' => $rincian['kode5'] ?? '',
            'kode6' => $rincian['kode6'] ?? '',
            'uraian1' => $rincian['uraian1'] ?? '',
            'uraian2' => $rincian['uraian2'] ?? '',
            'uraian3' => $rincian['uraian3'] ?? '',
            'uraian4' => $rincian['uraian4'] ?? '',
            'uraian5' => $rincian['uraian5'] ?? '',
            'uraian6' => $rincian['uraian6'] ?? '',
        ];
    }

    // Tambahkan rincipak yang belum ada di rincianData
    foreach ($rincipakData as $idpp => $rincipak) {
        if (!isset($rincianData[$idpp])) {

            $pergespak = $pergeseranPakData[$idpp] ?? null;

            $perubahanpak[] = [
                'idpp' => $idpp,
                'usulan' => $rincipak['usulan'] ?? '',
                'koderek108' => $rincipak['koderek108'] ?? '',
                'uraian108' => $rincipak['uraian108'] ?? '',

                'volume' => 0,
                'harga'  => 0,
                'total'  => 0,
                'satuan' => $rincipak['satuan'] ?? '',

                'volumebaru' => $pergespak['volumebaru'] ?? $rincipak['volume'] ?? 0,
                'hargabaru'  => $pergespak['hargabaru']  ?? $rincipak['harga']  ?? 0,
                'totalbaru'  => $pergespak['totalbaru']  ?? $rincipak['total']  ?? 0,

                'kode1' => $rincipak['kode1'] ?? '',
                'kode2' => $rincipak['kode2'] ?? '',
                'kode3' => $rincipak['kode3'] ?? '',
                'kode4' => $rincipak['kode4'] ?? '',
                'kode5' => $rincipak['kode5'] ?? '',
                'kode6' => $rincipak['kode6'] ?? '',
                'uraian1' => $rincipak['uraian1'] ?? '',
                'uraian2' => $rincipak['uraian2'] ?? '',
                'uraian3' => $rincipak['uraian3'] ?? '',
                'uraian4' => $rincipak['uraian4'] ?? '',
                'uraian5' => $rincipak['uraian5'] ?? '',
                'uraian6' => $rincipak['uraian6'] ?? '',
            ];
        }
    }

    // Tambahkan pergeseranpak yang tidak ada di rincipak dan rincianData
    foreach ($pergeseranPakData as $idpp => $pergespak) {
        if (!isset($rincipakData[$idpp]) && !isset($rincianData[$idpp])) {

            $perubahanpak[] = [
                'idpp' => $idpp,
                'usulan' => $pergespak['usulan'] ?? '',
                'koderek108' => $pergespak['koderek108'] ?? '',
                'uraian108' => $pergespak['uraian108'] ?? '',

                'volume' => 0,
                'harga'  => 0,
                'total'  => 0,
                'satuan' => $pergespak['satuan'] ?? '',

                'volumebaru' => $pergespak['volumebaru'] ?? 0,
                'hargabaru'  => $pergespak['hargabaru']  ?? 0,
                'totalbaru'  => $pergespak['totalbaru']  ?? 0,

                'kode1' => $pergespak['kode1'] ?? '',
                'kode2' => $pergespak['kode2'] ?? '',
                'kode3' => $pergespak['kode3'] ?? '',
                'kode4' => $pergespak['kode4'] ?? '',
                'kode5' => $pergespak['kode5'] ?? '',
                'kode6' => $pergespak['kode6'] ?? '',
                'uraian1' => $pergespak['uraian1'] ?? '',
                'uraian2' => $pergespak['uraian2'] ?? '',
                'uraian3' => $pergespak['uraian3'] ?? '',
                'uraian4' => $pergespak['uraian4'] ?? '',
                'uraian5' => $pergespak['uraian5'] ?? '',
                'uraian6' => $pergespak['uraian6'] ?? '',
            ];
        }
    }
        

        return [
            'id' => $item['id'],
            'notrans' => $item['notrans'],
            'kodepptk' => $item['kodepptk'],
            'pptk' => $item['pptk'],
            'kodebidang' => $item['kodebidang'],
            'namabidang' => $item['namabidang'],
            'kodekegiatan' => $item['kodekegiatan'],
            'kegiatan' => $item['kegiatan'],
            'capaianprogram' => $item['capaianprogram'],
            'masukan' => $item['masukan'],
            'keluaran' => $item['keluaran'],
            'hasil' => $item['hasil'],
            'targetcapaian' => $item['targetcapaian'],
            'targetkeluaran' => $item['targetkeluaran'],
            'targethasil' => $item['targethasil'],
            'hasilpergeseran' => $item['hasilpergeseran'],
            'perubahanpak' => $perubahanpak,
            
        ];
    })->all();

        return new JsonResponse($finalData);
    }
    
}
