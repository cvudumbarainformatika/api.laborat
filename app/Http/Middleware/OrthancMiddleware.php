<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrthancMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil key dari file .env SIMRS
        $apiKey = config('services.orthanc.api_key');

        // Cek apakah header X-API-KEY sesuai dengan yang ada di .env
        if ($request->header('X-API-KEY') !== $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. API Key tidak valid.'
            ], 401);
        }

        return $next($request);
    }
}
