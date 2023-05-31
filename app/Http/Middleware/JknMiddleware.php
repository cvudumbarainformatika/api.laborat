<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
// use JWTAuth;
use Exception;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\Providers\JWT\Namshi;

class JknMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('x-token');
        $username = $request->header('x-username');

        $selectedUser = User::where('username', '=', $username)->first();
        if (!$username || !$selectedUser) {
            $metadata['message'] = 'Username tidak ditemukan!';
            $metadata['code'] = 201;
            $data['metadata'] = $metadata;

            return response()->json($data, $metadata['code']);
        }

        if (!$token) {
            $metadata['message'] = 'Token not provided.';
            $metadata['code'] = 201;
            $data['metadata'] = $metadata;

            return response()->json($data, $metadata['code']);
        }

        try {
            // $user = JWTAuth::parseToken()->authenticate();
            // $tkn = JWTAuth::getToken();
            // $apy = JWTAuth::getPayload($token)->toArray();
            // // $apy = base64_decode($token);

            $apy = Namshi::decode3rdparty($token);

            // return response()->json($apy);
        } catch (TokenExpiredException $e) {
            $metadata['message'] = 'Provided token is expired.';
            $metadata['code'] = 201;
            $data['metadata'] = $metadata;

            return response()->json($data, $metadata['code']);
        } catch (TokenInvalidException $e) {
            $metadata['message'] = $e;
            // $metadata['message'] = 'An error while decoding token.';
            $metadata['code'] = 201;
            $data['metadata'] = $metadata;

            return response()->json($data, $metadata['code']);
        } catch (Exception $e) {
            $metadata['message'] = $e->getMessage();
            // $metadata['message'] = 'An error while decoding token.';
            $metadata['code'] = 201;
            $data['metadata'] = $metadata;

            return response()->json($data, $metadata['code']);
        }

        $user = User::find($apy['sub']);
        // return response()->json($user);
        // Now let's put the user in the request class so that you can grab it from there
        $request['auth'] = $user;
        return $next($request);
    }
}
