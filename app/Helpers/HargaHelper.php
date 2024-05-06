<?php

namespace App\Helpers;

use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use Illuminate\Http\JsonResponse;

class HargaHelper
{
    public static function getHarga($kdobat, $sistembayar)
    {
        $data = DaftarHarga::selectRaw('max(harga) as harga')
            ->where('kd_obat', $kdobat)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->limit(5)
            ->first();
        $harga = $data->harga;
        if (!$harga) {
            return [
                'res' => true,
                'message' => 'Tidak ada harga untuk obat ini',
                'data' => $data,
                'kdobat' => $kdobat,
                'sistembayar' => $sistembayar,
            ];
        }
        if ($sistembayar == 1 || $sistembayar == '1') {
            if ($harga <= 50000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 28 / (int) 100;
            } elseif ($harga > 50000 && $harga <= 250000) {
                $hargajualx = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
            } elseif ($harga > 250000 && $harga <= 500000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 21 / (int) 100;
            } elseif ($harga > 500000 && $harga <= 1000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 16 / (int)100;
            } elseif ($harga > 1000000 && $harga <= 5000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 11 /  (int)100;
            } elseif ($harga > 5000000 && $harga <= 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 9 / (int) 100;
            } elseif ($harga > 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 7 / (int) 100;
            }
        } else {
            $hargajualx = (int) $harga + (int) $harga * (int) 25 / (int)100;
        }
        return [
            'res' => false,
            'hargaJual' => $hargajualx,
            'harga' => $harga
        ];
    }
}
