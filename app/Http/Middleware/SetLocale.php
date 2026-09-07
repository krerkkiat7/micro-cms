<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // ดึงค่า lang จาก URL Parameter (th หรือ en)
        $lang = $request->route('lang');

        if (in_array($lang, ['th', 'en'])) {
            App::setLocale($lang);
        } else {
            App::setLocale('th'); // Default
        }

        return $next($request);
    }
}
