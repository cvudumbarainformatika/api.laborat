<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CarouselResource;
use App\Models\Carousel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarouselController extends Controller
{
    public function index()
    {
        $data=Carousel::where('status', 1)->latest()->paginate(8);
        return CarouselResource::collection($data);

    }
    public function manage()
    {
        $data=Carousel::latest()->paginate(request('per_page'));
        return CarouselResource::collection($data);

    }
    public function store(Request $request)
    {

        // edit
        if ($request->has('id')) {
            $data = Carousel::find($request->id);

            // $data->image = null;
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
            $data->title = $request->title;
            $data->desc = $request->desc;
            $saved = $data->save();
            if (!$saved) {
                return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
            }
            return new JsonResponse(['message'=>'success'], 201);

        } else {
            // save
            $data = new Carousel();
            if ($request->hasFile('image')) {
                $request->validate([
                    'image'=>'required|image|mimes:jpeg,png,jpg'
                ]);
                $path = $request->file('image')->store('images', 'public');
                $data->image = $path;
            }

            $data->title = $request->title;
            $data->desc = $request->desc;
            $saved = $data->save();
            if (!$saved) {
                return new JsonResponse(['message'=>'Data tidak tersimpan'], 500);
            }
            return new JsonResponse(['message'=>'success'], 201);
        }

    }
    public function destroy(Request $request)
    {
        $data = Carousel::find($request->id);
        if ($data) {
            $del = $data->delete();
            $user = $request->user();
            $user->log("Menghapus Data Carousel {$data->title}");
            if (!$del) {
                return new JsonResponse(['message'=>'Data tidak terhapus'], 500);
            }
            return new JsonResponse(['message'=>'success terhapus'], 201);
        }
    }
}
