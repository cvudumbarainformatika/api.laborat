<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Template\KamarOperasiDetailTemplate;
use App\Models\Simrs\Penunjang\Farmasinew\Template\KamarOperasiTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateObatOperasiController extends Controller
{
    //
    public function cari()
    {
        $data = KamarOperasiTemplate::where('nama', 'like', '%' . request('q') . '%')
            ->where('sistembayar', request('sistembayar'))
            ->when(request('user') == 'private', function ($q) {
                $q->where('user', 'private')
                    ->where('pegawai_id', auth()->user()->pegawai_id);
            }, function ($q) {
                $q->where(function ($y) {
                    $y->where('user', 'public')
                        ->orWhere(function ($x) {
                            $x->where('user', 'private')
                                ->where('pegawai_id', auth()->user()->pegawai_id);
                        });
                });
            })
            ->with(['rinci.obat:kd_obat,nama_obat', 'pegawai:id,nama'])
            ->limit(20)
            ->get();
        return new JsonResponse([
            'message' => 'data ditemukan',
            'data' => $data
        ]);
    }
    public function simpan(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $id = $request->id ?? null;

            $data = KamarOperasiTemplate::updateOrCreate(
                [
                    'id' => $id
                ],
                [
                    'nama' => $request->nama,
                    'user' => $request->user,
                    'pegawai_id' => auth()->user()->pegawai_id,
                    'sistembayar' => $request->groups
                ]
            );
            if (!$data) {
                throw new \Exception('ada kesalahan, gagal menyimpan data');
            }

            $detail = KamarOperasiDetailTemplate::updateOrCreate(
                [
                    'kamar_operasi_template_id' => $data->id,
                    'kd_obat' => $request->kd_obat,
                ],
                [
                    'jumlah' => $request->jumlah
                ]
            );
            if (!$detail) {
                throw new \Exception('ada kesalahan, gagal menyimpan obat');
            }
            $data->load('rinci.obat:kd_obat,nama_obat');
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Template Obat Operasi Sudah Disiampan',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan ' . $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ], 410);
        }
    }
    public function hapusRinci(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = KamarOperasiDetailTemplate::find($request->id);
            if (!$data) {
                throw new \Exception('data tidak ditemukan');
            }
            $idTem = $data->kamar_operasi_template_id;

            $data->delete();
            $jum = KamarOperasiDetailTemplate::where('kamar_operasi_template_id', '=', $idTem)->count();
            if ($jum == 0) {
                KamarOperasiTemplate::find($idTem)->delete();
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Obat Sudah Dihapus',
            ]);
        } catch (\Throwable $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan ' . $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ], 410);
        }
    }
}
