<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasi\MapingObat;
use App\Models\Simrs\Penunjang\Farmasi\StokReal;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal as FarmasinewStokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetNewStokController extends Controller
{

    public function setNewStok()
    {
        $mapingGudang = [
            ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
            ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
            ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
            ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
            ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
            ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
            ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        ];
        $gudBaru = ['05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];

        $mapingDep = ['GU0001', 'GU0002', 'RC0001', 'AP0002', 'AP0005', 'AP0001', 'AP0007'];
        $stok = StokReal::where('rs2', '>', 0)->get();
        $colStok = collect($stok);
        $mapingObat = MapingObat::with([
            'master:rs1,rs4',
            'stok' => function ($stok) use ($mapingDep) {
                $stok->where('rs2', '>', 0)
                    ->whereIn('rs4', $mapingDep);
            },
        ])->where('obatbaru', '<>', '')->get();
        $newStok = [];
        foreach ($mapingObat as $key) {
            foreach ($key['stok'] as $st) {
                $raw = collect($mapingGudang)
                    ->where('lama', $st['rs4'])
                    ->map(function ($it, $key) {
                        return $it['kode'] ?? null;
                    });
                /**
            Catatan:
            $anu dan $item ada karena key nya ($anu) dinamis, maka key nya harus dicari berdasarkan nilai objeck yang sekarang ($item)
            key nya tidak bisa langsung diambil dari $raw, karena $raw masih belum menjadi nilai dari object, maka nilai dari object harus di akses terlebih dahulu di $item
                 */
                $item = current((array)$raw); // value of current object
                $anu = key((array)$item); // key of the object value
                if ($item[$anu] === 'Gd-05010100') $nPen = 'G-KO';
                else if ($item[$anu] === 'Gd-03010100') $nPen = 'G-FO';
                else if ($item[$anu] === 'Gd-03010101') $nPen = 'D-FO';
                else if ($item[$anu] === 'Gd-04010102') $nPen = 'D-RI';
                else if ($item[$anu] === 'Gd-04010103') $nPen = 'D-OK';
                else if ($item[$anu] === 'Gd-05010101') $nPen = 'D-RJ';
                else if ($item[$anu] === 'Gd-02010104') $nPen = 'D-IGD';
                else  $nPen = 'NDF';
                $temp = [
                    'nopenerimaan' => '001/' . date('m/Y') . '/awal/' . $nPen,
                    'tglpenerimaan' => date('Y-m-d H:i:s'),
                    'kdobat' => $key['obatbaru'],
                    'jumlah' => $st['rs2'],
                    'kdruang' => $item[$anu],
                    'harga' => $key['master']['rs4'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $newStok[] = $temp;
            }
        }
        if (count($newStok) > 0) {
            FarmasinewStokreal::truncate();
            $data['ins'] = FarmasinewStokreal::insert($newStok);
        }

        $data['new stok'] = $newStok;
        $data['har'] = $this->cekHargaGud();

        return new JsonResponse($data);
    }
    public function cekHargaGud()
    {
        $gKo = 'Gd-05010100';
        $gFo = 'Gd-03010100';
        $dFo = 'Gd-03010101';
        $dep = ['Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
        $obKo = FarmasinewStokreal::select('kdobat')->where('kdruang', $gKo)->distinct()->get('kdobat');
        $obFo = FarmasinewStokreal::select('kdobat')->where('kdruang', $gFo)->distinct()->get('kdobat');
        $obDFo = FarmasinewStokreal::where('kdruang', $dFo)->whereNotIn('kdobat', $obFo)->distinct()->get();
        $obDep = FarmasinewStokreal::whereIn('kdruang', $dep)->whereNotIn('kdobat', $obKo)->distinct()->get();
        $stok = [];
        if (count($obDFo) > 0) {
            foreach ($obDFo as $key) {
                $temp['nopenerimaan'] = $key['nopenerimaan'];
                $temp['tglpenerimaan'] = $key['tglpenerimaan'];
                $temp['kdobat'] = $key['kdobat'];
                $temp['jumlah'] = 0;
                $temp['kdruang'] = 'Gd-03010100';
                $temp['harga'] = $key['harga'];
                $temp['flag'] = $key['flag'];
                $temp['tglexp'] = $key['tglexp'];
                $temp['nobatch'] = $key['nobatch'];
                $temp['nodistribusi'] = $key['nodistribusi'];
                $temp['created_at'] = date('Y-m-d H:i:s');
                $temp['updated_at'] = date('Y-m-d H:i:s');
                $stok[] = $temp;
            }
        }
        if (count($obDep) > 0) {
            foreach ($obDep as $key) {

                $temp['nopenerimaan'] = $key['nopenerimaan'];
                $temp['tglpenerimaan'] = $key['tglpenerimaan'];
                $temp['kdobat'] = $key['kdobat'];
                $temp['jumlah'] = 0;
                $temp['kdruang'] = 'Gd-05010100';
                $temp['harga'] = $key['harga'];
                $temp['flag'] = $key['flag'];
                $temp['tglexp'] = $key['tglexp'];
                $temp['nobatch'] = $key['nobatch'];
                $temp['nodistribusi'] = $key['nodistribusi'];
                $temp['created_at'] = date('Y-m-d H:i:s');
                $temp['updated_at'] = date('Y-m-d H:i:s');
                $stok[] = $temp;
            }
        }
        if (count($stok) > 0) {
            $data = FarmasinewStokreal::insert($stok);
        }
        return [
            'stok' => $stok,
            'data' => $data,
        ];
    }
}
