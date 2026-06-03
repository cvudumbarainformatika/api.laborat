<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamaroperasi\KeluarRuangPemulihan;
use App\Models\Simrs\Penunjang\Kamaroperasi\LogMonitoringPascaAnastesi;
use App\Models\Simrs\Penunjang\Kamaroperasi\LogMonitoringSelamaAnastesi;
use App\Models\Simrs\Penunjang\Kamaroperasi\MedikasiPascaAnastesi;
use App\Models\Simrs\Penunjang\Kamaroperasi\MedikasiSelamaAnastesi;
use App\Models\Simrs\Penunjang\Kamaroperasi\SkorAldrete;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function getSelama()
    {
        $data['monitoring'] = LogMonitoringSelamaAnastesi::where('noreg', request('noreg'))
            ->where('nota', request('nota'))
            ->where('norm', request('norm'))
            ->get();
        $data['medikasi'] = MedikasiSelamaAnastesi::where('noreg', request('noreg'))
            ->where('nota', request('nota'))
            ->where('norm', request('norm'))
            ->first();

        return new JsonResponse($data);
    }
    public function simpanSelama(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'nota' => 'required',
            'norm' => 'required',
            'time' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $data = LogMonitoringSelamaAnastesi::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'nota' => $request->nota,
                    'norm' => $request->norm,
                    'time' => $request->time,
                ],
                $request->all()
            );
            if (!$data) throw new Exception('gagal disimpan');
            DB::commit();

            return new JsonResponse([
                'data' => $data,
                'message' => 'Data Berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),

            ], 410);
        }
    }
    public function simpanMedikasiSelama(Request $request)
    {

        $request->validate([
            'noreg' => 'required',
            'nota' => 'required',
            'norm' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $data = MedikasiSelamaAnastesi::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'nota' => $request->nota,
                    'norm' => $request->norm,
                ],
                $request->all()
            );
            if (!$data) throw new Exception('gagal disimpan');
            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'message' => 'Data Berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),

            ], 410);
        }
    }

    /** 
     *  pasca anastesi
     */
    public function getLogPasca()
    {
        // Mengambil data log berdasarkan noreg, nota, dan norm
        $data['monitoring'] = LogMonitoringPascaAnastesi::where('noreg', request('noreg'))
            ->where('nota', request('nota'))
            ->where('norm', request('norm'))
            ->orderBy('time', 'asc')
            ->get();
        $data['medikasi'] = MedikasiPascaAnastesi::where('noreg', request('noreg'))
            ->where('nota', request('nota'))
            ->where('norm', request('norm'))
            ->first();

        return new JsonResponse($data);
    }

    public function simpanLogPasca(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'nota' => 'required',
            'norm' => 'required',
            'time' => 'required', // Menit keberapa di RR
        ]);

        try {
            DB::beginTransaction();

            // Menggunakan updateOrCreate agar jika user mengedit menit yang sama, data tertimpa
            $data = LogMonitoringPascaAnastesi::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'nota' => $request->nota,
                    'norm' => $request->norm,
                    'time' => $request->time,
                ],
                $request->all()
            );

            if (!$data) throw new \Exception('Gagal menyimpan log pasca anestesi');

            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'message' => 'Log monitoring berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse(['message' => $e->getMessage()], 410);
        }
    }
    public function simpanMedikasiPasca(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'norm' => 'required',
            'nota' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $data = MedikasiPascaAnastesi::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'nota' => $request->nota,
                    'norm' => $request->norm,
                ],
                $request->all()
            );

            if (!$data) throw new Exception('Gagal menyimpan data medikasi pasca');

            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'message' => 'Instruksi Pasca Anestesi berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse(['message' => $e->getMessage()], 410);
        }
    }
    /**
     * Aldrete
     */
    public function getLogAldrete()
    {

        // Mengambil data log berdasarkan noreg, nota, dan norm
        $data['monitoring'] = SkorAldrete::where('noreg', request('noreg'))
            ->where('nota', request('nota'))
            ->where('norm', request('norm'))
            ->orderBy('waktu', 'asc')
            ->get();
        $data['medikasi'] = KeluarRuangPemulihan::where('noreg', request('noreg'))
            ->where('nota', request('nota'))
            ->where('norm', request('norm'))
            ->first();

        return new JsonResponse($data);
    }
    public function simpanLogAldrete(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'nota' => 'required',
            'norm' => 'required',
            'waktu' => 'required', // Menit keberapa di RR
        ]);
        try {
            DB::beginTransaction();

            // Menggunakan updateOrCreate agar jika user mengedit menit yang sama, data tertimpa
            $data = SkorAldrete::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'nota' => $request->nota,
                    'norm' => $request->norm,
                    'waktu' => $request->time,
                ],
                $request->all()
            );

            if (!$data) throw new \Exception('Gagal menyimpan skor Aldrete');

            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'message' => 'Skor Aldrete berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse(['message' => $e->getMessage()], 410);
        }
    }
    public function hapusLogAldrete(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        try {
            DB::beginTransaction();

            // Menggunakan updateOrCreate agar jika user mengedit menit yang sama, data tertimpa
            $data = SkorAldrete::find($request->id);

            if (!$data) throw new \Exception('Gagal Hapus, Tidak ada data yang ditemukan');

            $data->delete();
            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'message' => 'Data Skor Aldrete berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse(['message' => $e->getMessage()], 410);
        }
    }
    public function simpanKeluarRuangPemulihan(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'norm' => 'required',
            'nota' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $data = KeluarRuangPemulihan::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'nota' => $request->nota,
                    'norm' => $request->norm,
                ],
                $request->all()
            );

            if (!$data) throw new Exception('Gagal menyimpan data Keluar Ruang Pemulihan');

            DB::commit();
            return new JsonResponse([
                'data' => $data,
                'message' => 'Data Keluar Ruang Pemulihan berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse(['message' => $e->getMessage()], 410);
        }
    }
}
