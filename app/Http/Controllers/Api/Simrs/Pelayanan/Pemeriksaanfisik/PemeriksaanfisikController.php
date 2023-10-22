<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisikdetail;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisiksubdetail;
use App\Models\Simrs\Pemeriksaanfisik\Simpangambarpemeriksaanfisik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PemeriksaanfisikController extends Controller
{
    public function simpan(Request $request)
    {

        $noreg = $request->noreg;
        $norm = $request->norm;
        $simpanperiksaan = Pemeriksaanfisik::create(
            [
                'rs1' => $noreg,
                'rs2' => $norm,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->denyutjantung,
                'tingkatkesadaran' => $request->tingkatkesadaran,
                'pernapasan' => $request->pernapasan,
                'sistole' => $request->sistole,
                'diastole' => $request->diastole,
                'suhutubuh' => $request->suhutubuh,
                'statuspsikologis' => $request->statuspsikologis,
                'sosialekonomi' => $request->sosialekonomi,
                'spiritual' => $request->spiritual,
                'user'  => auth()->user()->pegawai_id,
                'ruangan' => $request->spiritual,
                'scorenyeri' => $request->scorenyeris,
                'keteranganscorenyeri' => $request->keteranganscorenyeri,
            ]
        );

        if (!$simpanperiksaan) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }

        $data = $request->anatomys;
        foreach ($data as $key => $value) {
            $simpanpemeriksaandetail = Pemeriksaanfisikdetail::create(
                [
                    'rs236_id' => $simpanperiksaan->id,
                    'noreg' => $noreg,
                    'norm' => $norm,
                    'tgl' => date('Y-m-d H:i:s'),
                    'nama' => $value['nama'],
                    'keterangan' => $value['ket'],
                    'user'  => auth()->user()->pegawai_id,
                ]
            );
        };

        $data = $request->details;
        foreach ($data as $key => $value) {
            $simpanpemeriksaandetail = Pemeriksaanfisiksubdetail::create(
                [
                    'rs236_id' => $simpanperiksaan->id,
                    'noreg' => $noreg,
                    'norm' => $norm,
                    'tgl' => date('Y-m-d H:i:s'),
                    'anatomy' => $value['anatomy'],
                    'ket' => $value['ket'],
                    'ketebalan' => $value['ketebalan'],
                    'noreg' => $value['noreg'],
                    'norm' => $value['norm'],
                    'panjang' => $value['panjang'],
                    'penanda' => $value['penanda'],
                    'templategambar' => $value['templategambar'],
                    'templateindex' => $value['templateindex'],
                    'templatemenu' => $value['templatemenu'],
                    'warna' => $value['warna'],
                    'templateindex' => $value['templateindex'],
                    'x' => $value['x'],
                    'y' => $value['y'],
                    'user'  => auth()->user()->pegawai_id,
                ]
            );
        };

        $pemeriksaan = $simpanperiksaan->load(['anatomys', 'detailgambars']);
        return new JsonResponse(
            [
                'message' => 'BERHASIL DISIMPAN',
                'result' => $pemeriksaan
            ],
            200
        );
    }

    public function hapuspemeriksaanfisik(Request $request)
    {
        $cari = Pemeriksaanfisik::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }

        Pemeriksaanfisikdetail::where('rs236_id', $request->id)->delete();
        Pemeriksaanfisiksubdetail::where('rs236_id', $request->id)->delete();
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function simpangambar(Request $request)
    {
        $image = $request->image;

        $name = date('YmdHis');
        $noreg = str_replace('/', '-', $request->noreg);
        $folderPath = "pemeriksaan_fisik/" . $noreg . '/';

        $image_parts = explode(";base64,", $image);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . $name . '.' . $image_type;

        $imageName = $name . '.' . $image_type;
        // Storage::delete('public/pemeriksaan_fisik/' . $noreg . '/' . $imageName);
        $wew = Storage::disk('public')->put('pemeriksaan_fisik/' . $noreg . '/' . $imageName, $image_base64);

        $simpangambar = Simpangambarpemeriksaanfisik::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'keterangan' => $request->keterangan,
                'gambar' => $file,
            ]
        );

        return new JsonResponse(
            [
                'message' => 'BERHASIL DISIMPAN',
                'result' => $simpangambar
            ],
            200
        );
    }

    public function hapusgambar(Request $request)
    {
        $filename = $request->nama;
        $cari = Simpangambarpemeriksaanfisik::where('gambar', $filename)->first();
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        Storage::delete('public/' . $filename);
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
