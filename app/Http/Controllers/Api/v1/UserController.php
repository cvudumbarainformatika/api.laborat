<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $data= User::where('id','>',1)
                ->latest()->paginate(12);
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {

        $saved = DB::transaction(function () use ($request) {
            $data = null;
            if ($request->has('id')) {
                $data = User::find($request->id);
                if ($request->has('password')) {
                    $data->password = bcrypt($request->password);
                }
            } else {
                $data = new User();
                $data->password = bcrypt($request->password);
            }
            $data->name = $request->name;
            $data->email = $request->email;
            $saved = $data->save();

            return $saved;
        });
        if (!$saved) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'Success, Data tersimpan'], 201);
    }

    public function destroy(Request $request)
    {
        $data = User::query()->find($request->id);
        // $old_path = $data->thumbnail;
        // Storage::delete('public/'.$old_path);
        $deleted = $data->forceDelete();
        $user = $request->user();
        $user->log("Menghapus Data User {$data->name}");
        if (!$deleted) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['message'=>'success terhapus'], 201);

    }
}
