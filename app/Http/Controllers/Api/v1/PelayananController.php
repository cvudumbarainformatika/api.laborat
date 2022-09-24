<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Pelayanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PelayananController extends Controller
{
    public function index()
    {
       $data = Pelayanan::latest()
    //    ->with(['categories'])
    //    ->filter(request(['q','status']))
       ->paginate(12);
       return new JsonResponse($data);
    }

    public function web_content()
    {
       $data = Pelayanan::query()
       ->get();

    //    $clientIP = request()->ip();
    //    $data->berita_views()->firstOrCreate(['ip'=>$clientIP, 'berita_id'=>$data->id],['agent'=> request()->header('User-Agent')]);

       return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $saved = DB::transaction(function () use ($request) {
            $path = null;
            $old_path = null;
            $data = null;

            if ($request->hasFile('thumbnail')) {
                $request->validate([
                    'thumbnail'=>'required|image|mimes:jpeg,png,jpg'
                ]);
                $path = $request->file('thumbnail')->store('thumbnail', 'public');
            }

            $request->validate([
                'slug'=> 'unique:pelayanans,id,'.$request->id
            ]);

            if ($request->has('id')) {
                $data = Pelayanan::find($request->id);

                if ($request->hasFile('thumbnail')) {
                    $old_path = $data->thumbnail;
                    Storage::delete('public/'.$old_path);

                    $data->thumbnail = $path;
                }
            } else {
                $data = new Pelayanan();
                if ($request->hasFile('thumbnail')) {
                    $data->thumbnail = $path;
                }
            }

            $data->slug = $request->slug;
            $data->nama = $request->nama;
            $data->content = $request->content;

            $saved = $data->save();

            return $saved;
        });
        if (!$saved) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'success'], 201);
    }

    public function destroy(Request $request)
    {
        $data = Pelayanan::query()->find($request->id);
        $old_path = $data->thumbnail;
        Storage::delete('public/'.$old_path);
        $deleted = $data->forceDelete();
        $user = $request->user();
        $user->log("Menghapus Data Pelayanan {$data->nama}");

        if (!$deleted) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'success terhapus'], 201);

    }
}
