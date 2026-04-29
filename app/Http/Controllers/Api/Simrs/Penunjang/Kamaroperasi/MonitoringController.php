<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamaroperasi\LogMonitoringSelamaAnastesi;
use App\Models\Simrs\Penunjang\Kamaroperasi\MedikasiSelamaAnastesi;
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
}
