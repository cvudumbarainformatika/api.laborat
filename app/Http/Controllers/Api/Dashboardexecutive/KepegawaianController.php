<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KepegawaianController extends Controller
{
    public function index()
    {
        $kategori_pegawai = DB::connection('kepex')->select(
            "select p.kategoripegawai kode_kat,namakategoripeg,count(nip) jumlah
		            from pegawai p left join m_kategori_peg k on p.kategoripegawai=k.kodekategoripeg
                    where aktif='AKTIF' and jenispegawai!='Struktural' group by kategoripegawai order by k.kodekategoripeg"
        );

        $status_pegawai = DB::connection('kepex')->select(
            "select flag status,jp.jenispegawai,(select count(nip) from pegawai where flag=status and kelamin='Laki-Laki' and aktif='AKTIF') l,
            (Select count(nip) from pegawai where flag=status and kelamin='Perempuan' and aktif='AKTIF') p,
            count(nip) jumlah from pegawai p,m_jenispegawai jp where p.flag=jp.kode_jenis and aktif='AKTIF' group by flag"
        );

        $golongan = DB::connection('kepex')->select("
            select p.golruang kode_gol,g.golruang,keterangan,count(nip) jumlah
		    from pegawai p left join m_golruang g on p.golruang=g.kode_gol
            where aktif='AKTIF' and (flag='P01' or flag='P04') group by golruang order by golruang desc
        ");

        $data = array(
            'kategori_pegawai' => $kategori_pegawai,
            'status_pegawai' => $status_pegawai,
            'golongan' => $golongan,
        );
        return response()->json($data);
    }
}
