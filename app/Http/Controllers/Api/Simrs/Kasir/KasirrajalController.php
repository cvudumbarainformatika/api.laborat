<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Karcis;
use App\Models\Simrs\Kasir\Kwitansidetail;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasirrajalController extends Controller
{
    public function kunjunganpoli()
    {

        $tgl = request('tgl');
        $daftarkunjunganpasienbpjs = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groupssistembayar',
            'rs15.rs3 as sapaan',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs222.rs8 as sep',
            'rs17.rs19 as status'
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->whereDate('rs17.rs3', $tgl)
            //->where('rs19.rs4', '=', 'Poliklinik')
            ->where('rs17.rs8', '!=', 'POL014')
            ->where('rs9.rs9', '=', 'UMUM')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->where('rs17.rs8', 'LIKE', '%' . request('kdpoli') . '%')
            ->orderby('rs17.rs3', 'DESC')
            ->paginate(request('per_page'));

        return new JsonResponse($daftarkunjunganpasienbpjs);
    }

    public function tagihanpergolongan()
    {
        $layanan = ['RM#', 'K1#', 'K2#', 'K3#', 'K4#', 'K5#', 'K6#'];
        $noreg = request('noreg');
        if (request('golongan') == 'karcis') {
            $karcis = Pembayaran::where('rs1', $noreg)->whereIn('rs3', $layanan)->get();
            $tagihanpergolongan = $karcis->map(function ($karcisx, $kunci) {
                return [
                    'namatindakan' => $karcisx->rs6,
                    'subtotal' => $karcisx->rs7 + $karcisx->rs11,
                ];
            });
            $karcis = $karcis->sum('subtotal');
            return new JsonResponse(
                [
                    'Pelayanan' => $tagihanpergolongan,
                    'Subtotal' => $karcis
                ]
            );
        } elseif (request('golongan') == 'konsulantarpoli') {
            $konsul = Pembayaran::where('rs1', $noreg)->where('rs3', 'K3#')->get();
            $konsulantarpoli = $konsul->map(function ($konsul, $kunci) {
                return [
                    'namatindakan' => $konsul->rs6,
                    'subtotal' => $konsul->rs7 + $konsul->rs11,
                ];
            });
            $konsul = $konsul->sum('subtotal');
            return new JsonResponse(
                [
                    'Pelayanan' => $konsulantarpoli,
                    'Subtotal' => $konsul
                ]
            );
        }
    }

    public function pembayaran(Request $request)
    {
        if (str_contains($request->groupssistembayar, '1')) {
            return 'wew';
        } else {
            if ($request->jenispembayaran == 'Karcis') {
                $cek = Karcis::where('noreg', $request->noreg)->where('batal', '')->count();
                if ($cek > 0) {
                    return new JsonResponse(['message' => 'Maaf Karcis Sudah tercetak...!!!'], 500);
                }
                DB::select('call karcisrj(@nomor)');
                $x = DB::table('rs1')->select('karcisrj')->get();
                $wew = $x[0]->karcisrj;
                $nokarcis = FormatingHelper::karcisrj($wew, 'KRJ');

                $simpankarcis = Karcis::firstOrCreate(
                    [

                        'nokarcis' => $nokarcis
                    ],
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'tgl' => $request->tgl_kunjungan,
                        'nama' => $request->nama,
                        'sapaan' => $request->sapaan,
                        'kelamin' => $request->kelamin,
                        'poli' => $request->poli,
                        'sistembayar' => $request->sistembayar,
                        'total' => $request->total,
                        'rinci' => $request->rinci,
                        'tglx' => date('Y-m-d H:i:s'),
                        'users' => auth()->user()->pegawai_id
                    ]
                );
                if (!$simpankarcis) {
                    return new JsonResponse(['message' => 'Maaf Data Gagal Disimpan'], 500);
                }

                $x = ['RM#', 'K2#', 'K1#', 'K3#', 'K4#', 'K5#', 'K6#'];
                $cariid = Pembayaran::select('id', 'rs6', DB::raw('rs7+rs11 as jml'))->whereIn('rs3', $x)->where('rs1', $request->noreg)->get();
                foreach ($cariid as $val) {
                    //$wew[] = $val['jml'];
                    $simpandetail = Kwitansidetail::create(
                        [
                            'no_pembayaran' => '-',
                            'no_kwitansi' => $nokarcis,
                            'id_trans' => $val['id'],
                            'noreg' => $request->noreg,
                            'pelayanan' => 'RAJAL',
                            'jenis' => $val['rs6'],
                            'unit' => $request->kodepoli,
                            'jml' => $val['jml']
                        ]
                    );
                }
                if (!$simpandetail) {
                    return new JsonResponse(['message' => 'Maaf Data Gagal Disimpan'], 500);
                }
                return new JsonResponse(
                    [
                        'message' => 'Data Berhasil Disimpan',
                        'result' => $simpankarcis
                    ],
                    200
                );
            }
            return 'UMUM';
        }
    }
}
