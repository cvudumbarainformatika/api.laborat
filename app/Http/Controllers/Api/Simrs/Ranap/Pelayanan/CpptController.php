<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\KeluhanNyeri;
use App\Models\Simrs\Ranap\Pelayanan\Cppt;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanSambung;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CpptController extends Controller
{
    public function list()
    {
        $data = self::getdata(request('noreg'), null);
        return new JsonResponse($data);
    }

    public static function getdata($noreg, $id){
      $data = Cppt::query()
      ->where(function($query) use ($noreg, $id){
        if ($id !==null) {
          $query->where('id', $id);
        } else {
          $query->where('noreg', $noreg);
        }
      })
      ->with([
        'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
        'anamnesis'=> function($query){
          $query->select(
            'rs209.id','rs209.rs1','rs209.rs1 as noreg',
            'rs209.rs2 as norm',
            'rs209.rs3 as tgl',
            'rs209.rs4 as keluhanUtama',
            'rs209.riwayatpenyakit',
            'rs209.riwayatalergi',
            'rs209.keteranganalergi',
            'rs209.riwayatpengobatan',
            'rs209.riwayatpenyakitsekarang',
            'rs209.riwayatpenyakitkeluarga',
            'rs209.riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya',
            'rs209.kdruang',
            'rs209.awal',
            'rs209.user',
          )->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes',
          'keluhannyeri',
          'skreeninggizi',
          'neonatal',
          'pediatrik',
          'kebidanan'
          ]);
          // ->where('awal','!=', '1');
        },
        'pemeriksaan'=> function($query){
          $query->select([
            'rs253.id','rs253.rs1','rs253.rs1 as noreg',
          'rs253.rs2 as norm',
          'rs253.rs3 as tgl',
          'rs253.rs4 as ruang',
          'rs253.pernapasan as pernapasanigd',
          'rs253.nadi as nadiigd',
          'rs253.tensi as tensiigd',
          'rs253.beratbadan',
          'rs253.tinggibadan',
          'rs253.kdruang',
          'rs253.user',
          'rs253.awal',
          
          'sambung.keadaanUmum',
          'sambung.bb' ,
          'sambung.tb' ,
          'sambung.nadi' ,
          'sambung.suhu' ,
          'sambung.sistole' ,
          'sambung.diastole' ,
          'sambung.pernapasan' ,
          'sambung.spo' ,
          'sambung.tkKesadaran' ,
          'sambung.tkKesadaranKet' ,
          'sambung.sosial' ,
          'sambung.spiritual' ,
          'sambung.statusPsikologis' ,
          'sambung.ansuransi' ,
          'sambung.edukasi',
          'sambung.ketEdukasi',
          'sambung.penyebabSakit' ,
          'sambung.komunikasi' ,
          'sambung.makananPokok' ,
          'sambung.makananPokokLain' ,
          'sambung.pantanganMkanan' ,
          
          'pegawai.nama as petugas',
          'pegawai.kdgroupnakes as nakes',
          ])
          ->leftJoin('rs253_sambung as sambung', 'rs253.id', '=', 'sambung.rs253_id')
          ->leftJoin('kepegx.pegawai as pegawai', 'rs253.user', '=', 'pegawai.kdpegsimrs')
          // ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes',
          //   'neonatal',
          //   'pediatrik',
          //   'kebidanan',
          //   //  'penilaian'
          //   ])
            ->groupBy('rs253.id')
          ;
          // ->where('awal','!=', '1');
        },
        'penilaian'=> function($query){
          $query->select([
            'id','rs1','rs1 as noreg',
            'rs2 as norm','rs3 as tgl',
            'barthel','norton','humpty_dumpty','morse_fall','ontario','user','kdruang','awal','group_nakes'
          ]);
          // ->where('awal','!=', '1');
        },
        'cpptlama',

        ])
      ->orderBy('tgl', 'DESC')
      ->get();
      return $data;
    }

    
    public function saveCppt(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;
      $nakes = $user->kdgroupnakes;

       $anamnesis = AnamnesisController::storeAnamnesis((object) $request->anamnesis);
      //  return $anamnesis;
       $anamnesisId = null;
       if ($anamnesis['success']===true) {
        $anamnesisId = $anamnesis['idAnamnesis'];
       }

       $pemeriksaanUmum = PemeriksaanUmumController::store((object) $request->pemeriksaan);
      //  return $pemeriksaanUmum;
       $pemeriksaanUmumId = null;
       if ($pemeriksaanUmum['success']===true) {
        $pemeriksaanUmumId = $pemeriksaanUmum['idPemeriksaan'];
       }

       $penilaian = PemeriksaanPenilaianController::store((object) $request->penilaian);
       $penilaianId = null;
       if ($penilaian['success']===true) {
        $penilaianId = $penilaian['idPenilaian'];
       }



       $cppt = Cppt::create([
        'noreg' => $request->noreg,
        'norm' => $request->norm,
        'tgl' => date('Y-m-d H:i:s'),
        'rs209_id' => $anamnesisId,
        'rs253_id' => $pemeriksaanUmumId,
        'penilaian_id' => $penilaianId,
        'asessment'=> $request->form['asessment'],
        'plann'=> $request->form['plann'],
        'instruksi'=> $request->form['instruksi'],
        'kdruang' => $request->kdruang,
        'user' => $kdpegsimrs,
        'nakes'=> $nakes,

       ]);

       if (!$cppt) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Gagal menyimpan data'
        ]);
       }


       $result = self::getdata($request->noreg, null);
       return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $result
       ]);
    }


    public function editCpptAnamnesis(Request $request)
    {
      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

      DB::beginTransaction();
      try {
        
        $data = Anamnesis::find($request->id);

        $data->rs4 = $request->form['keluhanUtama'] ?? '';
        $data->user  = $kdpegsimrs;
        $data->save();

        $skorNyeri = 0;
        $ketNyeri = null;
        if ($request->formKebidanan ===null && $request->formNeoNatal=== null && $request->formPediatrik=== null) {
          $skorNyeri = $request->form['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->form['keluhannyeri']['ket'] ?? null;
          
        }
        else if ($request->formKebidanan !==null) {
          $skorNyeri = $request->formKebidanan['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formKebidanan['keluhannyeri']['ket'] ?? null;
        }
        else if ($request->formNeoNatal !==null) {
          $skorNyeri = $request->formNeoNatal['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formNeoNatal['keluhannyeri']['ket'] ?? null;
        }
        else if ($request->formPediatrik !==null) {
          $skorNyeri = $request->formPediatrik['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formPediatrik['keluhannyeri']['ket'] ?? null;
        }

        KeluhanNyeri::where('rs209_id', $data->id)->update(
          [
            'dewasa'=> $request->form['keluhannyeri'] ?? null, // array
            'kebidanan'=> $request->formKebidanan['keluhannyeri'] ?? null, // array
            'neonatal'=> $request->formNeoNatal['keluhannyeri'] ?? null, // array
            'pediatrik'=> $request->formPediatrik['keluhannyeri'] ?? null, // array
            'skor'=> $skorNyeri,
            'keluhan'=> $ketNyeri,
            'user_input'=> $kdpegsimrs,
            'group_nakes' => $user->kdgroupnakes
          ]
        );

        DB::commit();
        return new JsonResponse([
          'success' => true,
          'message' => 'success',
          'result' => self::getdata(null, $request->id_cppt)
        ]);
      } catch (\Exception $th) {
        DB::rollBack();
        $data = [
          'success' => false,
          'message' => 'GAGAL DISIMPAN',
          'result' => $th->getMessage(),
        ];
      }
    }


    public function editCpptPemeriksaan(Request $request)
    {
      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

      // return $request->all();

      DB::beginTransaction();
      try {
        
        $data = PemeriksaanUmum::find($request->id);

        $data->pernapasan = $request->form['pernapasan'] ?? '';
        $data->nadi  = $request->form['nadi'] ?? '';
        $data->tensi  = $request->form['sistole']. '/' . $request->form['diastole'];
        $data->beratbadan  = $request->form['bb'];
        $data->tinggibadan  = $request->form['tb'];
        $data->save();

        PemeriksaanSambung::where('rs253_id', $data->id)->update(
          [
            'keadaanUmum' => $request->form['keadaanUmum'] ?? '',
            'bb' => $request->form['bb'],
            'tb' => $request->form['tb'],
            'nadi' => $request->form['nadi'],
            'suhu' => $request->form['suhu'],
            'sistole' => $request->form['sistole'],
            'diastole' => $request->form['diastole'],
            'pernapasan' => $request->form['pernapasan'],
            'spo' => $request->form['spo'],
            'tkKesadaran' => $request->form['tkKesadaran'],
            'tkKesadaranKet' => $request->form['tkKesadaranKet'],
            'sosial' => $request->form['sosial'],
            'spiritual' => $request->form['spiritual'],
            'statusPsikologis' => $request->form['statusPsikologis'],
            'ansuransi' => $request->form['ansuransi'],
            'edukasi' => $request->form['edukasi'],
            'ketEdukasi' => $request->form['ketEdukasi'],
            'penyebabSakit' => $request->form['penyebabSakit'],
            'komunikasi' => $request->form['komunikasi'],
            'makananPokok' => $request->form['makananPokok'],
            'makananPokokLain' => $request->form['makananPokokLain'],
            'pantanganMkanan' => $request->form['pantanganMkanan'],
          ]
        );


        //penilaian

        Penilaian::where('id', $request->penilaian['id'])->update(
          [
            'barthel' => $request->penilaian['barthel'],
            'norton' => $request->penilaian['norton'],
            'humpty_dumpty' => $request->penilaian['humpty_dumpty'],
            'morse_fall' => $request->penilaian['morse_fall'],
            'ontario' => $request->penilaian['ontario'],
            
            
            'user'  => $kdpegsimrs,
            'group_nakes'  => $user->kdgroupnakes,
          ]
      );

        DB::commit();
        return new JsonResponse([
          'success' => true,
          'message' => 'success',
          'result' => self::getdata(null, $request->id_cppt)
        ]);
      } catch (\Exception $th) {
        DB::rollBack();
        $data = [
          'success' => false,
          'message' => 'GAGAL DISIMPAN',
          'result' => $th->getMessage(),
        ];
      }

    }


    
}
