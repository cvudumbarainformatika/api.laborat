<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Pengusulan_header;
use App\Models\Siasik\Anggaran\Pengusulan_rinci;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Rinci;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PenyesuaianPrioritasController extends Controller
{
    public function getRekening(){
        // $rekening = Akun_mapjurnal::pluck('kode50')->toArray();
        $perPage = request('per_page', 50); // Default ke 100 per halaman, 0 untuk semua data

        $query = Akun50_2024::select('uraian', 'kodeall3', 'kodeall2')
            ->where('subrincian_objek', '!=', '')
            ->where('akun', '5')
            ->with('rekening108');
        // Pencarian
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('uraian', 'like', '%' . $cari . '%')
                  ->orWhere('kodeall2', 'like', '%' . $cari . '%')
                  ->orWhere('kodeall3', 'like', '%' . $cari . '%');
            });
        }

        if ($perPage <= 0) {
            $akun = $query->get();
            return new JsonResponse(['data' => $akun]);
        }

        $akun = $query->simplePaginate($perPage);

        return new JsonResponse($akun);
    }
    public function selectPengusulan()
    {
        $perPage = request('per_page', 50);
        $tahun   = request('tahun', date('Y'));
        $q       = request('q');
        
        $query = Pengusulan_header::query()
            ->where('kunci', '1')
            ->with('rincian')
            ->withSum('rincian as nilaipengusulan', 'nilai')
            ->leftJoin(
                'mappingpptkkegiatan',
                'mappingpptkkegiatan.kodekegiatan',
                '=',
                'usulanHonor_h.kodeKegiatan'
            )
            ->select(
                'usulanHonor_h.*',
                'mappingpptkkegiatan.kodepptk',
                'mappingpptkkegiatan.namapptk'
            );

        if ($tahun) {
            $query->whereBetween('tglTransaksi', [
                $tahun . '-01-01',
                $tahun . '-12-31',
            ]);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('usulanHonor_h.notrans', 'like', "%{$q}%")
                ->orWhere('usulanHonor_h.ruangan', 'like', "%{$q}%")
                ->orWhere('usulanHonor_h.kegiatan', 'like', "%{$q}%");

                if (is_numeric($q) && strlen($q) === 4) {
                    $w->orWhereYear('tglTransaksi', $q);
                }
            });

            if (is_numeric($q)) {
                $query->having('nilaipengusulan', (float) $q);
            }
        }

        $query->orderByDesc('notrans');

        // 🔥 SAMA PERSIS DENGAN selectKegiatan
        if ($perPage <= 0) {
            $data = $query->get();
            return new JsonResponse(['data' => $data]);
        }

        $data = $query->simplePaginate($perPage);
        return new JsonResponse($data);
    }

    public function index(){
        $perPage = request('per_page', 50);
        $tahun = request('tahun', date('Y'));
        $q = request('q');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $query = Penyesuaian_Prioritas_Header::with('rincian')
        ->withSum('rincian as nilaianggaran', 'nilai');
        if ($sa !== 'sa' && $sa !== '1619' && $sa !== '38' && $sa !== '1618' && $sa !== '81_X' && $sa !== '1215') {
                $query->where('kodepptk', $pegawai);
            }

        if ($tahun) {
            $query->whereBetween('tgltrans', [
                $tahun . '-01-01',
                $tahun . '-12-31',
            ]);
        }
        if ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('notrans', 'like', "%{$q}%")
                    ->orWhere('pptk', 'like', "%{$q}%")
                    ->orWhere('namabidang', 'like', "%{$q}%")
                    ->orWhere('kegiatan', 'like', "%{$q}%");
                })
                ->having(function ($h) use ($q) {
                    $h->havingRaw('nilaianggaran LIKE ?', ["%{$q}%"])
                    ->orHavingRaw('nilaianggaran = ?', [(float) $q]);
                });
            }
        return response()->json(
            $query->orderBy('id', 'asc')->get()
        );
    }


    public function save(Request $request)
    {
        $validated = $request->validate([
            'notrans' => 'nullable',
            'pagu' => 'nullable',
            'kodepptk' => 'required',
            'pptk' => 'required',
            'kodebidang' => 'required',
            'namabidang' => 'required',
            'kodekegiatan' => 'required',
            'kegiatan' => 'required',
            'tgltrans' => 'nullable',
            'kdruang_pengusul' => 'nullable',
            'ruang_pengusul' => 'nullable',
            'capaianprogram' => 'required',
            // 'masukan' => 'required',
            'keluaran' => 'required',
            'hasil' => 'required',
            'targetcapaian' => 'required',
            'targetkeluaran' => 'required',
            'targethasil' => 'required',
        ],
        [
            'kodepptk.required' => 'Kode PPTK Harus Di isi.',
            'pptk.required' => 'PPTK Harus Di isi.',
            'kodebidang.required' => 'Kode Bidang Harus Di isi.',
            'namabidang.required' => 'Bidang Harus Di isi.',
            'kodekegiatan.required' => 'Kode Bidang Harus Di isi.',
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
        if (empty($request->notrans)) {
            DB::connection('siasik')->select('call penyesesuaianperioritas(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('penyesesuaianperioritas')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur');
            }
            $nomer = (int)$x->penyesesuaianperioritas;
            $notrans = FormatingHelper::nonotadinas($nomer, 'ANGGARAN');
        } else {
            $notrans = $request->notrans;
        }

        try {
            DB::beginTransaction();

            $anggaran = Penyesuaian_Prioritas_Header::updateOrCreate(
                [
                    'notrans' => $notrans
                ],
                [
                    'kodepptk' => $validated['kodepptk'],
                    'pptk' => $validated['pptk'],
                    'kodebidang' => $validated['kodebidang'],
                    'namabidang' => $validated['namabidang'],
                    'kodekegiatan' => $validated['kodekegiatan'],
                    'kegiatan' => $validated['kegiatan'],
                    'tgltrans' => $validated['tgltrans'],
                    'kdruang_pengusul' => $validated['kdruang_pengusul'],
                    'ruang_pengusul' => $validated['ruang_pengusul'],
                    'capaianprogram' => $validated['capaianprogram'],
                    // 'masukan' => $validated['masukan'],
                    'masukan' => 'Dana yang Dibutuhkan',
                    'keluaran' => $validated['keluaran'],
                    'hasil' => $validated['hasil'],
                    'targetcapaian' => $validated['targetcapaian'],
                    'targetkeluaran' => $validated['targetkeluaran'],
                    'targethasil' => $validated['targethasil'],
                    'pagu' => $validated['pagu'],
                    'tgl_entry' => $time,
                    'user_entry' => $pegawai,
                ]
            );
            if ($anggaran) {
                // $exists = Penyesuaian_Prioritas_Rinci::where('notrans', $anggaran->notrans)
                //     ->where('koders', $request->koders)
                //     ->exists();

                // if ($exists) {
                //     return new JsonResponse([
                //         'message' => 'Item Pengusulan Sudah ada di Rincian'
                //     ], 422);
                // }
                $jumlahacc = (int) $request->jumlahacc;
                $harga  = (int) $request->harga;
                $nilai  = $jumlahacc * $harga;
                Penyesuaian_Prioritas_Rinci::create([
                    'notrans' => $anggaran->notrans,
                    'usulan' => $request->usulan,
                    'jumlahacc' => $jumlahacc,
                    'volume' => $request->volume,
                    'harga' => $harga,
                    'nilai' => $nilai,
                    'koderek108' => $request->koderek108,
                    'uraian108' => $request->uraian108,
                    'koderek50' => $request->koderek50,
                    'uraian50' => $request->uraian50,
                    'satuan' => $request->satuan,

                    'nousulan' => $request->nousulan,
                    'koders' => $request->koders,
                    'tgl_entry' => $time,
                    'user_entry' => $pegawai,

                ]);
            }

            DB::commit();
            $anggaran = Penyesuaian_Prioritas_Header::with(['rincian'])->find($anggaran->id);
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $anggaran]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }
    public function deleterinci(Request $request)
    {
        $header = Penyesuaian_Prioritas_Header::where('notrans', $request->notrans)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'Data Masih Dikunci'], 500);
        }

        // 1️⃣ ambil 1 rinci (MODEL, bukan collection)
        $rinci = Penyesuaian_Prioritas_Rinci::find($request->id);

        if (!$rinci) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $notrans = $rinci->notrans;

        // 3️⃣ hapus rinci
        $rinci->delete();

        // 4️⃣ cek sisa rinci
        $sisaRinci = Penyesuaian_Prioritas_Rinci::where('notrans', $notrans)->get();

        // 5️⃣ kalau sudah habis, hapus header
        if (count($sisaRinci) === 0) {
            Penyesuaian_Prioritas_Header::where('notrans', $notrans)->delete();

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

            $data = Penyesuaian_Prioritas_Header::find($validated['id']);

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
        $nousulan = $request->nousulan;

        if (!$nousulan) {
            return response()->json([
                'message' => 'Nomor Usulan Wajib di isi!'
            ], 422);
        }
        $db = DB::connection('siasik');
        // Ambil data dari header + rincian
        $data = $db->table('penyesesuaianperioritas_rinci as r')
            ->join(
                'penyesesuaianperioritas_heder as h',
                'h.notrans',
                '=',
                'r.notrans'
            )
            ->where('r.nousulan', $nousulan)
            ->select([
                'r.id as idpp',
                'r.nousulan as notrans',
                'r.usulan',
                'r.nilai as pagu',
                'r.koderek50 as koderek50',
                'r.koderek108 as koderek108',
                'h.kodekegiatan as kodekegiatanblud',
                'h.tgltrans',
                'r.volume',
                'r.harga',
                'r.satuan',
                'r.uraian50',
                'r.uraian108',
                'h.kodebidang as bidang',
            ])
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

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
                'flag'               => '1'
            ];
        }

        // Optional: hapus dulu jika notrans sudah ada
         $db->transaction(function () use ($db, $nousulan, $insert) {
            $db->table('t_tampung')
                ->where('notrans', $nousulan)
                ->delete();

            $db->table('t_tampung')->insert($insert);
        });
        return response()->json([
            'message' => 'Data berhasil disimpan ke t_tampung (DB SIASIK)',
            'total'   => count($insert)
        ]);
    }


    public function updateData()
    {
        DB::connection('siasik')->statement("
            UPDATE usulanHonor_r u
            JOIN sigarang.barang_r_s b
            ON u.kode = b.kode
            SET
            u.kode_50   = b.kode_50,
            u.uraian50  = b.uraian_50,
            u.kode_108  = b.kode_108,
            u.uraian108 = b.uraian_108
            WHERE u.kode IS NOT NULL
        ");

        return response()->json([
            'message' => 'Update selesai (1 query, super cepat)'
        ]);
    }

    public function cetakData()
    {
        $rkaawal = Penyesuaian_Prioritas_Header::where('penyesesuaianperioritas_heder.notrans', request('notrans'))
            ->with(['rincian' => function($query) {
                $query->join('akun50_2024', function ($join) {
                    $join->on(
                        'akun50_2024.kodeall2',
                        '=',
                        'penyesesuaianperioritas_rinci.koderek50'
                    )->orOn(
                        'akun50_2024.kodeall3',
                        '=',
                        'penyesesuaianperioritas_rinci.koderek50'
                    );
                })
                    ->select(
                        'penyesesuaianperioritas_rinci.id as idpp',
                        'penyesesuaianperioritas_rinci.notrans',
                        'penyesesuaianperioritas_rinci.usulan',
                        'penyesesuaianperioritas_rinci.koderek108',
                        'penyesesuaianperioritas_rinci.uraian108',
                        'penyesesuaianperioritas_rinci.jumlahacc as volume',
                        'penyesesuaianperioritas_rinci.harga',
                        'penyesesuaianperioritas_rinci.nilai as total',
                        'penyesesuaianperioritas_rinci.satuan',
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                        'akun50_2024.kodeall3 as kode6',
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 1) LIMIT 1) as uraian1'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 2) LIMIT 1) as uraian2'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 3) LIMIT 1) as uraian3'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 4) LIMIT 1) as uraian4'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 5) LIMIT 1) as uraian5'),
                        'akun50_2024.uraian as uraian6'
                    )->distinct();
            }])
            ->get();
            return new JsonResponse(['data' => $rkaawal]);
    }
}
