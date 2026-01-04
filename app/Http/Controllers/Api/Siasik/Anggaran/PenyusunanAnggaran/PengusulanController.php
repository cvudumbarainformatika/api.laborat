<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Aset\Master\Maset;
use App\Models\Siasik\Anggaran\Penetapan_Pagu;
use App\Models\Siasik\Anggaran\Pengusulan_header;
use App\Models\Siasik\Anggaran\Pengusulan_rinci;
use App\Models\Siasik\Master\Master_Jasa;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PengusulanController extends Controller
{
    public function selectKegiatan()
    {
        $perPage = request('per_page', 50);
        $tahun = request('tahun','Y');
        $query = Penetapan_Pagu::where('penetapan_pagu.tahun',$tahun)
        ->join('kegiatan_blud', 'kegiatan_blud.no', 'penetapan_pagu.kodekegiatan')
        ->select('penetapan_pagu.*', 'kegiatan_blud.no', 'kegiatan_blud.nomenklatur', 'kegiatan_blud.kode');
         if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('nomenklatur', 'like', '%' . $cari . '%')
                  ->orWhere('kegiatanblud', 'like', '%' . $cari . '%');
            });
        }
         if ($perPage <= 0) {
            $data = $query->get();
            return new JsonResponse(['data' => $data]);
        }
        $data = $query->simplePaginate($perPage);
        return new JsonResponse($data);
    }
    public function selectItem()
    {
        $jenis    = request('jenis');
        $q        = request('q');
        $perPage  = request('per_page', 50);

        // Tentukan model berdasarkan jenis
        switch ($jenis) {
            case 'Barang':
                $query = BarangRS::query()
                ->with('barang108.maping', 'rekening50', 'satuan', 'satuankecil', 'depo');
                break;

            case 'Jasa':
                $query = Master_Jasa::query();
                break;

            case 'Modal':
                $query = Maset::query()
                    ->whereNull('flaging');;
                break;

            default:
                return response()->json([
                    'message' => 'Jenis tidak valid'
                ], 422);
        }

        // Filter / pencarian
        if ($q) {
        $query->where(function ($w) use ($q, $jenis) {
                if ($jenis === 'Barang') {
                    $w->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode','like', "%{$q}%");
                } elseif ($jenis === 'Jasa') {
                    $w->where('nama', 'like', "%{$q}%");
                } else {
                    $w->where('kdaset', 'like', "%{$q}%")
                    ->orWhere('namaaset','like', "%{$q}%");
                }
            });
        }

        // Paginate
        $data = $query->orderBy('id', 'desc')
                      ->simplePaginate($perPage);

        return response()->json($data);
     
    }

    public function index(){
        $perPage = request('per_page', 50);
        $tahun = request('tahun', date('Y'));
        $q = request('q');
        $query = Pengusulan_header::with('rincian')
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
            $query->orderBy('notrans', 'desc')->get()
        );
    }
   
    public function save(Request $request){
        $validated = $request->validate([
            'notrans' => 'nullable',
            'kodeRuangan' => 'required',
            'ruangan' => 'required',
            'kodeKegiatan' => 'required',
            'kegiatan' => 'required',
            'kodebagian' => 'nullable',
            'organisasi_nama' => 'nullable',
            'kode50' => 'nullable',
            'uraian' => 'nullable',
            'paguanggaran' => 'required',
            'tglTransaksi' => 'required',
        ], [
            // 'notrans.required' => 'Nomer Transaksi Gagal Generate.',
            'kodeRuangan.required' => 'Kode Ruangan Harus Di isi.',
            'ruangan.required' => 'Ruangan Harus Di isi.',
            'kodeKegiatan.required' => 'Kode Kegiatan Harus Di isi.',
            'kegiatan.required' => 'Kegiatan Harus Di isi.',
            'paguanggaran.required' => 'Pagu Anggaran Harus Di isi.',
            'tglTransaksi.required' => 'Tanggal Transaksi Harus Di isi.',
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
            $notrans = FormatingHelper::nonotadinas($nomer, 'PENGUSULAN');
        } else {
            $notrans = $request->notrans;
        }
        try {
            DB::beginTransaction();

            $anggaran = Pengusulan_header::updateOrCreate(
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
                    'tgl_entry' => $time,
                    'user_entry' => $pegawai,
                ]
            );
            if ($anggaran) {
                $volume = (int) $request->volume;
                $harga  = (int) $request->harga;
                $nilai  = $volume * $harga;
                Pengusulan_rinci::create([
                    'notrans' => $anggaran->notrans,
                    'kode' => $request->kode,
                    'keterangan' => $request->keterangan,
                    'volume' => $volume,
                    'harga' => $harga,
                    'nilai' => $nilai,
                    'satuan' => $request->satuan,
                    'jenis' => $request->jenis,
                    'tglEntry' => $time,
                    'userEntry' => $pegawai,

                ]);
            }

            DB::commit();
            $anggaran = Pengusulan_header::with(['rincian'])->find($anggaran->id);
            return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $anggaran]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
        }
    }

    public function deleterinci(Request $request)
    {
        $header = Pengusulan_header::where('notrans', $request->notrans)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'NPD Masih Dikunci'], 500);
        }

        // 1️⃣ ambil 1 rinci (MODEL, bukan collection)
        $rinci = Pengusulan_rinci::find($request->id);

        if (!$rinci) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $notrans = $rinci->notrans;

        // 3️⃣ hapus rinci
        $rinci->delete();

        // 4️⃣ cek sisa rinci
        $sisaRinci = Pengusulan_rinci::where('notrans', $notrans)->get();

        // 5️⃣ kalau sudah habis, hapus header
        if (count($sisaRinci) === 0) {
            Pengusulan_header::where('notrans', $notrans)->delete();

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

}
