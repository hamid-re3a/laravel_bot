<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $origin = $request->server()['HTTP_ORIGIN'];
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Headers: '. $origin);
    }
}
