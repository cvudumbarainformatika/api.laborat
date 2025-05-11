<?php

namespace App\Http\Controllers\Api\Simrs\Penjaminan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penjaminan\listcasmixrajal;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Klaim extends Controller
{
    public function getdataklaim()
    {
        $pelayanan = request('pelayanan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        if($pelayanan === '1')
        {
            $data = listcasmixrajal::select('listkirimcasmixRajal.noreg as noreg','listkirimcasmixRajal.norm as norm',
            'listkirimcasmixRajal.nosep as nosep','listkirimcasmixRajal.noka as noka',
            'listkirimcasmixRajal.norm as norm','listkirimcasmixRajal.nosep as nosep',
            'kepegx.pegawai.nama as dpjp','rs17.rs3 as tgl_kunjungan','rs17.rs8 as kodepoli',
            'rs15.rs2 as pasien',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
             'rs15.rs17 as kelamin',
             DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
             'rs9.rs2 as sistembayar',
            'rs19.rs2 as poli','klaim_trans_rajal.status_klaim as ket',
            DB::raw('\'rajal\' as layanan'))
            ->leftjoin('rs17', 'rs17.rs1', '=', 'listkirimcasmixRajal.noreg')
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9')
            ->leftjoin('klaim_trans_rajal', 'klaim_trans_rajal.noreg', '=', 'listkirimcasmixRajal.noreg')
            ->whereYear('rs17.rs3', $tahun )->whereMonth('rs17.rs3', $bulan)
            ->where('rs9.groups', '1')
            ->paginate(request('per_page'));
            return new JsonResponse($data);
        }else{
            $data = listcasmixrajal::select('listkirimcasmixRajal.noreg as noreg','listkirimcasmixRajal.norm as norm',
            'listkirimcasmixRajal.nosep as nosep','listkirimcasmixRajal.noka as noka',
            'listkirimcasmixRajal.norm as norm','listkirimcasmixRajal.nosep as nosep',
            'kepegx.pegawai.nama as dpjp','rs23.rs3 as tgl_kunjungan','rs23.rs5 as kodepoli',
            'rs15.rs2 as pasien',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
             'rs15.rs17 as kelamin',
             DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
             'rs9.rs2 as sistembayar',
            'rs19.rs2 as poli','klaim_trans_rajal.status_klaim as ket',
            DB::raw('\'ranap\' as layanan'))
            ->leftjoin('rs23', 'rs23.rs1', '=', 'listkirimcasmixRajal.noreg')
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs23.rs2')
            ->leftjoin('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs23.rs19')
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs23.rs10')
            ->leftjoin('klaim_trans_rajal', 'klaim_trans_rajal.noreg', '=', 'listkirimcasmixRajal.noreg')
            ->whereYear('rs23.rs3', $tahun )->whereMonth('rs23.rs3', $bulan)
            ->where('rs9.groups', '1')
            ->paginate(request('per_page'));
            return new JsonResponse($data);
        }
    }

    public function terimapasien(Request $request)
    {
        $cekx = KunjunganPoli::select('rs1', 'rs2', 'rs3','rs4','rs8', 'rs9','rs14', 'rs19','rs26 as tglpulang')->where('rs1', $request->noreg)->where('rs8','POL014')
        ->with([
            'anamnesis' => function($anamnesis){
                $anamnesis->with(['anamnesetambahan','anamnesebps','anamnesenips','datasimpeg'])->where('kdruang', 'POL014');
            },
            'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai',
            'permintaanperawatanjenazah',
            'triage' => function($triage) {
                $triage->select(
                'rs250.id','rs250.rs1 as noreg','rs250.rs1',
                'rs250.doa','rs250.rs6 as tanggal',
                'rs250.rs8 as suhu',
                'rs250.rs10 as pernapasan',
                'rs250.rs11 as nadi',
                'rs250.rs12 as tensi',
                'rs250.rs13 as bb',
                'rs250.rs21 as tb',
                'rs250.rs10 as pernapasanx',
                'rs250.sistole', 'rs250.meninggaldiluarrs','rs250.barulahirmeninggal',
                'rs250.diastole',
                'rs250.kesadarans as kesadaran','rs250.scorediastole','rs250.scoresistole','rs250.scorekesadaran','rs250.scorelochea','rs250.scorenadi','rs250.scorenyeri',
                'rs250.scorepernapasanx','rs250.scoreproteinurin','rs250.scorespo2','rs250.scoresuhu','rs250.totalscore','rs250.rs16 as kategoritriage','rs250.hasilprimarusurve',
                'rs250.hasilsecondsurve',
                'rs251.rs14 as eye',
                'rs251.rs15 as verbal',
                'rs251.rs16 as motorik',
                'rs250.spo2','rs250.gangguanperilaku','rs250.falsetriage',
                'rs251.flaghamil',
                'rs251.haidterakir as haid',
                'rs251.gravida',
                'rs251.partus',
                'rs251.abortus','rs251.nyeri','rs251.lochea','rs251.proteinurin',
                'rs251.rs7 as jalannafas','rs251.rs9 as pernapasan','rs251.rs19 as sirkulasi','rs251.rs20 as disability'
                )->leftjoin('rs251','rs250.rs1','rs251.rs1')->groupBy('id');
            },
            'penilaiananamnesis' => function($penilaiananamnesis){
                $penilaiananamnesis->select([
                    'id','rs1','rs1 as noreg',
                    'rs2 as norm','rs3 as tgl',
                    'barthel','norton','humpty_dumpty','morse_fall','ontario','user','kdruang','awal','group_nakes'
                   ])
                   ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes'])->where('kdruang','POL014');
            },
            'historyperkawinan',
            'historykehamilan',
            'anamnesekebidanan',
            'bankdarah',
            // 'peresepanobat' => function($peresepanobat){
            //     $peresepanobat->with(
            //         [
            //             'rincian' => function($rincian){
            //                 $rincian->with('mobat');
            //             },
            //         ]
            //     )->whereIn('flag', ['3','4'])
            //     ->where('ruangan','POL014');
            // },
            'msistembayar',
            'planheder' => function($planheder){
                $planheder->with([
                    'planranap' => function($planranap){
                        $planranap->with(
                            [
                                'ruangranap',
                                'dokumentransfer'
                            ]
                        );
                    },
                    'planrujukan',
                    'planpulang' => function($planpulang){
                        $planpulang->with(
                            [
                                'dokterpenangungjawabpulang' => function($dokterpenangungjawabpulang){
                                    $dokterpenangungjawabpulang->select('*')
                                    ->leftjoin('m_golruang', 'm_golruang.kode_gol','pegawai.golruang')
                                    ->leftjoin('m_jabatan','m_jabatan.kode_jabatan','pegawai.jabatan');
                                }
                            ]
                        );
                    }
                ]);
            },
            'ambulan' => function($ambulan) {
                $ambulan->with(
                    [
                        'tujuan',
                        'perawat',
                        'perawat2'
                    ]
                );
            },
            'laborats' => function ($t) {
                $t->with('details.pemeriksaanlab')->where('unit_pengirim', 'POL014')
                    ->orderBy('id', 'DESC');
            },
            'laboratold'=> function ($t) {
                $t->with('pemeriksaanlab')
                    ->orderBy('id', 'DESC')->where('rs23','POL014');
            },
            'radiologi' => function ($t) {
                $t->orderBy('id', 'DESC');
            },
            'penunjanglain' => function ($t) {
                $t->with('masterpenunjang')->orderBy('id', 'DESC');
            },
            'tindakan' => function ($t) {
                $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url','mpoli:rs1,rs2')
                    ->where('rs4','<>','T00075')
                    ->orderBy('id', 'DESC')->where('rs22','POL014');
            },
            'diagnosa' => function ($d) {
                $d->with('masterdiagnosa')->where('rs13','POL014');
            },
            'pemeriksaanfisik' => function ($a) {
                $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                    ->orderBy('id', 'DESC');
            },
            'ok' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'diagnosakeperawatan'=> function ($d) {
                $d->with('petugas:id,nama,satset_uuid','intervensi.masterintervensi');
            },
            'diagnosakebidanan' => function ($diag) {
                    $diag->with('intervensi.masterintervensi');
            },
            'pemeriksaanfisikpsikologidll' => function($pemeriksaanfisikpsikologidll){
                $pemeriksaanfisikpsikologidll->select('rs253.*','kepegx.pegawai.kdpegsimrs','kepegx.pegawai.nama')->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs253.user')
                ->with('pemerisaanpsikologidll')->where('kdruang','POL014');
            },
            'newapotekrajal' => function ($newapotekrajal) {
                $newapotekrajal->with([
                    'permintaanresep.mobat:kd_obat,nama_obat',
                    'permintaanracikan.mobat:kd_obat,nama_obat',
                ])
                    ->orderBy('id', 'DESC');
            },
            'tinjauanulang' => function($tinjauanulang){
                $tinjauanulang->select('peninjauan_ulang_igd.*','kepegx.pegawai.nama')->with([
                    'tinjauanulangnips',
                    'tinjauanulangbps'
                ])->leftjoin('kepegx.pegawai','kepegx.pegawai.kdpegsimrs','peninjauan_ulang_igd.user');
            },
            'konsuldokterspesialis' => function ($konsuldokterspesialis){
                $konsuldokterspesialis->with(
                    [
                        'tindakan' => function($tindakans){
                            $tindakans->with(
                                [
                                    'mastertindakan'
                                ]
                            );
                        },
                        'nakesminta'
                    ]
                )->where('kdruang', 'POL014');
            },
            'skalatransfer',
            'dokumenluar' => function ($neo) {
                $neo->with(['pegawai:id,nama']);
            },
            'pemberianobat' => function ($pemberianobat){
                $pemberianobat->with(
                    [
                        'mobat',
                        'datasimpeg'
                    ]);
            },
            'rencanaterapidokter'
        ])
        ->first();

        if ($cekx) {
            $flag = $cekx->rs19;

            if ($flag === '') {
                $cekx->rs19 = '2';
                $cekx->save();
            }

            return new JsonResponse($cekx, 200);
        } else {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 500);
        }
    }
}
