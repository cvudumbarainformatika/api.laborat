<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Helpers\FormatingHelper;
use App\Helpers\TarifHelper;
use App\Http\Controllers\Controller;
use App\Models\SerahTerima;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RuanganController extends Controller
{
    public function listruanganranap()
    {
        // $list = Mruangranap::select('groups', 'groups_nama')
        //     ->groupby('groups')
        //     ->where('hiddens', '')
        //     ->get();
        $list = Cache::remember('ruanganranap', now()->addDays(7), function () {
            return Mruangranap::select('groups', 'groups_nama')
            ->groupby('groups')
            ->where('hiddens', '')
            ->get();
        });
        return new JsonResponse($list);
    }
    public function ruanganranap()
    {
        $ruangan = DB::table('v_15_23')->select('*')->where('noreg', '=', request('noreg'))
        ->orderBy('noreg', 'desc')
        ->limit(10)->get();

        $tarip = TarifHelper::ruang(request('noreg'));
        $data = [
            'ruangan' => $ruangan,
            'tarif' => $tarip
        ];
        return new JsonResponse($data);
    }


    public function simpanMutasi(Request $request)
    {
        

        $kd_kelas = $request->kd_kelas;
        $ruang    = $request->ruang;
        $ruanglm    = $request->ruanglm;
        $noreg    = $request->noreg;

        if ($ruanglm == $ruang) {
            return response()->json([
                'message' => 'Maaf, asal dan tujuan ruangan tidak boleh sama.'
            ], 500);
        }

        // format tanggal
        $tanggal = $request->tanggal.' '.date('H:i:s');

        // cek tgl masuk mutasi
        $rs_tglmasuk = DB::table('rs44')
            ->where('rs1', $noreg)
            ->orderBy('rs2', 'desc')
            ->value('rs2');

        if ($rs_tglmasuk) {
            $tglmasuk_mutasi = $rs_tglmasuk;
        } else {
            $tglmasuk_mutasi = DB::table('rs23')
                ->where('rs1', $noreg)
                ->value('rs3');
        }

        // return response()->json($tglmasuk_mutasi);

        $user = FormatingHelper::session_user();
        // insert ke rs44
        DB::table('rs44')->insert([
            'rs1'       => $noreg,
            'rs2'       => $tanggal,
            'rs3'       => $request->ruanglm,
            'rs4'       => $request->kamarlm,
            'rs5'       => $request->bedlm,
            'rs6'       => $request->hargalm ?? 0, // ini belum
            'rs7'       => '',
            'rs8'       => '',
            'rs9'       => $request->kd_mutasi,
            'rs10'      => $ruang,
            'rs11'      => $request->kamar,
            'rs12'      => $request->nobed,
            'rs13'      => $request->biaya_kamar ?? 0, // ini belum
            'rs14'      => $request->biaya_dokter2 ?? 0,
            'rs15'      => '',
            'rs16'      => $user['kodesimrs'],
            'tglmasuk'  => $tglmasuk_mutasi,
        ]);

        // cek titipan
        $titipan = DB::table('rs23')->where('rs1', $noreg)->value('titipan');

        if ($titipan) {
            $groups_titipan = DB::table('rs24')
                ->where('rs1', $titipan)
                ->distinct()
                ->value('groups');

            DB::table('rs25')
                ->where('rs5', $titipan)
                ->where('rs1', $request->kamarlm)
                ->where('rs2', $request->bedlm)
                ->update(['rs3' => 'A', 'rs4' => 'V']);

            DB::table('rs25')
                ->where('rs6', $groups_titipan)
                ->where('rs1', $request->kamarlm)
                ->where('rs2', $request->bedlm)
                ->where('rs5', '-')
                ->update(['rs3' => 'A', 'rs4' => 'V']);
        } else {
            $groups = DB::table('rs24')
                ->where('rs1', $ruanglm)
                ->distinct()
                ->value('groups');

            $groupsx = DB::table('rs24')
                ->where('rs1', $ruang)
                ->distinct()
                ->value('groups');

            DB::table('rs25')
                ->where('rs5', $ruanglm)
                ->where('rs1', $request->kamarlm)
                ->where('rs2', $request->bedlm)
                ->update(['rs3' => 'A', 'rs4' => 'V']);

            DB::table('rs25')
                ->where('rs5', $ruang)
                ->where('rs1', $request->kamar)
                ->where('rs2', $request->nobed)
                ->update(['rs3' => 'S', 'rs4' => 'N']);

            DB::table('rs25')
                ->where('rs6', $groups)
                ->where('rs1', $request->kamarlm)
                ->where('rs2', $request->bedlm)
                ->where('rs5', '-')
                ->update(['rs3' => 'A', 'rs4' => 'V']);

            DB::table('rs25')
                ->where('rs6', $groupsx)
                ->where('rs1', $request->kamar)
                ->where('rs2', $request->nobed)
                ->where('rs5', '-')
                ->update(['rs3' => 'S', 'rs4' => 'N']);
        }

        // update rs23
        DB::table('rs23')
            ->where('rs1', $noreg)
            ->update([
                'rs5'   => $ruang,
                'rs6'   => $request->kamar,
                'rs7'   => $request->nobed,
                'rs31'  => $ruanglm,
                'rs36'  => $tanggal,
                'rs22'  => 1, // status belum diterima
                'titipan' => '',
            ]);
        
        // input dokumen serah terima

        self::serahterima($request);

        return response()->json(['message' => 'OK']);
    }

    /**
     * history mutasi
     */
    public function historyMutasi()
    {
        $data = DB::table('rs44')
            ->leftJoin('rs24 as lm', 'lm.rs1', '=', 'rs44.rs3')
            ->leftJoin('rs24 as br', 'br.rs1', '=', 'rs44.rs10')
            ->leftJoin('rs45', 'rs45.rs1', '=', 'rs44.rs9')
            ->select(
                'rs44.*','rs44.rs3 as kd_ruanglm', 'rs44.rs4 as kamarlm', 'rs44.rs5 as no_bedlm', 'rs44.rs10 as kd_ruang','rs44.rs11 as kamar','rs44.rs12 as no_bed',
                'lm.rs2 as nm_ruanglm',
                'br.rs2 as nm_ruang',
                'rs45.rs2 as alasan'
                )
            
            ->where('rs44.rs1', request('noreg'))
            ->orderBy('rs44.id', 'desc')
            ->get();

        return response()->json($data);
    }
    /**
     * document serah terima
     */
    public static function  serahterima($request)
    {
        // insert document serah (penyerahan pasien)
        $userSerah = FormatingHelper::session_user();
        $data = SerahTerima::create([
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'dari' => $request->ruanglm,
            'ke' => $request->ruang,
            'derajatPasien' => $request->derajatPasien,
            'tensi' => $request->tensi,
            'nadi' => $request->nadi,
            'suhu' => $request->suhu,
            'rr' => $request->rr,
            'spo2' => $request->spo2,
            'terapis' => $request->terapis,
            'plann' => $request->plann,
            'ro' => $request->ro,
            'lab' => $request->lab,
            'ecg' => $request->ecg,
            'lainlain' => $request->lainlain,
            'kelengkapan' => $request->kelengkapan,
            'user_serah' => $userSerah['kodesimrs'],
        ]);
    }

    public function updateSerahTerima(Request $request)
    {
       $user = FormatingHelper::session_user();
       $data = SerahTerima::find($request->id);
       if (!$data) {
           return new JsonResponse([
               'message' => 'Data Tidak Ditemukan'
           ], 500);
       }
       $data->update([
            'tensi_trm' => $request->tensi_trm,
            'nadi_trm' => $request->nadi_trm,
            'suhu_trm' => $request->suhu_trm,
            'rr_trm' => $request->rr_trm,
            'spo2_trm' => $request->spo2_trm,
            'user_trm' => $user['kodesimrs'],
       ]);

       // update rs23
        DB::table('rs23')
            ->where('rs1', $request->noreg)
            ->update([
                // 'rs5'   => $ruang,
                // 'rs6'   => $request->kamar,
                // 'rs7'   => $request->nobed,
                // 'rs31'  => $ruanglm,
                // 'rs36'  => $tanggal,
                'rs22'  => '', // status belum diterima
                // 'titipan' => '',
            ]);
        
       return response()->json(['message' => 'OK', 'data' => $data]);
    }

}
