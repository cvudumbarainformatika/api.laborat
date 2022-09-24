<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\GalleryResource;
use App\Models\Gallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index()
    {
        $data=Gallery::latest()->paginate(request('per_page'));
        return GalleryResource::collection($data);
    }
    public function upload(Request $request)
    {

        if($request->hasFile('images'))
        {
            // return response()->json($request->images);
            $files = $request->file('images');

            if(!empty($files)){

                for($i = 0; $i < count($files); $i++){
                    $file = $files[$i];
                    $originalname = $file->getClientOriginalName();
                    $data = Gallery::where('original', $originalname)->first();
                    Storage::delete('public/gallery/'.$originalname);
                    $gallery = null;
                    if ($data) {
                        $gallery = $data;
                    } else {
                        $gallery = new Gallery();
                    }
                    $path = $file->storeAs('public/gallery/', $originalname);
                    $gallery->nama = $path;
                    $gallery->original = $originalname;
                    $gallery->save();
                    // Gallery::updateOrCreate(['original'=>$originalname],
                    // ['nama'=> $path]);
                }
                return new JsonResponse(['message'=>'success'], 201);
            }
        }

    }
}
