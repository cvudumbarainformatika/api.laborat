<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use App\Models\KunjunganPoli;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Poli;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelayananController extends Controller
{
    public function index()
    {
        $tempat_tidur = DB::select(
            "SELECT * FROM (
                    SELECT UPPER(rs24.rs2) AS ruang,COUNT(vBed.rs5) AS total,SUM(vBed.terisi) AS terisi,( COUNT(vBed.rs5) - SUM(vBed.terisi) ) AS sisa FROM (
                    SELECT rs5,IF(rs3='S',1,0) AS terisi FROM rs25 WHERE rs7<>'1' AND extra<>'1' AND rs5<>'-' AND rs8<>'1'
                    ) AS vBed,rs24
                    WHERE rs24.rs1=vBed.rs5 AND rs24.status<>'1' AND rs24.rs4<>'BR' GROUP BY vBed.rs5
                    UNION ALL
                    SELECT UPPER(ruang) AS ruang,COUNT(ruang) AS total,SUM(terisi) AS terisi,(COUNT(ruang)-SUM(terisi)) AS sisa FROM (
                        SELECT CONCAT('Ruang ',rs1) AS ruang,IF(rs3='S',1,0) AS terisi FROM rs25 WHERE rs7<>'1' AND extra<>'1' AND rs5='-' AND rs8<>'1' AND rs6<>'BR'
                        ) AS vBed GROUP BY ruang
                        ) AS vKamar ORDER BY ruang ASC"
        );


        $igd_harini = DB::select("
            select tanggalmasuk,noreg,norm,nama,alamat,kelamin,IF(thn<1,IF(bln<1,concat(hari,' hari'),concat(bln,' bln')),concat(thn,' thn')) as umur,
            poli,tipe,kodedokter,sistembayar,flagcovid from(select distinct rs17.rs1 as noreg,IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')/365)),floor((datediff(rs17.rs3,rs15.rs16)/365))) as thn,
            IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30),floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)) as bln,
            IF(rs15.rs16='1900-01-01',(datediff(rs17.rs3,'1970-01-01')-((floor((datediff(rs17.rs3,'1970-01-01')/365))*365)+(floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30)*30))),
            (datediff(rs17.rs3,rs15.rs16)-((floor((datediff(rs17.rs3,rs15.rs16)/365))*365)+(floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)*30)))) as hari,
            rs17.rs2 as norm,rs17.rs14 as kd_akun,rs15.rs2 as nama,rs15.rs3 as sapaan,rs15.rs4 as alamat,rs15.rs5 as kelurahan,
            rs15.rs6 as kecamatan,rs15.rs7 as rt,rs15.rs8 as rw,rs15.rs10 as propinsi,rs15.rs11 as kabupaten,rs15.rs16 as tgllahir,
            rs15.rs17 as kelamin,rs15.rs36 as normlama,rs15.rs37 as tmplahir,rs17.rs3 as tanggalmasuk,rs17.rs4 as penanggungjawab,
            rs17.rs6 as kodeasalrujukan,rs17.rs20 as asalpendaftaran,rs17.rs7 as namaperujuk,rs17.rs8 as kodepoli,rs19.rs2 as poli,
            rs17.rs18 as userid,rs17.rs19 as status,rs9.rs2 as sistembayar,IF(rs15.rs31>1,'Lama','Baru') as tipe,rs17.rs9 as kodedokter,'' as nosep,
            rs15.flag_covid as flagcovid
            from rs15,rs17,rs19,rs9
            where rs15.rs1=rs17.rs2 and rs17.rs8=rs19.rs1 and rs9.rs1=rs17.rs14
            and rs17.rs19='' and year(rs17.rs3)='" . date("Y") . "' and month(rs17.rs3)='" . date("m") . "' and dayofmonth(rs17.rs3)='" . date("d") . "'
            and rs17.rs8='POL014') as v_15_17 order by tanggalmasuk
        ");

        // $poli_hariinibelum = DB::select(
        //     "
        //         SELECT tanggalmasuk,noreg,norm,nama,alamat,kelamin,IF(thn<1,IF(bln<1,concat(hari,' hari'),concat(bln,' bln')),concat(thn,' thn')) as umur,
        //         poli,tipe,kodedokter,sistembayar,prmrj
        //         FROM(select distinct rs17.rs1 as noreg,
        //             IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')/365)),floor((datediff(rs17.rs3,rs15.rs16)/365))) as thn,
        //             IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30),
        //             floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)) as bln,
        //             IF(rs15.rs16='1900-01-01',(datediff(rs17.rs3,'1970-01-01')-((floor((datediff(rs17.rs3,'1970-01-01')/365))*365)+(floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30)*30))),
        //             (datediff(rs17.rs3,rs15.rs16)-((floor((datediff(rs17.rs3,rs15.rs16)/365))*365)+(floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)*30)))) as hari,
        //         rs17.rs2 as norm,rs17.rs14 as kd_akun,
        //          rs15.rs2 as nama,rs15.rs3 as sapaan,rs15.rs4 as alamat,rs15.rs5 as kelurahan,
        //         rs15.rs6 as kecamatan,rs15.rs7 as rt,rs15.rs8 as rw,rs15.rs10 as propinsi,rs15.rs11 as kabupaten,rs15.rs16 as tgllahir,
        //         rs15.rs17 as kelamin,rs15.rs36 as normlama,rs15.rs37 as tmplahir,rs17.rs3 as tanggalmasuk,rs17.rs4 as penanggungjawab,
        //         rs17.rs6 as kodeasalrujukan,rs17.rs20 as asalpendaftaran,rs17.rs7 as namaperujuk,rs17.rs8 as kodepoli,rs19.rs2 as poli,
        //         rs17.rs18 as userid,rs17.rs19 as status,rs9.rs2 as sistembayar,IF(rs15.rs31>1,'Lama','Baru') as tipe,rs17.rs9 as kodedokter,'' as nosep,'' as prmrj from rs15,rs17,rs19,rs9
        //         where rs15.rs1=rs17.rs2
        //         and rs17.rs8=rs19.rs1 and
        //         rs9.rs1=rs17.rs14
        //         and rs17.rs19=''
        //         and year(rs17.rs3)='" . date("Y") . "'
        //         and month(rs17.rs3)='" . date("m") . "' and
        //         dayofmonth(rs17.rs3)='" . date("d") . "'
        //         and rs17.rs8<>'POL014'
        //         and rs17.rs8<>'POL005' and
        //         rs17.rs8<>'POL025') as v_15_17 order by tanggalmasuk
        //     "
        // );

        $poli_hariinibelum = DB::table('rs17')
            // ->selectRaw('rs1 as noreg, rs3 as tanggal, rs2 as norm, rs8 as kd_poli, rs14 as kd_akun, rs19 as status')
            ->select('rs1', 'rs3', 'rs2', 'rs8', 'rs14', 'rs19')
            ->whereNotIn('rs8', ['POL014', 'POL005', 'POL025'])
            ->whereDate('rs3', Carbon::today())
            ->where('rs19', '=', '')
            ->join('rs15', 'rs17.rs2', '=', 'rs15.rs1') // JOIN DATA PASIEN
            ->join('rs19', 'rs17.rs8', '=', 'rs19.rs1') // JOIN DATA MASTER POLI
            ->join('rs9', 'rs17.rs14', '=', 'rs9.rs1') // JOIN DATA MASTER CARA BAYAR
            ->get();

        $poli_hariinisudah = DB::select(
            "select tanggalmasuk,noreg,norm,nama,alamat,kelamin,IF(thn<1,IF(bln<1,concat(hari,' hari'),concat(bln,' bln')),concat(thn,' thn')) as umur,
                poli,tipe,kodedokter,sistembayar,kondisiakhir from(select distinct rs17.rs1 as noreg,IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')/365)),floor((datediff(rs17.rs3,rs15.rs16)/365))) as thn,
                IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30),floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)) as bln,
                IF(rs15.rs16='1900-01-01',(datediff(rs17.rs3,'1970-01-01')-((floor((datediff(rs17.rs3,'1970-01-01')/365))*365)+(floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30)*30))),
                (datediff(rs17.rs3,rs15.rs16)-((floor((datediff(rs17.rs3,rs15.rs16)/365))*365)+(floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)*30)))) as hari,
                rs17.rs2 as norm,rs17.rs14 as kd_akun,rs15.rs2 as nama,rs15.rs3 as sapaan,rs15.rs4 as alamat,rs15.rs5 as kelurahan,
                rs15.rs6 as kecamatan,rs15.rs7 as rt,rs15.rs8 as rw,rs15.rs10 as propinsi,rs15.rs11 as kabupaten,rs15.rs16 as tgllahir,
                rs15.rs17 as kelamin,rs15.rs36 as normlama,rs15.rs37 as tmplahir,rs17.rs3 as tanggalmasuk,rs17.rs4 as penanggungjawab,
                rs17.rs6 as kodeasalrujukan,rs17.rs20 as asalpendaftaran,rs17.rs7 as namaperujuk,rs17.rs8 as kodepoli,rs19.rs2 as poli,
                rs17.rs18 as userid,rs17.rs19 as status,rs9.rs2 as sistembayar,IF(rs15.rs31>1,'Lama','Baru') as tipe,rs17.rs9 as kodedokter,if(rs141.rs5='',concat(rs141.rs4,' ',masterpoli.rs2),concat(rs141.rs4,' ',rs141.rs5)) as kondisiakhir,'' as nosep
                from rs15,rs17,rs19,rs9,rs141,rs19 as masterpoli
                where rs15.rs1=rs17.rs2 and
                rs17.rs8=rs19.rs1 and
                rs9.rs1=rs17.rs14 and
                rs141.rs1=rs17.rs1 and
                masterpoli.rs1=rs141.rs3
                and rs17.rs19='1' and year(rs17.rs3)='" . date("Y") . "' and month(rs17.rs3)='" . date("m") . "' and dayofmonth(rs17.rs3)='" . date("d") . "'
                and rs17.rs8<>'POL014' and rs17.rs8<>'POL005' and rs17.rs8<>'POL025') as v_15_17 order by tanggalmasuk"
        );

        // $poli_hariini = KunjunganPoli::selectRaw('rs1, rs3, rs8, rs19 as sudah')
        //     ->where('rs19', '=', '1')
        //     ->whereDate('rs3', Carbon::today())
        //     ->whereNotIn('rs8', ['POL014', 'POL005', 'POL025'])
        //     ->whereHas('poli', function ($x) {
        //         $x->where('rs5', '=', '1');
        //     })
        //     ->orderBy('rs3', 'asc')->groupBy('rs1')
        //     ->get();
        // $poli_hariini = Poli::where('rs5', '=', '1')
        //     ->get();

        $data = array(
            "tempat_tidur" => $tempat_tidur,
            'igd_harini' => $igd_harini,
            'poli_hariinibelum' => $poli_hariinibelum,
            'poli_hariinisudah' => $poli_hariinisudah,
        );
        return response()->json($data);
    }
}
