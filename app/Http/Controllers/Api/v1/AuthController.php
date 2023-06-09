<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\aplikasi\Aplikasi;
use App\Models\Pegawai\Akses\Access;
use App\Models\Pegawai\Akses\AksesUser;
use App\Models\Pegawai\Akses\Menu;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $temp = User::where('email', '=', $request->email)
            ->first();
        if (!$temp) {
            return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
        }
        if ($temp) {

            $pass = Hash::check($request->password, $temp->password);
            if (!$pass) {
                return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
            }
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }

    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    public function authuser()
    {
        $me = auth()->user();
        $user = User::with(['pegawai'])->find($me->id);

        $apps = Aplikasi::with(['menus', 'menus.submenus'])->get();
        $akses = 'all';
        $allAccess = array('sa');

        if (!in_array(auth()->user()->username, $allAccess)) {
            // $akses = User::with('akses.aplikasi', 'akses.menu', 'akses.submenu')->find($me);
            $akses = AksesUser::where('user_id', $me->id)->get();
        }

        $result = [
            'apps' => $apps,
            'akses' => $akses,
            'user' => $user
        ];
        return new JsonResponse($result);
    }

    public function me()
    {
        $me = auth()->user();
        $akses = User::with('akses.aplikasi', 'akses.menu', 'akses.submenu')->find(auth()->user()->id);
        $pegawai = Pegawai::with('ruang', 'depo')->find($akses->pegawai_id);
        $submenu = Access::where('role_id', $pegawai->role_id)->with('role', 'aplikasi', 'submenu.menu')->get();

        $col = collect($submenu);
        $role = $col->map(function ($item, $key) {
            return $item->role;
        })->unique();
        $apli = $col->map(function ($item, $key) {
            return $item->aplikasi;
        })->unique('id');
        $subm = $col->map(function ($item, $key) {
            return $item->submenu;
        });

        $menu = $col->map(function ($item, $key) {
            return $item->submenu->menu;
        })->unique('id');

        $into = $menu->map(function ($item, $key) use ($subm) {
            // $mbuh = [];
            $temp = $subm->where('menu_id', $item->id);
            $map = $temp->map(function ($ki, $ke) {
                // $map = $temp->each(function ($ki, $ke) {
                return [
                    'nama' => $ki->nama,
                    'name' => $ki->name,
                    'icon' => $ki->icon,
                    'link' => $ki->link,

                ];
            });
            $apem = [
                'aplikasi_id' => $item->aplikasi_id,
                'nama' => $item->nama,
                'name' => $item->name,
                'icon' => $item->icon,
                'link' => $item->link,
                'submenus' => $map,
            ];
            return $apem;
        });
        // akses 2 start
        $aks = collect($akses->akses);
        // $apli2 = $aks;
        $apli2 = $aks->map(function ($item, $key) {
            return $item->aplikasi;
        })->unique('id');
        $subm2 = $aks->map(function ($item, $key) {
            return $item->submenu;
        });
        $menu2 = $aks->map(function ($item, $key) {
            return $item->menu;
        })->unique('id');

        $into2 = $menu2->map(function ($item, $key) use ($subm2) {
            // $mbuh = [];
            $temp = $subm2->where('menu_id', $item->id);
            $map = $temp->map(function ($ki, $ke) {
                // $map = $temp->each(function ($ki, $ke) {
                return [
                    'nama' => $ki->nama,
                    'name' => $ki->name,
                    'icon' => $ki->icon,
                    'link' => $ki->link,

                ];
            });
            $apem = [
                'aplikasi_id' => $item->aplikasi_id,
                'nama' => $item->nama,
                'name' => $item->name,
                'icon' => $item->icon,
                'link' => $item->link,
                'submenus' => $map,
            ];
            return $apem;
        });
        // akses 2 end
        $foto = $pegawai->nip . '/' . $pegawai->foto;
        $raw = collect($pegawai);
        $apem = $raw['ruang'];
        $gud = $raw['depo'];
        return new JsonResponse([
            'result' => $me,
            'aplikasi' => $apli,
            'aplikasi2' => $apli2,
            'menus' => $into,
            'menus2' => $into2,
            'role' => $role,
            'foto' => $foto,
            'ruang' => $apem,
            'kode_ruang' => $pegawai->kode_ruang,
            'depo' => $gud,
            'akses' => $akses
        ]);
    }

    public function user()
    {
        $data = User::filter(request(['q']))->get();

        return new JsonResponse($data);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json(['message' => 'User sukses logout dari aplikasi']);
    }

    // public function refresh() {
    //     return $this->createNewToken(auth()->refresh());
    // }

    protected function createNewToken($token)
    {

        $akses = User::with('akses.aplikasi', 'akses.menu', 'akses.submenu')->find(auth()->user()->id);
        $pegawai = Pegawai::with('ruang', 'depo')->find($akses->pegawai_id);
        $submenu = Access::where('role_id', $pegawai->role_id)->with(['role', 'aplikasi', 'submenu.menu'])->get();

        $col = collect($submenu);
        $role = $col->map(function ($item, $key) {
            return $item->role;
        })->unique();
        $apli = $col->map(function ($item, $key) {
            return $item->aplikasi;
        })->unique('id');
        $subm = $col->map(function ($item, $key) {

            return $item->submenu;
        });

        $menu = $col->map(function ($item, $key) {
            return $item->submenu->menu;
        })->unique('id');

        $into = $menu->map(function ($item, $key) use ($subm) {
            // $mbuh=[];
            $temp = $subm->where('menu_id', $item->id);
            $map = $temp->map(function ($ki, $ke) {
                return [
                    'nama' => $ki->nama,
                    'name' => $ki->name,
                    'icon' => $ki->icon,
                    'link' => $ki->link,

                ];
            });
            $apem = [
                'aplikasi_id' => $item->aplikasi_id,
                'nama' => $item->nama,
                'name' => $item->name,
                'icon' => $item->icon,
                'link' => $item->link,
                'submenus' => $map,
            ];
            return $apem;
        });
        // akses 2 start
        $aks = collect($akses->akses);
        // $apli2 = $aks;
        $apli2 = $aks->map(function ($item, $key) {
            return $item->aplikasi;
        })->unique('id');
        $subm2 = $aks->map(function ($item, $key) {
            return $item->submenu;
        });
        $menu2 = $aks->map(function ($item, $key) {
            return $item->menu;
        })->unique('id');

        $into2 = $menu2->map(function ($item, $key) use ($subm2) {
            // $mbuh = [];
            $temp = $subm2->where('menu_id', $item->id);
            $map = $temp->map(function ($ki, $ke) {
                // $map = $temp->each(function ($ki, $ke) {
                return [
                    'nama' => $ki->nama,
                    'name' => $ki->name,
                    'icon' => $ki->icon,
                    'link' => $ki->link,

                ];
            });
            $apem = [
                'aplikasi_id' => $item->aplikasi_id,
                'nama' => $item->nama,
                'name' => $item->name,
                'icon' => $item->icon,
                'link' => $item->link,
                'submenus' => $map,
            ];
            return $apem;
        });
        // akses 2 end
        $foto = $pegawai->nip . '/' . $pegawai->foto;
        $raw = collect($pegawai);
        $apem = $raw['ruang'];
        $gud = $raw['depo'];
        return response()->json([
            'token' => $token,
            'user' => auth()->user(),
            'aplikasi' => $apli,
            'menus' => $into,
            'aplikasi2' => $apli2,
            'menus2' => $into2,
            'role' => $role,
            'foto' => $foto,
            'ruang' => $apem,
            'kode_ruang' => $pegawai->kode_ruang,
            'depo' => $gud,
            'akses' => $akses
        ]);
    }


    public function test()
    {
        $data = User::all();
        return new JsonResponse($data);
    }

    public function new_reg(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'username' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);


        $data = new User();
        $data->nama = $request->nama;
        $data->username = $request->username;
        $data->email = $request->email . '@app.com';
        $data->password = bcrypt($request->password);

        $saved = $data->save();

        if (!$saved) {
            return new JsonResponse(['status' => 'failed', 'message' => 'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['status' => 'success', 'message' => 'Data tersimpan'], 201);
    }
}
