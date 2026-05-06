<?php

namespace App\Helpers;

use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RadiologyHelper
{
  public static function sendPatientToPacs($payload = [])
  {
    $url = config('services.orthanc.url');

    // =========================
    // DETEKSI MODALITY
    // =========================
    $payload['modality'] = self::detectModality($payload);

    // =========================
    // VALIDASI URL
    // =========================
    if (empty($url)) {
      Log::error('Orthanc URL tidak diset', [
        'payload' => $payload
      ]);
      return true; // tetap lanjut
    }

    try {
      // $response = Http::timeout(3) // biar gak ngegantung lama
      //   ->post($url . '/webhook/patient', $payload);

      // $response = Http::async()->post($url . '/webhook/patient', $payload);

      $response = Http::timeout(2)
        ->retry(1, 100)
        ->post($url . '/webhook/patient', $payload);

      // optional: log kalau gagal response (status bukan 2xx)
      if (!$response->successful()) {
        Log::warning('Webhook patient gagal', [
          'url' => $url,
          'payload' => $payload,
          'status' => $response->status(),
          'body' => $response->body()
        ]);
      }
    } catch (\Throwable $e) {

      // ❗ error apapun ditangkap, tidak mengganggu aplikasi
      Log::error('Webhook patient error', [
        'url' => $url,
        'payload' => $payload,
        'error' => $e->getMessage()
      ]);
    }

    // selalu return true biar flow lanjut
    return true;
  }


  private static function detectModality($payload): string
  {
    // contoh ambil dari tindakan
    $tindakan = $payload['kdpemeriksaan'] ?? '';

    $cek = Mpemeriksaanradiologi::where('rs1', $tindakan)->first();

    return $cek?->modality ?: 'OT';
  }
}
