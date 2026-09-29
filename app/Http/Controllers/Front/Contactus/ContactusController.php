<?php

namespace App\Http\Controllers\Front\Contactus;

use App\Http\Controllers\Front\FrontController;
use App\Http\Requests\Front\StoreContactusRequest;
use App\Models\ContactusItem;
use App\Models\FileInfo;
use App\Models\LogFrontAccess;
use App\Support\ClientIp;
use App\Support\ContactusSetting;
use App\Support\Front\FrontCache;
use App\Support\Front\FrontFile;
use App\Support\Front\FrontMenuResolver;
use App\Support\Front\FrontUrl;
use App\Support\Front\SeoMeta;
use App\Support\FrontMenuType;
use App\Support\GoogleMap;
use App\Support\PageTextStyle;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * หน้าติดต่อเรา (/{lang}/contactus) — แสดงตามตั้งค่าโมดูลติดต่อเรา (App\Support\ContactusSetting)
 * ข้อมูลติดต่อ/social มาจาก prop `front` (FrontLayoutData — ตั้งค่าระบบ) ไม่ส่งซ้ำ
 * แบบฟอร์มเปิดรับเฉพาะเมื่อเปิดแสดงและตั้งค่า Turnstile ครบ (ContactusSetting::formEnabled()) — ไม่งั้นไม่แสดงและ store = 404
 */
class ContactusController extends FrontController
{
    public function show(Request $request, string $lang): Response
    {
        $display = FrontCache::remember("contactus.{$lang}", (int) config('front.cache.content_ttl', 300), fn () => $this->displayData($lang));

        $title = (string) trans('front.contact_us', [], $lang);

        LogFrontAccess::record($title);

        $menu = FrontMenuResolver::findFor($lang, FrontMenuType::CONTACTUS, 0);
        $header = $this->pageHeader($lang, $menu, [['name' => $menu['name'] ?? $title, 'url' => null]]);
        $canonical = FrontUrl::contactus($lang);

        $seo = SeoMeta::make($lang, [
            'title' => $menu['name'] ?? $title,
            'canonical' => $canonical,
            'alternates' => $this->alternates(fn (string $code) => FrontUrl::contactus($code)),
            'breadcrumb' => $this->crumbsWithCurrent($header['breadcrumb'], $canonical),
            'schema' => [
                '@type' => 'ContactPage',
                'name' => $menu['name'] ?? $title,
                'url' => $canonical,
                'inLanguage' => $lang,
            ],
        ]);

        $form = null;

        if ($display['formEnabled']) {
            $form = [
                'fields' => $display['formFields'],
                'siteKey' => Turnstile::siteKey(),
                // เวลาที่เปิดฟอร์ม (เข้ารหัส) — สร้างใหม่ทุก request (ไม่อยู่ใน cache)
                'token' => StoreContactusRequest::issueFormToken(),
                'action' => route('front.contactus.item.store', ['lang' => $lang]),
            ];
        }

        return $this->render('Front/Contactus/Item', $lang, [
            'contactus' => [
                'title' => $menu['name'] ?? $title,
                'displayType' => $display['displayType'],
                'texts' => $display['texts'],
                'showSocial' => $display['showSocial'],
                'mapImage' => $display['mapImage'],
                'googleMap' => $display['googleMap'],
                'fontsUrl' => $display['fontsUrl'],
            ],
            'form' => $form,
            'sent' => (bool) $request->session()->get('contactus_sent', false),
            'header' => $header,
        ], $seo);
    }

    public function store(StoreContactusRequest $request, string $lang): RedirectResponse
    {
        if (! ContactusSetting::formEnabled()) {
            abort(404);
        }

        // honeypot มีค่า = บอท — ตอบเหมือนสำเร็จแต่ไม่บันทึก
        if (! $request->isHoneypotFilled()) {
            $item = new ContactusItem;
            $item->forceFill($request->contactData());
            $item->fullname = (string) $item->fullname;
            $item->lang = $lang; // จาก route — pattern ของ route รับเฉพาะภาษาที่เปิดใช้
            $item->remote_ip = ClientIp::from($request);
            $item->agent = Str::limit((string) $request->userAgent(), 490, '');
            $item->process_status = 'unread';
            $item->status = 'Y';
            $item->save();
        }

        return redirect()->route('front.contactus.item', ['lang' => $lang])->with('contactus_sent', true);
    }

    /**
     * ข้อมูลการแสดงผลที่ cache ได้ (ไม่มีข้อมูลเฉพาะผู้ชม)
     *
     * @return array<string, mixed>
     */
    private function displayData(string $lang): array
    {
        $settings = ContactusSetting::all();
        $texts = [];
        $fonts = [];

        foreach (ContactusSetting::TEXT_PARTS as $part) {
            $texts[$part] = [
                'show' => $part === 'owner' || $settings["show_{$part}"] === 'Y',
                'style' => [
                    'font_size' => (int) $settings["{$part}_font_size"],
                    'font_family' => $settings["{$part}_font_family"],
                    'bold' => $settings["{$part}_bold"] === 'Y',
                    'italic' => $settings["{$part}_italic"] === 'Y',
                    'underline' => $settings["{$part}_underline"] === 'Y',
                    'color' => $settings["{$part}_color"],
                ],
            ];
            $fonts[] = $settings["{$part}_font_family"];
        }

        $mapImage = null;

        if ($settings['show_map_image'] === 'Y' && $settings['map_image_id'] !== '') {
            $mapImage = FrontFile::fromFileInfo(FileInfo::find($settings['map_image_id']), 1600);
        }

        $googleMap = null;

        if ($settings['show_google_map'] === 'Y' && is_numeric($settings['latitude']) && is_numeric($settings['longitude'])) {
            $latitude = (float) $settings['latitude'];
            $longitude = (float) $settings['longitude'];
            $googleMap = [
                'embedUrl' => GoogleMap::embedUrl($latitude, $longitude, $lang),
                'directionsUrl' => GoogleMap::directionsUrl($latitude, $longitude),
            ];
        }

        return [
            'displayType' => $settings['display_type'],
            'texts' => $texts,
            'showSocial' => $settings['show_social'] === 'Y',
            'mapImage' => $mapImage ? ['url' => $mapImage['url'], 'downloadUrl' => $mapImage['download_url'], 'name' => $mapImage['name']] : null,
            'googleMap' => $googleMap,
            'fontsUrl' => PageTextStyle::stylesheetUrlFor(array_unique($fonts)),
            'formEnabled' => ContactusSetting::formEnabled($settings),
            'formFields' => ContactusSetting::formFields($settings),
        ];
    }
}
