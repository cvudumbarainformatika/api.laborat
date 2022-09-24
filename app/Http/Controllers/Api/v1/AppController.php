<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\AppResource;
use App\Models\App;
use App\Models\Kunjungan;
use App\Models\Staf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isEmpty;

class AppController extends Controller
{
    public function header()
    {
        $data = App::with(['staf'])->find(1);
        $clientIP = request()->ip();
        Kunjungan::updateOrCreate(['ip'=>$clientIP],['agent'=> request()->header('User-Agent')]);
        return new AppResource($data);
    }

    public function store_logo(Request $request)
    {
        $data = App::find(1);
        $old_path = '';
        // hapus foto lama jika ada
        if (!empty($data)) {
            $old_path = $data->logo;
            if (!empty($old_path)) {
                Storage::delete('public/'.$old_path);
            }

            if ($request->hasFile('logo')) {
                $request->validate([
                    'logo'=>'required|image|mimes:jpeg,png,jpg'
                ]);
                $path = $request->file('logo')->store('images', 'public');
                $data->logo = $path;
                $saved = $data->save();
                if (!$saved) {
                    return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
                }
                return new JsonResponse(['message'=>'success'], 201);
            }
        }
        return new JsonResponse(['message'=>'Data tidak valid'], 500);
    }
    public function store_banner(Request $request)
    {
        $data = App::find(1);
        $old_path = '';
        // hapus foto lama jika ada
        if (!empty($data)) {
            $old_path = $data->banner;
            if (!empty($old_path)) {
                Storage::delete('public/'.$old_path);
            }

            if ($request->hasFile('banner')) {
                $request->validate([
                    'banner'=>'required|image|mimes:jpeg,png,jpg'
                ]);
                $path = $request->file('banner')->store('images', 'public');
                $data->banner = $path;
                $saved = $data->save();
                if (!$saved) {
                    return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
                }
                return new JsonResponse(['message'=>'success'], 201);
            }
        }
        return new JsonResponse(['message'=>'Data tidak valid'], 500);
    }
    public function store_image_section_one(Request $request)
    {
        $data = App::first();

        // $m = json_decode($request->input('point_1_title'));
        // return response()->json($request->input('point_2_data'));
        $old_path = '';
        // hapus foto lama jika ada
        if (!empty($data)) {
            $old_path = $data->section_one['image'];
            if (!empty($old_path)) {
                Storage::delete('public/'.$old_path);
            }

            $path = null;
            if ($request->hasFile('image')) {
                $request->validate([
                    'image'=>'required|image|mimes:jpeg,png,jpg'
                ]);
                $path = $request->file('image')->store('images', 'public');
            }
            // $request = new \Illuminate\Http\Request();
            $data1 = [];
            foreach($request->input('point_1_data') as $d)
            {
                if(! empty($d))
                {
                    $data1[] = $d;
                }
            }
            $data2 = [];
            foreach($request->input('point_2_data') as $d2)
            {
                if(! empty($d2))
                {
                    $data2[] = $d2;
                }
            }
            // return response()->json($data1);
            $data->section_one = array(
                'image'=> $path,
                'point_1'=>array(
                    'title'=>$request->input('point_1_title'),
                    'data'=> $data1,
                ),
                'point_2'=>[
                    'title'=>$request->input('point_2_title'),
                    'data'=> $data2,
                ],
            );
            $saved = $data->save();
            if (!$saved) {
                return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
            }
            return new JsonResponse(['message'=>'success', 'data' =>$data->section_one['point_2']['data']], 201);
        }
        return new JsonResponse(['message'=>'Data tidak valid'], 500);
    }

    public function store_section_two(Request $request)
    {
        $data = App::first();
        $arr = [];
        foreach($request->section_two as $key)
        {
            if(! empty($key))
            {
                $arr[]=$key;
            }
        }

        $data->section_two = $arr;
        $saved = $data->save();
        if (!$saved) {
            return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
        }
        return new JsonResponse(['message'=>'success'], 201);
    }
    public function store_themes(Request $request)
    {
        $data = App::first();
        $arr = [];
        foreach($request->themes as $key)
        {
            if(! empty($key))
            {
                $arr[]=$key;
            }
        }

        $data->themes = $arr;
        $saved = $data->save();
        if (!$saved) {
            return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
        }
        return new JsonResponse(['message'=>'success'], 201);
    }


    public function store_staf(Request $request)
    {
        $data = null;
        $old_path = '';
        if ($request->has('id')) {
            // edit
            $data = Staf::find($request->id);
            if ($request->hasFile('image')) {
                $request->validate([
                    'image'=>'required|image|mimes:jpeg,png,jpg'
                ]);
                $old_path = $data->image;
                if (!empty($old_path)) {
                    Storage::delete('public/'.$old_path);
                }
                $path = $request->file('image')->store('images', 'public');
                $data->image = $path;
            }
            $data->nama = $request->nama;
            $data->jabatan = $request->jabatan;
            $saved = $data->save();
            if (!$saved) {
                return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
            }
            return new JsonResponse(['message'=>'success'], 201);
        } else {
            // tambah data
            $data = new Staf();
            if ($request->hasFile('image')) {
                $request->validate([
                    'image'=>'required|image|mimes:jpeg,png,jpg'
                ]);
                $path = $request->file('image')->store('images', 'public');
                $data->image = $path;
            }
            $data->nama = $request->nama;
            $data->jabatan = $request->jabatan;
            $data->app_id =1;
            $saved = $data->save();
            if (!$saved) {
                return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
            }
            return new JsonResponse(['message'=>'success, Data tersipan'], 201);

        }
    }

    public function remove_staf(Request $request)
    {
        $data = Staf::find($request->id);
        if ($data) {
            $del = $data->delete();
            if (!$del) {
                return new JsonResponse(['message'=>'Data tidak terhapus'], 500);
            }
            return new JsonResponse(['message'=>'success terhapus'], 201);
        }
    }

    public function store(Request $request)
    {
        $data = App::find(1);

        $data->title = $request->title;
        $data->desc = $request->desc;
        $data->phone = $request->phone;
        $data->email = $request->email;
        $data->link_fb = $request->link_fb;
        $data->link_instagram = $request->link_instagram;
        $data->link_youtube = $request->link_youtube;
        $data->link_map = $request->link_map;
        $saved = $data->save();
        if (!$saved) {
            return new JsonResponse(['message'=>'Data tidak valid'], 500);
        }
        return new JsonResponse(['message'=>'success'], 201);
    }
}
