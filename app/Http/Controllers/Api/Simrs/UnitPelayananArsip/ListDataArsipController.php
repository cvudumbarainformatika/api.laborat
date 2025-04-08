<?php

namespace App\Http\Controllers\Api\Simrs\UnitPelayananArsip;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\UnitPengelolahArsip\Dataarsip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ListDataArsipController extends Controller
{
    public function listdataarsip()
    {
        if(request('bidangbagian') === '' || request('bidangbagian') === null)
        {
            $bidangbagian = request('bidangbagian');
        }else{
            $bidangbagian = ['1.2.4.1'];
        }
        $data = Dataarsip::select('data_arsip.*',
        'master_kode.kode as kodeklasifikasi','master_kode.nama as namakelasifikasi','master_lokasi.nama_lokasi','master_media.nama_media')
        ->join('master_kode','data_arsip.kode','master_kode.kode')
        ->join('master_lokasi','data_arsip.lokasi','master_lokasi.id')
        ->join('master_media','data_arsip.media','master_media.id')
        ->where(function ($query) {
            $query->where('data_arsip.noarsip', 'LIKE', '%' . request('q') . '%')
                ->orWhere('data_arsip.uraian', 'LIKE', '%' . request('q') . '%')
                ->orWhere('data_arsip.nobox', 'LIKE', '%' . request('q') . '%')
                ->orWhere('master_kode.kode', 'LIKE', '%' . request('q') . '%')
                ->orWhere('master_kode.nama', 'LIKE', '%' . request('q') . '%');
        })
        ->whereIn('data_arsip.unit_pengolah', $bidangbagian)
        ->paginate(request('per_page'));
       return new JsonResponse($data);
    }

    public static function getlistdataarsipbynoarsip($noarsip)
    {

        $data = Dataarsip::select('data_arsip.*','master_kode.kode as kodeklasifikasi','master_kode.nama as namakelasifikasi','master_lokasi.nama_lokasi','master_media.nama_media')
        ->join('master_kode','data_arsip.kode','master_kode.kode')
        ->join('master_lokasi','data_arsip.lokasi','master_lokasi.id')
        ->join('master_media','data_arsip.media','master_media.id')
        ->where('data_arsip.flaging', '1')
        ->where('data_arsip.noarsip', $noarsip)
        ->get();
        return $data;
    }

    public function simpanarsip(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kdpegsimrs = $user['kodesimrs'];
        $kdruangarsip = $user['kode_ruang_arsip'];
        $nomor = '@nomor';


        DB::connection('siasik')->select('call noarsip(?,?)',array($nomor, $kdruangarsip));
        $x = DB::connection('siasik')->table('organisasi')->select('counter_arsip','panggilan','nama')->where('kode', $kdruangarsip)->get();
        $wew = $x[0]->counter_arsip;
        $panggilan = $x[0]->panggilan;
        $pencipta = $kdruangarsip;
        $unit_pengolah = $kdruangarsip;
        $noarsip = FormatingHelper::noarsip($wew, $panggilan);

        if ($request->hasFile('dokumen')) {

            try {
              $files = $request->file('dokumen');

            //   $user = auth()->user()->pegawai_id;

              if (!empty($files)) {

                for ($i = 0; $i < count($files); $i++) {
                    $file = $files[$i];

                    $originalname = $file->getClientOriginalName();
                    $penamaan = date('YmdHis') . '-' . $i . '-' . $noarsip . '.' . $file->getClientOriginalExtension();

                    $extension = $file->getClientOriginalExtension();

                    // return new JsonResponse($extension);
                    $data = Dataarsip::where([
                      ['noarsip',$noarsip],
                      ['file', $originalname]
                      ])->first();
                    if ($data) {
                      Storage::delete($data->path);
                    }

                    $gallery = null;
                    if ($data) {
                        $gallery = $data;
                    } else {
                        $gallery = new Dataarsip();
                    }

                    $folder = 'dokumen_arsip/'.$panggilan;
                    return $folder;


                    if (!is_dir(storage_path("app/public/$folder"))) {
                      mkdir(storage_path("app/public/$folder"), 0775, true);
                    }


                    // // Upload Avatar (IMAGE INTERVENTION - LARAVEL)
                    // Image::make($request->file("upload_image"))->save(storage_path("app/public/post-images/".$id.".png"));

                    if ($extension !== 'pdf') {

                      $img=Image::make($file)->resize(600, null, function ($constraint) {
                        $constraint->aspectRatio();
                      });

                      $img->save(\public_path("storage/$folder/". $penamaan), 60);
                    }else{
                      // $file->move(\public_path("storage/$folder/"), $penamaan);
                    // $path = $file->storeAs('public/dokumen_luar_poli', $penamaan);
                      $path = $file->storeAs('public/'.$folder, $penamaan);


                    }

                    $gallery->noarsip = $noarsip;
                    $gallery->pencipta = $pencipta;
                    $gallery->unit_pengolah = $unit_pengolah;
                    $gallery->tanggal = $request->tgl;
                    $gallery->uraian = $request->uraian;
                    $gallery->ket = $request->keaslian;
                    $gallery->kode = $request->kodekelasifikasi;
                    $gallery->jumlah = $request->jumlah;
                    $gallery->nobox = $request->nobox;
                    $gallery->lokasi = $request->lokasi;
                    $gallery->media = $request->media;
                    $gallery->file = $originalname;
                    $gallery->username = $kdpegsimrs;
                    $gallery->flaging = '1';

                    $gallery->path = "public/$folder/$penamaan";
                    $gallery->url = $folder . '/' . $penamaan;
                    $gallery->save();

                }

                $kirim = self::getlistdataarsipbynoarsip($noarsip);
                return new JsonResponse(['message' => 'success','result'=> $kirim], 200);
              }
            } catch (\Exception $th) {
              return new JsonResponse(['message' => 'invalid dokumen', 'error' => $th->getMessage()], 500);
            }
        }
    }
}
