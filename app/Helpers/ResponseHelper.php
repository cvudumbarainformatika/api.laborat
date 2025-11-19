<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    public static function responseGetSimplePaginate($raw, $req, $totalCount)
    {

        $lastPage = max(1, (int)ceil($totalCount / $req['per_page']));
        $current = $raw->currentPage();

        // from – to
        if ($totalCount === 0) {
            $from = null;
            $to = null;
        } else {
            $from = (($current - 1) * $req['per_page']) + 1;
            $to = min($current * $req['per_page'], $totalCount);
        }

        

        $data = [
            'data' => $raw->items(),
            'meta' => [
                'first' => $raw->url(1),
                'last' => $raw->url($lastPage),
                'prev' => $raw->previousPageUrl(),
                'next' => $raw->nextPageUrl(),
                'current_page' => $current,
                'per_page' => (int)$req['per_page'],
                'total' => (int)$totalCount,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ];

        return $data;
    }
    public static function responseStore($data, $message = '',  $code = 200, $side = null)
    {

        return new JsonResponse([
            'data' => $data,
            'side' => $side,
            'message' => $message
        ], $code);
    }
}
