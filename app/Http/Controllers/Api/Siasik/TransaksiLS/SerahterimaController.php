<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\Serahterima_header;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Return_;

class SerahterimaController extends Controller
{
    public function getkontrak(){
        $tahun=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $data = KontrakPengerjaan::where('kunci', '!=', '')
        ->whereBetween('tgltrans', [$tahun.'-01-01', $tahun.'-12-31'])
        ->when(request('q'), function($q){
            $q->where('nokontrak', 'LIKE', '%' . request('q') . '%')
                ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
                ->orWhere('namapptk', 'LIKE', '%' . request('q') . '%')
                ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })
        ->get();
        return new JsonResponse($data);
    }
    public function savedata(Request $request)
    {
        // Tentukan noserahterima
        if (empty($request->noserahterima)) {
            // Panggil stored procedure noserahterimaPekerjaan di siasik
            DB::connection('siasik')->select('call noserahterimapekerjaan(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('noserahterimapekerjaan')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur noserahterimaPekerjaan');
            }
            $nomer = (int)$x->noserahterimapekerjaan; // Gunakan nomor dari counter sebagai $total

            // Format nomor menggunakan FormatingHelper::nostp
            $noserahterima = FormatingHelper::nostp($nomer, 'SERAHTERIMA');
        } else {
            $noserahterima = $request->noserahterima;
        }
        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        try
        {
            DB::beginTransaction();
            $save = Serahterima_header::updateOrCreate
            (
                [
                    'noserahterimapekerjaan' => $noserahterima
                ],
                [
                    'nokontrak'=>$request->nokontrak ?? '',
                    'kodepihakketiga'=>$request->kodepihakketiga ?? '',
                    'namaperusahaan'=>$request->namaperusahaan ?? '',
                    'kodemapingrs'=>$request->kodemapingrs ?? '',
                    'namasuplier'=>$request->namasuplier ?? '',
                    'tglmulaikontrak'=>$request->tglmulaikontrak ?? '',
                    'tglakhirkontrak'=>$request->tglakhirkontrak ?? '',
                    'tgltrans'=>$request->tgltrans ?? '',
                    'kodepptk'=>$request->kodepptk ?? '',
                    'namapptk'=>$request->namapptk ?? '',
                    'program'=>$request->program ?? '',
                    'kegiatan'=>$request->kegiatan ?? '',
                    'kodekegiatanblud'=>$request->kodekegiatanblud ?? '',
                    'kegiatanblud'=>$request->kegiatanblud ?? '',
                    'tglentry'=>$time ?? '',
                    'userentry'=>$pegawai ?? '',
                ]
            );
            foreach ($request->rincian as $rinci)
            {
                $save->rinci()->create(
                    [
                        'noserahterimapekerjaan'=>$save->noserahterimapekerjaan,
                        'nokontrak'=>$rinci['nokontrak'] ?? '',
                        'koderek50'=>$rinci['koderek50'] ?? '',
                        'uraianrek50'=>$rinci['uraianrek50'] ?? '',
                        'koderek108'=>$rinci['koderek108'] ?? '',
                        'uraian108'=>$rinci['uraian108'] ?? '',
                        'itembelanja'=>$rinci['itembelanja'] ?? '',
                        'idserahterima_rinci'=>$rinci['idserahterima_rinci'] ?? '',
                        'volume'=>$rinci['volume'] ?? '',
                        'satuan'=>$rinci['satuan'] ?? '',
                        'harga'=>$rinci['harga'] ?? '',
                        'total'=>$rinci['total'] ?? '',
                        'volumels'=>$rinci['volumels'] ?? '',
                        'hargals'=>$rinci['hargals'] ?? '',
                        'totalls'=>$rinci['totalls'] ?? '',
                        'nominalpembayaran'=>$rinci['nominalpembayaran'] ?? '',
                        'tglentry'=>$time ?? '',
                        'userentry'=>$pegawai ?? '',
                    ]
                );
            }
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
}
