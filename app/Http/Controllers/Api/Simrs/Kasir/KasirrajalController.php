<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
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
            ->with([
                'laborats' => function ($t) {
                    $t->with('details.pemeriksaanlab')
                        ->orderBy('id', 'DESC');
                },
                'radiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                },
                'penunjanglain' => function ($t) {
                    $t->with('masterpenunjang')->orderBy('id', 'DESC');
                },
                'tindakan' => function ($t) {
                    $t->with('mastertindakan:rs1,rs2')
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($d) {
                    $d->with('masterdiagnosa');
                },
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['anatomys', 'detailgambars'])
                        ->orderBy('id', 'DESC');
                }
            ])
            ->orderby('rs17.rs3', 'DESC')
            ->paginate(request('per_page'));

        return new JsonResponse($daftarkunjunganpasienbpjs);
    }

    public function tagihanpergolongan()
    {
        $layanan = ['RM#', 'K1#', 'K2#'];
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
}
