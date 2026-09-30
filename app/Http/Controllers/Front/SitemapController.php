<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\Front\Sitemap;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * /sitemap.xml (index) + ไฟล์ย่อย และ /robots.txt — ข้อมูลอิงตามเมนูหน้าบ้านที่เผยแพร่ (App\Support\Front\Sitemap)
 *
 * route เหล่านี้ตัด middleware session/CSRF/Inertia ออก (routes/web.php) — bot ไม่สร้าง session และ response ไม่มี Set-Cookie
 * จึง cache ที่ CDN/proxy ได้; ตอบพร้อม Cache-Control + ETag (If-None-Match ตรง = 304 ไม่ส่งเนื้อหาซ้ำ)
 */
class SitemapController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->xml($request, Sitemap::index());
    }

    public function main(Request $request): Response
    {
        return $this->xml($request, Sitemap::main()['xml']);
    }

    public function article(Request $request, int $n): Response
    {
        $file = Sitemap::articleFile($n);

        // ตอบ 404 เปล่า ๆ (ไม่ abort) — หน้า error หน้าบ้านต้องใช้ session ซึ่ง route นี้ไม่มี
        return $file === null ? response('', 404) : $this->xml($request, $file['xml']);
    }

    public function robots(Request $request): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            '',
            'Sitemap: '.route('front.sitemap'),
            '',
        ]);

        return $this->respond($request, $content, 'text/plain; charset=UTF-8');
    }

    private function xml(Request $request, string $content): Response
    {
        return $this->respond($request, $content, 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex'); // ตัวไฟล์ sitemap เองไม่ต้องขึ้นผลค้นหา
    }

    private function respond(Request $request, string $content, string $type): Response
    {
        $response = response($content, 200, ['Content-Type' => $type]);
        $response->setPublic();
        $response->setMaxAge(max(0, (int) config('front.sitemap.ttl', 3600)));
        $response->setEtag(md5($content));
        $response->isNotModified($request); // ETag ตรง → เปลี่ยนเป็น 304 และตัดเนื้อหาออกให้

        return $response;
    }
}
