<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\Pergeseran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Perubahan_RincianBelanja;
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
        $query = Penyesuaian_Prioritas_Header::with(['penetapan' => function($q) {
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
        ->withSum('penetapan as nilaipengusulan', 'pagu');

        if ($tahun) {
            $query->whereBetween('tgltrans', [
                $tahun . '-01-01',
                $tahun . '-12-31',
            ]);
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

            $header = Penyesuaian_Prioritas_Header::where('notrans', $validated['notrans'])
                ->first();
            if (!$header) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data Prioritas tidak ditemukan'
                ], 422);
            }

            $paguHeader = (int) $header->pagu;
            $totalPenetapan = PergeseranPaguRinci::where('notrans', $validated['notrans'])
                ->where('kodekegiatanblud', $validated['kodekegiatanblud'])
                ->where('bidang', $validated['kodebidang'])
                ->when($idpp, function ($q) use ($idpp) {
                    // jika edit jangan ikut dihitung data lama
                    $q->where('idpp', '!=', $idpp);
                })
                ->sum('pagu');
            $totalSetelahSimpan = $totalPenetapan + $totalbaru;

            // VALIDASI
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
                    'bidang' => $validated['bidang'],
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
                PergeseranPaguRinci::updateOrCreate(
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
                    ]
                );
            }

            DB::commit();
            $data = Penyesuaian_Prioritas_Header::with(['penetapan' => function($q) {
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
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }
    
}
