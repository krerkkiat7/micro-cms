<?php

namespace App\Http\Middleware;

use App\Support\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // ดึงค่า lang จาก URL Parameter — route {lang} เองก็ผูก where ตาม Setting::selectedLanguages()
        // ไว้แล้ว (routes/web.php) จึงควรจะ valid เสมอ เช็กซ้ำที่นี่กันไว้เผื่อกรณีอื่นที่ไม่ผ่าน route นี้
        $lang = $request->route('lang');

        App::setLocale(
            in_array($lang, Setting::selectedLanguages(), true) ? $lang : Setting::defaultLanguage()
        );

        return $next($request);
    }
}
