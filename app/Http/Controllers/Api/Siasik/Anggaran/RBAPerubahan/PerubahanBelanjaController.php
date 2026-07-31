<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\RBAPerubahan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penetapan_Pagu_pak;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Perubahan_pak_header;
use App\Models\Siasik\Anggaran\Perubahan_pak_rinci;
use App\Models\Sigarang\Pegawai;
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
                $q->where('nomenklatur', 'like', '%' . $cari . '%')
                  ->orWhere('kegiatanblud', 'like', '%' . $cari . '%');
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
        $ambil = PergeseranPaguRinci::where('tgl', request('tahun'))
        ->where('kodekegiatanblud', request('kodeKegiatan'))
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
        $q = request('q');
        $query = Perubahan_pak_header::with('rincian')
        ->withSum('rincian as nilaipengusulan', 'nilai');

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
                $rinci = Perubahan_pak_rinci::create([
                    'notrans' => $anggaran->notrans ?? '',
                    'kode' => $request->kode ?? '',
                    'keterangan' => $request->keterangan ?? '',
                    'volume' => $volume ?? 0,
                    'harga' => $harga ?? 0,
                    'nilai' => $nilai ?? 0,
                    'satuan' => $request->satuan ?? '',
                    'jenis' => $request->jenis ?? '',
                    // 'kodebidangpengusul' => $request->kodebidangpengusul ?? '',
                    // 'bidangPengusul' => $request->bidangPengusul ?? '',
                    'idpp' => $request->idpp ?? '',
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

                ]);

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
}
