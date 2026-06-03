<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiPanjar;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiPjr\NpdPanjar_Header;
use App\Models\Siasik\TransaksiPjr\NpdPanjar_Rinci;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class NPD_PanjarController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'tglnpdpanjar' => 'nullable|date',
            'triwulan' => 'nullable|string',
            'kodepptk' => 'required|string',
            'pptk' => 'required|string',
            'program' => 'nullable|string',
            'kegiatan' => 'nullable|string',
            'kodekegiatanblud' => 'required|string',
            'kegiatanblud' => 'required|string',
            'userentry' => 'nullable|string',
            'tglentry' => 'nullable|date',
            'kunci' => 'nullable|string',
            'kodebidang' => 'nullable|string',
            'bidang' => 'nullable|string',
        ], [
            'kodepptk.required' => 'Kode PPTK harus diisi.',
            'pptk.required' => 'Nama PPTK harus diisi.',
            'kodekegiatanblud.required' => 'Kode Kegiatan BLUD harus diisi.',
            'kegiatanblud.required' => 'Nama Kegiatan BLUD harus diisi.',
            
        ]);
        try {
            $time = date('Y-m-d H:i:s');
            $user = auth()->user()->pegawai_id;
            $pegawai = Pegawai::find($user);
            $kodepegawai = $pegawai->kdpegsimrs;
            $tanggal = $request->tglnpdpanjar;
            $bulan = Carbon::parse($tanggal)->month;
            if ($bulan >= 1 && $bulan <= 3) {
                $triwulan = 'TRIWULAN 1';
            } elseif ($bulan >= 4 && $bulan <= 6) {
                $triwulan = 'TRIWULAN 2';
            } elseif ($bulan >= 7 && $bulan <= 9) {
                $triwulan = 'TRIWULAN 3';
            } else {
                $triwulan = 'TRIWULAN 4';
            }
            
            if (empty($request->nonpdpanjar)) {
                DB::connection('siasik')->select('call nonpdpanjar(@nomor)');
                $x = DB::connection('siasik')->table('conter')->select('nonpdpanjar')->first();

                if (!$x) {
                    throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
                }
                $nomer = (int)$x->nonpdpanjar;
                $nonpdpanjar = FormatingHelper::nonotadinas($nomer, 'NPD-PANJAR');
            } else {
                $nonpdpanjar = $request->nonpdpanjar;
            }
            DB::beginTransaction();
            $save = NpdPanjar_Header::updateOrCreate(
                [
                    'nonpdpanjar' => $nonpdpanjar,
                ],
                [
                    'tglnpdpanjar' => $validated['tglnpdpanjar'],
                    'triwulan' => $triwulan ?? '',
                    'kodepptk' => $validated['kodepptk'],
                    'pptk' => $validated['pptk'],
                    'program' => 'PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA',
                    'kegiatan' => 'PELAYANAN DAN PENUNJANG PELAYANAN BLUD',
                    'kodekegiatanblud' => $validated['kodekegiatanblud'],
                    'kegiatanblud' => $validated['kegiatanblud'],
                    'userentry' => $kodepegawai,
                    'tglentry' => $time,
                    'kodebidang' => $validated['kodebidang'],
                    'bidang' => $validated['bidang'],

                ]
            );
            if ($save) {
                NpdPanjar_Rinci::create([
                    'nonpdpanjar' => $save->nonpdpanjar,
                    'nopp' => $request->nopp,
                    'koderek50' => $request->koderek50,
                    'rincianbelanja50' => $request->rincianbelanja50,
                    'koderek108' => $request->koderek108 ?? '',
                    'uraian108' => $request->uraian108 ?? '',
                    'itembelanja' => $request->itembelanja,
                    'volume' => $request->volume,
                    'harga' => $request->harga,
                    'total' => $request->total,
                    'satuan' => $request->satuan,
                    'userentry' => $save->userentry,
                    'tglentry' => $save->tglentry,
                    'volumepermintaanpanjar' => $request->volumepermintaanpanjar,
                    'hargapermintaanpanjar' => $request->hargapermintaanpanjar,
                    'totalpermintaanpanjar' => $request->totalpermintaanpanjar,
                    'idpp' => $request->idpp,
                ]);
                
            }
            DB::commit();
            $save = NpdPanjar_Header::with(['npdpjr_rinci'])->find($save->id);
                return new JsonResponse(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $save]);
            } catch (\Exception $e) {
                DB::rollBack();
                return new JsonResponse(['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()], 500);
            }
    }

    public function delete(Request $request)
    {
        $header = NpdPanjar_Header::
        where('nonpdpanjar', $request->nonpdpanjar)
        ->where('kunci', '!=', '')
        ->where('verif', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'Nota Dinas Masih Terkunci dan Terverifikasi'], 500);
        }


        if ($request->id) {
            $findrinci = NpdPanjar_Rinci::where('id', $request->id)->first();
        } else {
            $findrinci = NpdPanjar_Rinci::where('nonpdpanjar', $request->nonpdpanjar)->first();
        }

        if (!$findrinci) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        else {
            $findrinci->delete();
        
            $rinciAll = NpdPanjar_Rinci::where('nonpdpanjar', $request->nonpdpanjar)->get();
            if(count($rinciAll) === 0){
                $header = NpdPanjar_Header::where('nonpdpanjar', $request->nonpdpanjar)->first();

                if ($header) {
                    $header->delete();
                    return new JsonResponse([
                        'message' => 'Data Berhasil dihapus',
                        'data' => []
                    ], 200);
                } else {
                    return new JsonResponse([
                        'message' => 'Data header tidak ditemukan',
                    ], 404);
                }
            }
            return new JsonResponse([
                'message' => 'Data Berhasil dihapus',
                'data' => $rinciAll
            ]);
        }
    }

}
