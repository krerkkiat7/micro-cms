<?php

namespace App\Http\Controllers\Front\Intropage;

use App\Http\Controllers\Front\FrontController;
use App\Models\LogFrontAccess;
use App\Support\Front\FrontLayoutData;
use App\Support\Front\FrontMenuResolver;
use App\Support\Front\FrontUrl;
use App\Support\Front\IntropageResolver;
use App\Support\Front\SeoMeta;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * หน้าแรกของเว็บ (/ และ /{lang}) — แสดง Intropage ที่เผยแพร่อยู่ (ดู IntropageResolver) บน layout เปล่า (ไม่ใช้ sys_template)
 * ถ้าไม่มี Intropage ที่ตรงเงื่อนไข redirect ไปหน้าแรกที่กำหนดในเมนู (front_menu_info.is_home = Y)
 */
class IntropageController extends FrontController
{
    /**
     * `/` — ใช้ภาษาหลักของระบบ (ไม่ redirect ไป /{lang} ก่อน เพื่อให้เปิดเว็บได้ในการเรียกครั้งเดียว — canonical ชี้ /{lang})
     */
    public function root(Request $request): Response|RedirectResponse
    {
        $lang = Setting::defaultLanguage();
        App::setLocale($lang);

        return $this->index($request, $lang);
    }

    public function index(Request $request, string $lang): Response|RedirectResponse
    {
        $intro = IntropageResolver::current($lang);
        $homeUrl = FrontMenuResolver::homeUrl($lang);

        if ($intro === null) {
            // กัน redirect วนถ้าเมนูหน้าแรกชี้กลับมาที่หน้านี้เอง (เช่น ลิงก์ภายนอกเป็น /th)
            if ($homeUrl === null || $this->isSelf($request, $homeUrl)) {
                abort(404);
            }

            return redirect()->to($homeUrl);
        }

        LogFrontAccess::record($intro['title'] !== '' ? $intro['title'] : 'Intropage');

        $layout = FrontLayoutData::forLanguage($lang);
        $seo = SeoMeta::make($lang, [
            'title' => $intro['title'],
            'description' => $intro['detail'],
            'image' => $intro['image']['url'] ?? null,
            'canonical' => FrontUrl::home($lang),
            'alternates' => $this->alternates(fn (string $code) => FrontUrl::home($code)),
        ]);

        // layout เปล่า — ส่งเฉพาะข้อมูลระบบที่ใช้ (ไม่ส่ง template/เมนู)
        return Inertia::render('Front/Intropage/Index', [
            'intro' => $intro,
            'homeUrl' => $homeUrl ?? FrontUrl::home($lang),
            'front' => [
                'lang' => $layout['lang'],
                'languages' => $layout['languages'],
                'defaultLanguage' => $layout['defaultLanguage'],
                'site' => $layout['site'],
                'copyright' => $layout['copyright'],
                'fontsUrl' => null,
                't' => $layout['t'],
            ],
            'seo' => $seo,
        ]);
    }

    private function isSelf(Request $request, string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $host = parse_url($url, PHP_URL_HOST);

        return ($host === null || $host === $request->getHost()) && rtrim($path, '/') === rtrim($request->getPathInfo(), '/');
    }
}
