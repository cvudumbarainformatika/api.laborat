<?php
namespace App\Http\Controllers\Api\Simrs\Pelayanan\DokumenUpload;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MdokumenUpload;
use App\Models\Simrs\Pelayanan\DokumenUpload;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class DokumenUploadController extends Controller
{

    public function master()
    {
        $data= MdokumenUpload::when(request()->ranap, function ($query) {
            $query->where('ranap', '1');
        })
        ->pluck('nama');
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
      // return response()->json($request->all());
        if ($request->hasFile('dokumen')) {
            
            try {
              $files = $request->file('dokumen');

              // $validator = Validator::make($request->all(), [
              //     'dokumen' => 'max:1024',
              // ]);

              // return new JsonResponse($files);
              $user = auth()->user()->pegawai_id;

              if (!empty($files)) {

                for ($i = 0; $i < count($files); $i++) {
                    $file = $files[$i];
                    
                    $originalname = $file->getClientOriginalName();
                    $penamaan = date('YmdHis') . '-' . $i . '-' . $request->norm . '.' . $file->getClientOriginalExtension();

                    $extension = $file->getClientOriginalExtension();

                    // return new JsonResponse($extension);
                    $data = DokumenUpload::where([
                      ['noreg',$request->noreg],
                      ['original', $originalname]
                      ])->first();
                    if ($data) {
                      Storage::delete($data->path);
                    } 
                    
                    $gallery = null;
                    if ($data) {
                        $gallery = $data;
                    } else {
                        $gallery = new DokumenUpload();
                    }

                    $folder = $request->isRanap ? 'dokumen_luar_ranap' : 'dokumen_luar_poli';



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
                    
                    


                    

                    $gallery->noreg = $request->noreg;
                    $gallery->norm = $request->norm;
                    $gallery->nama = $request->nama;
                    $gallery->path = "public/$folder/$penamaan";
                    $gallery->url = $folder . '/' . $penamaan;
                    $gallery->original = $originalname;
                    $gallery->user_input = $user;
                    $gallery->save();
                }

                $kirim = DokumenUpload::where([['noreg', '=',$request->noreg]])->get();
                return new JsonResponse(['message' => 'success','result'=> $kirim->load('pegawai:id,nama')], 200);
              }
            } catch (\Exception $th) {
              return new JsonResponse(['message' => 'invalid dokumen', 'error' => $th->getMessage()], 500);
            }
        }
        return new JsonResponse(['message' => 'invalid dokumen'], 500);
    }

    public function deletedata(Request $request)
    {
      
      $data = DokumenUpload::find($request->id);

      if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
      }
      Storage::delete($data->path);
      $del = $data->delete();

      if (!$del) {
        return new JsonResponse(['message'=> 'Failed'], 500);
      }

      return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
    }
}
