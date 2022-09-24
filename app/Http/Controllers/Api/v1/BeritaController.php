<?php



namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML;

class BeritaController extends Controller
{
    public function index()
    {
       $data = Berita::latest()->with(['categories'])
       ->filter(request(['q','status']))
       ->paginate(12);
       return new JsonResponse($data);
    }

    public function web_beranda()
    {
       $data = Berita::with(['categories'])
       ->where('status', 2)
       ->filter(request(['q','category']))
       ->latest()->limit(8)->get();
       return new JsonResponse($data);
    }
    public function web_popular()
    {
       $data = Berita::query()
       ->withCount('berita_views')
       ->where('status', 2)
       ->orderBy('berita_views_count', 'desc')
       ->limit(6)
       ->get();
       return new JsonResponse($data);
    }
    public function web_content()
    {
       $data = Berita::query()
       ->withCount('berita_views')
       ->where('status', 2)
       ->filter(request(['q']))
       ->latest()->first();

       $clientIP = request()->ip();
       $data->berita_views()->firstOrCreate(['ip'=>$clientIP, 'berita_id'=>$data->id],['agent'=> request()->header('User-Agent')]);

       return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        // return new JsonResponse(['message'=> is_array($request->category_id), 'data'=>$request->all()], 201);
        $saved = DB::transaction(function () use ($request) {
            $originalname = null;
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
                // 'slug'=> [Rule::unique('beritas')->ignore($request->id)]
                'slug'=> 'unique:beritas,id,'.$request->id
            ]);

            if ($request->has('id')) {
                $data = Berita::find($request->id);

                if ($request->hasFile('thumbnail')) {
                    $old_path = $data->thumbnail;
                    Storage::delete('public/'.$old_path);

                    $data->thumbnail = $path;
                }

                $data->judul = $request->judul;
                $data->slug = $request->slug;
                $data->content = $request->content;
                $saved = $data->save();

                $data->categories()->sync($request->category_id);

            }else {
                $saved = Berita::create(
                [
                    'judul'=> $request->judul,
                    'slug'=> $request->slug,
                    'content'=> $request->content,
                    'thumbnail'=> $path
                ]);
                $saved->categories()->sync($request->category_id);
            }

            return $saved;
        });

        if (!$saved) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'success'], 201);


    }

    public function update_status(Request $request)
    {
        $data = Berita::find($request->id);
        $saved=$data->update([
            'status'=>$request->status
        ]);
        if (!$saved) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'success'], 201);
    }

    public function destroy(Request $request)
    {
        $data = Berita::query()->find($request->id);
        $old_path = $data->thumbnail;
        Storage::delete('public/'.$old_path);
        $deleted = $data->forceDelete();
        $user = $request->user();
        $user->log("Menghapus Data Berita {$data->judul}");
        if (!$deleted) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'success terhapus'], 201);

    }
}
