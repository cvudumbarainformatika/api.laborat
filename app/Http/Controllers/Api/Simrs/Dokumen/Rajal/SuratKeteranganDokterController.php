<?php

namespace App\Http\Controllers\Api\Simrs\Dokumen\Rajal;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Kwitansidetail;
use App\Models\Simrs\Master\MsuratKeteranganDokter;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\SuratPasien\SuratKeteranganDokter;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuratKeteranganDokterController extends Controller
{
    public function simpanskd(Request $request)
    {
        try {
            DB::beginTransaction();
                DB::select('call nota_tindakan(@nomor)');
                $x = DB::table('rs1')->select('rs14')->get();
                $wew = $x[0]->rs14;

                $notatindakan = FormatingHelper::notatindakan($wew, 'T-HD');

                if ($request->jenis === 'SRT02' || $request->jenis === 'SRT03') {
                    $notatindakan = FormatingHelper::notatindakan($wew, 'T-HD');
                }


                $createbill = null;
                if ($request->jenis !== 'SRT03') {
                    $createbill = self::createBill($request, $notatindakan);
                }


                // $cekharga = Mtindakan::where('rs1', 'T00668')->first();

                // if ($request->jenis === 'SRT02') {
                //     $cekharga = Mtindakan::where('rs1', 'TX0148')->first();
                // }

                // $js = $cekharga->rs8 ?? 0;
                // $jp = $cekharga->rs9 ?? 0;


                // $wew = FormatingHelper::session_user();
                // $kdpegsimrs = $wew['kodesimrs'];

                // $rs20 = 'Surat Keterangan Dokter';
                // if ($request->jenis === 'SRT02') {
                //     $rs20 = 'Surat Keterangan Kesehatan Jiwa';
                // }

                // $createbill = Tindakan::create([
                //     'rs1' => $request->noreg,
                //     'rs2' => $notatindakan,
                //     'rs3' => date('Y-m-d H:i:s'),
                //     'rs4' => 'T00668',
                //     'rs5' => 1,
                //     'rs6' => $js,
                //     'rs7' => $js,
                //     'rs8' => $request->kddpjp,
                //     'rs9' => $kdpegsimrs,
                //     // 'rs10' => $jp,
                //     // 'rs11' => $request->kdpoli,
                //     'rs13' => $jp,
                //     'rs14' => $jp,
                //     'rs20' => $rs20,
                //     'rs22' => $request->kdpoli,
                //     'rs24' => $request->sistembayar,
                // ]);

                $kode = $request->jenis;
                $nomor = '@nomor';

                DB::connection('mysql')->select('call conterSurat('.$nomor.', "'.$kode.'")');
                $datamaster = MsuratKeteranganDokter::where('kodeSurat', $kode)->first();
                // return new JsonResponse([ 'result' => $datamaster], 200);
                $nosurat = $datamaster->fAwal.''.$datamaster->conter.''.$datamaster->fAkhir;

                $simpan = SuratKeteranganDokter::create([
                    'nosurat' => $nosurat,
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'kdsurat' => $kode,
                    'pekerjaan' => $request->pekerjaan,
                    'untukKeperluan' => $request->keperluan,
                    'golonganDarah' => $request->golDar,
                    'pengliatanKiri' => $request->penglihatankiri,
                    'pengliatanKanan' => $request->penglihatankanan,
                    'pendengaranKiri' => $request->pendengarankiri,
                    'pendengaranKanan' => $request->pendengarankanan,
                    'perbedaanWarna' => $request->warna,
                    'gimut' => $request->gimut,
                    // 'tinggiBadan' => $request->tinggi,
                    // 'beratBadan' => $request->berat,


                    // ini untuk test kejiwaan
                    'pendidikan' => $request->pendidikan,
                    'statusperkawinan' => $request->statusperkawinan,
                    'psikatopologi' => $request->psikatopologi,
                    'kepribadian' => $request->kepribadian,
                    'kecerdasan' => $request->kecerdasan,

                    // ini untuk napza
                    'riwayatObat' => $request->riwayatObat,

                    'kesimpulan' => $request->doc,
                    'dokter' => $request->dokter,
                    'kdRuang' => $request->kdpoli,
                    'tindakan_id' => $createbill ? $createbill->id : null,
                ]);
            DB::commit();
                return new JsonResponse(['message' => 'Berhasil menyimpan surat keterangan dokter','result' => $simpan], 200);
            }catch(\Exception $th) {
                DB::rollback();
                return new JsonResponse(['message' => 'Gagal Disimpan', 'error' => $th->getMessage()], 500);
            }
    }

    public static function createBill($request, $notatindakan)
    {
       $cekharga = Mtindakan::where('rs1', 'T00668')->first();

        if ($request->jenis === 'SRT02') {
            $cekharga = Mtindakan::where('rs1', 'TX0148')->first();
        }

        $js = $cekharga->rs8 ?? 0;
        $jp = $cekharga->rs9 ?? 0;


        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];

        $rs20 = 'Surat Keterangan Dokter';
        if ($request->jenis === 'SRT02') {
            $rs20 = 'Surat Keterangan Kesehatan Jiwa';
        }

        $createbill = Tindakan::create([
            'rs1' => $request->noreg,
            'rs2' => $notatindakan,
            'rs3' => date('Y-m-d H:i:s'),
            'rs4' => 'T00668',
            'rs5' => 1,
            'rs6' => $js,
            'rs7' => $js,
            'rs8' => $request->kddpjp,
            'rs9' => $kdpegsimrs,
            // 'rs10' => $jp,
            // 'rs11' => $request->kdpoli,
            'rs13' => $jp,
            'rs14' => $jp,
            'rs20' => $rs20,
            'rs22' => $request->kdpoli,
            'rs24' => $request->sistembayar,
        ]);

        return $createbill;
    }

    public function skdbatal(Request $request)
    {
        try{
            DB::beginTransaction();
                $cek = DB::table('kwitansi_d')
                ->leftjoin('kwitansilog', 'kwitansilog.nokwitansi', 'kwitansi_d.no_kwitansi')
                ->where('id_trans', $request->tindakan_id)
                 ->where(function($query) {
                    $query->where('kwitansilog.batal', '')
                        ->orWhereNull('kwitansilog.batal');
                })
                ->count();

                if($cek > 0){
                    return new JsonResponse(['message' => 'Pasien Sudah Dibayar Ke Kasir'], 500);
                }

                $data = SuratKeteranganDokter::where('tindakan_id', $request->tindakan_id)
                ->where('kdsurat', 'SRT01')
                ->first();

                $delete = Tindakan::where('id', $request->tindakan_id)->delete();

                $wew = FormatingHelper::session_user();
                $kdpegsimrs = $wew['kodesimrs'];

                $update = $data->update([
                    'batal' => '1',
                    'userBatal' => $kdpegsimrs,
                    'tglBatal' => date('Y-m-d H:i:s')
                ]);
            DB::commit();
                return new JsonResponse(['result' => $data], 200);

        }catch(\Exception $th) {
            DB::rollback();
            return new JsonResponse(['message' => 'Surat Keterangan Dokter Gagal Dihapus,Coba Periksa Apakah Untuk Kwitansi Ini Sudah Di batal Sama KASIR...!!!', 'error' => $th->getMessage()], 500);
        }

    }

    public function cekpembayaran(Request $request)
    {
        // return new JsonResponse(['message' => 'OK'], 200);
        if($request->sistembayar === 'UMUM'){
            $data = Kwitansidetail::where('id_trans', $request->tindakan_id)->count();
            // return $data;
            if($data > 0){
                return new JsonResponse(['message' => 'OK'], 200);
            }else{
                return new JsonResponse(['message' => 'Pasien belum Bayar ke Kasir'], 500);
            }
        }else if(stripos($request->sistembayar, "BPJS") !== false){
            return new JsonResponse(['message' => 'Sistem Bayar Ini Tidak Mendukung...!!!'], 500);
        }else{
            return new JsonResponse(['message' => 'OK'], 200);
        }
    }
}
