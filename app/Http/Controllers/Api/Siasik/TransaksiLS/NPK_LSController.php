<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\FormatingHelper;
use App\Models\Sigarang\Pegawai;

class NPK_LSController extends Controller
{

    public function listdata()
    {
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = NpkLS_heder::whereBetween('npkls_heder.tglnpk', [$tahunawal.'-01-01', $tahun.'-12-31']);

            $nota=$data->join('npkls_rinci', 'npkls_rinci.nonpk', '=',  'npkls_heder.nonpk')
            ->join('npdls_heder', 'npdls_heder.nonpk', '=',  'npkls_heder.nonpk')
            ->with('rincians', function ($query) {
                $query->join('npdls_heder', 'npdls_heder.nonpdls', '=',  'npkls_rinci.nonpdls')
                // ->leftJoin('npdls_rinci', 'npdls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
                ->select('npdls_heder.pptk',
                 'npdls_heder.penerima',
                 'npdls_heder.bidang',
                 'npdls_heder.tglnpdls',
                 'npdls_heder.keterangan',
                 'npdls_heder.nonpdls',
                 'npkls_rinci.*',
                //  'npdls_rinci.*'
                )->with('npdlsrinci', function ($query) {
                    $query->join('akun50_2024', 'akun50_2024.kodeall2', 'npdls_rinci.koderek50')
                    ->select(
                        'npdls_rinci.nonpdls',
                        'npdls_rinci.nominalpembayaran as pengajuan',
                        'akun50_2024.kodeall3 as koderekening',
                        'akun50_2024.uraian as rekeningbelanja',
                        DB::raw('0 as pagu'), // Add pagu = 0
                        DB::raw('0 as realisasi') // Add realisasi = 0
                    );
                });
            })
            ->select('npkls_heder.*',
                'npdls_heder.nonpk',
                'npdls_heder.nopencairan',
                DB::raw('SUM(npkls_rinci.total) as total'),
            )
            ->when(request('q'), function($q){
                $q->where('npkls_heder.nonpk', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('npkls_heder.tglnpk', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('npkls_rinci.total', 'LIKE', '%' . request('q') . '%')
                    ;
            })
            ->groupBy('npkls_heder.nonpk')
            ->orderBy('npkls_heder.tglnpk', 'desc')
            ->get();
        return new JsonResponse($nota);

    }
    public function selectNpd() {

        // $tahun = Carbon::creatfromFormat('Y', request('tgl'))->format('Y');
        $tahun = request('tahun') ?? date('Y');
        $npd = NpdLS_heder::whereBetween('npdls_heder.tglnpdls', [$tahun.'-01-01', $tahun.'-12-31'])
        ->where('kunci','=', '1')
        ->where('verif', '=', '1')
        ->where('flagnotadinas', '=', 'DISETUJUI')
        ->where('flagnpk', '=', '')
        ->where('nonpk', '=', '')
        ->join('npdls_rinci', 'npdls_rinci.nonpdls', '=',  'npdls_heder.nonpdls')
        ->select('npdls_heder.*',
                DB::raw('SUM(npdls_rinci.nominalpembayaran) as total'),
                DB::raw('SUBSTRING_INDEX(npdls_rinci.koderek50, ".", 2) as kode'),
        )
        ->when(request('q'),function ($query) {
            $query
            ->where('npdls_heder.nonpdls', 'LIKE', '%' . request('q') . '%')
            ->where('npdls_rinci.nominalpembayaran', 'LIKE', '%' . request('q') . '%')
            ;
        })
        ->groupBy('npdls_heder.nonpdls')
        ->get();

        return new JsonResponse($npd);
    }

    public function getlistrinci()
    {
        $data = NpkLS_heder::where('npkls_heder.nonpk', request('nonpk'))
        ->select('npkls_heder.nonpk',
                        'npkls_heder.tglnpk',
                        'npkls_rinci.*',
        )
        ->join('npkls_rinci', 'npkls_rinci.nonpk', 'npkls_heder.nonpk')
        ->get();

        return new JsonResponse($data);
    }

    public function savedata(Request $request)
    {
        if (empty($request->nonpk)) {
            DB::connection('siasik')->select('call nonpkls(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('nonpkls')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur nonpkls');
            }
            $nomer = (int)$x->nonpkls;
            $nonpk = FormatingHelper::nonotadinas($nomer, 'NPK-LS');
        } else {
            $nonpk = $request->nonpk;
        }

        
        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        try
        {
            DB::beginTransaction();
            $save = NpkLS_heder::updateOrCreate
            (
                [
                    'nonpk' => $nonpk
                ],
                values: [
                    
                    'tglnpk'=>$request->tglnpk ?? '',
                    'kodepptk'=>$request->kodepptk ?? '',
                    'akun'=>'BLUD',
                    'tglentry'=>$time ?? '',
                    'userentry'=>$pegawai ?? '',
                    'nonpdls'=>$request->nonpdls ?? '',
                ]
            );
            $rincidata = [];
            foreach ($request->rincians as $rinci)
            {
                $save->rincians()->create(
                    [
                        'nonpk'=>$save->nonpk,
                        'nonpdls'=>$rinci['nonpdls'] ?? '',
                        'tglnpd'=>$rinci['tglnpd'] ?? '',
                        'kegiatan'=>$rinci['kegiatan'] ?? '',
                        'kodekegiatanblud'=>$rinci['kodekegiatanblud'] ?? '',
                        'kegiatanblud'=>$rinci['kegiatanblud'] ?? '',
                        'notadinas'=>$rinci['notadinas'] ?? '',
                        'total'=>$rinci['total'] ?? '',
                        'tglentry'=>$time ?? '',
                        'userentry'=>$pegawai ?? '',
                    ]
                );
                $rincidata[] = $rinci['nonpdls'];
            }
            NpdLS_heder::where('nonpdls', $rincidata)
            ->update([
                'nonpk' => $save->nonpk,
                'flagnpk' => '1',
            ]);
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!',
                    'result' => $save,
                ], 200
            );
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse(
                [
                    'message' => 'Ada Kesalahan',
                    'error' => $er->getMessage()
                ], 500
            );
        }
    }

    public function deleterinci(Request $request)
    {
        $header = NpkLS_heder::
        where('nonpk', $request->nonpk)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'NPK-LS Masih Terkunci'], 500);
        }


        if ($request->id) {
            $findrinci = NpkLS_rinci::where('id', $request->id)->first();
        } else {
            $findrinci = NpkLS_rinci::where('nonpdls', $request->nonpdls)->first();
        }

        if (!$findrinci) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        else {
        $findrinci->delete();
        NpdLS_heder::whereIn('nonpdls', [$request->nonpdls])->update(['nonpk' => '', 'flagnpk' => '']);
        }
        $rinciAll = NpkLS_rinci::where('nonpk', $request->nonpk)->get();
        if(count($rinciAll) === 0){
            $header = NpkLS_heder::where('nonpk', $request->nonpk)->first();

             if ($header) {
                $header->delete();
                NpdLS_heder::whereIn('nonpk', [$request->nonpk])->update(['nonpk' => '', 'flagnpk' => '']);
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

    public function kuncidata (Request $request)
    {
        $request->validate([
            'nonpk' => 'required|string'
        ]);

        try {
            $header = NpkLS_heder::where('nonpk', $request->nonpk)->first();
            if (!$header) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }
            if ($header->kunci == '1') {
                // Buka kunci → harus superadmin
                $user = auth()->user()->pegawai_id;
                $pg = Pegawai::find($user);

                if (!$pg || $pg->kdpegsimrs !== 'sa') {
                    return response()->json(['message' => 'Anda tidak Memiliki Izin Membuka Kunci Data ini, Silahkan Hubungi Admin'], 403);
                }

                if (!empty($header->nopencairan)) {
                    return response()->json(['message' => 'NPK-LS Sudah Pencairan'], 400);
                }

                $header->kunci = '';
                $header->save();

                return response()->json(['message' => 'Kunci berhasil dibuka'], 200);
            } else {
                $header->kunci = '1';
                $header->save();

                return response()->json(['message' => 'Data berhasil dikunci'], 200);
            }
        } catch (\Exception $e) {
            // Log::error('Gagal Membuka Kunci Serahterima: ' . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi Kesalahan saat Membuka Kunci',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
