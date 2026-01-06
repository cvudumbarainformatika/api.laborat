<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran\PenyusunanAnggaran;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Pengusulan_header;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Rinci;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PenyesuaianPrioritasController extends Controller
{
    public function selectPengusulan()
    {
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

    public function index(){
        $perPage = request('per_page', 50);
        $tahun = request('tahun', date('Y'));
        $q = request('q');
        $query = Penyesuaian_Prioritas_Header::with('rincian')
        ->withSum('rincian as nilaianggaran', 'nilai');

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
            $query->orderBy('notrans', 'desc')->get()
        );
    }


    public function save(Request $request)
    {
        $validated = $request->validate([
            'notrans' => 'nullable',
            'kodepptk' => 'required',
            'pptk' => 'required',
            'kodebidang' => 'required',
            'namabidang' => 'required',
            'kodekegiatan' => 'required',
            'kegiatan' => 'required',
            'tgltrans' => 'nullable',
            'kdruang_pengusul' => 'nullable',
            'ruang_pengusul' => 'nullable',
            'capaianprogram' => 'nullable',
            'masukan' => 'nullable',
            'keluaran' => 'nullable',
            'hasil' => 'nullable',
            'targetcapaian' => 'nullable',
            'targetkeluaran' => 'nullable',
            'targethasil' => 'nullable',
        ],
        [
            'kodepptk.required' => 'Kode PPTK Harus Di isi.',
            'pptk.required' => 'PPTK Harus Di isi.',
            'kodebidang.required' => 'Kode Bidang Harus Di isi.',
            'namabidang.required' => 'Bidang Harus Di isi.',
            'kodekegiatan.required' => 'Kode Bidang Harus Di isi.',
            'kegiatan.required' => 'Kegiatan Harus Di isi.',
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
                    'masukan' => $validated['masukan'],
                    'keluaran' => $validated['keluaran'],
                    'hasil' => $validated['hasil'],
                    'targetcapaian' => $validated['targetcapaian'],
                    'targetkeluaran' => $validated['targetkeluaran'],
                    'targethasil' => $validated['targethasil'],
                    'tgl_entry' => $time,
                    'user_entry' => $pegawai,
                ]
            );
            if ($anggaran) {
                $exists = Penyesuaian_Prioritas_Rinci::where('notrans', $anggaran->notrans)
                    ->where('koders', $request->koders)
                    ->exists();

                if ($exists) {
                    return new JsonResponse([
                        'message' => 'Item Pengusulan Sudah ada di Rincian'
                    ], 422);
                }
                $volume = (int) $request->jumalhacc;
                $harga  = (int) $request->harga;
                $nilai  = $volume * $harga;
                Penyesuaian_Prioritas_Rinci::create([
                    'notrans' => $anggaran->notrans,
                    'usulan' => $request->usulan,
                    'keterangan' => $request->keterangan,
                    'jumalhacc' => $volume,
                    'volume' => $volume,
                    'harga' => $harga,
                    'nilai' => $nilai,
                    'koderek108' => $request->koderek108,
                    'uraian108' => $request->uraian108,
                    'koderek50' => $request->koderek50,
                    'uraian50' => $request->uraian50,
                    'satuan' => $request->satuan,

                    'nousulan' => $request->nousulan,
                    'koders' => $request->koders,
                    'tglEntry' => $time,
                    'userEntry' => $pegawai,

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
}
