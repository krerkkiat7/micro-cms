<?php

namespace App\Support\Front;

use App\Models\FileInfo;
use App\Models\SysTemplate;
use App\Support\AppAsset;
use App\Support\PageTextStyle;
use App\Support\Setting;
use App\Support\Template\TemplateZone;

/**
 * ข้อมูลของ layout หน้าบ้าน — template ที่เปิดใช้งาน (sys_template status = Y + 4 โซน), ข้อมูลไซต์/ติดต่อ/social/ลิขสิทธิ์
 * จากตั้งค่าระบบ, ภาษา, เมนูพร้อม URL และ URL หน้าแรก — แชร์ให้ทุกหน้าหน้าบ้านผ่าน prop `front` (ดู FrontController)
 *
 * ส่วนที่ใช้เฉพาะตอนโหลดหน้าเต็ม (Custom CSS/JS + หน้า Loading) แยกไว้ที่ assets() ให้ app.blade.php อ่านตรง ๆ
 * ไม่ส่งใน prop (ไม่ต้องส่งซ้ำทุกครั้งที่เปลี่ยนหน้าแบบ Inertia)
 */
final class FrontLayoutData
{
    /** คีย์ของ social ตามลำดับฟอร์มตั้งค่าระบบ + ชื่อที่แสดง (aria-label) */
    public const SOCIAL_LABELS = [
        'facebook' => 'Facebook',
        'youtube' => 'YouTube',
        'x' => 'X',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'line' => 'LINE',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function forLanguage(string $lang): array
    {
        $shared = FrontCache::remember("layout.{$lang}", (int) config('front.cache.shared_ttl', 3600), fn () => self::build($lang));

        return [
            ...$shared,
            'menu' => FrontMenuResolver::tree($lang),
            'homeUrl' => FrontMenuResolver::homeUrl($lang) ?? FrontUrl::home($lang),
            // ข้อความส่วนติดต่อผู้ใช้ (lang/<code>/front.php)
            't' => trans('front', [], $lang),
        ];
    }

    /**
     * Custom CSS/JS + หน้า Loading ของ template ที่เปิดใช้งาน (อ่านใน app.blade.php เฉพาะหน้าบ้าน)
     *
     * @return array{custom_css: string|null, custom_js: string|null, loading: array<string, mixed>|null}
     */
    public static function assets(): array
    {
        return FrontCache::remember('template.assets', (int) config('front.cache.shared_ttl', 3600), function () {
            $template = self::activeTemplate();

            if (! $template) {
                return ['custom_css' => null, 'custom_js' => null, 'loading' => null];
            }

            $loadingImage = $template->loading_type === 'image' ? $template->loadingImage : null;

            return [
                'custom_css' => $template->custom_css_status === 'Y' && trim((string) $template->custom_css) !== '' ? (string) $template->custom_css : null,
                'custom_js' => $template->custom_js_status === 'Y' && trim((string) $template->custom_js) !== '' ? (string) $template->custom_js : null,
                'loading' => $template->loading_status === 'Y' ? [
                    'show_logo' => $template->loading_show_logo === 'Y',
                    'logo_url' => AppAsset::logo() ? route('app.logo') : null,
                    'type' => $template->loading_type === 'image' && $loadingImage?->status === 'Y' ? 'image' : 'spinner',
                    'spinner' => $template->loading_spinner ?: 'ring',
                    'color' => $template->loading_color ?: '#2563EB',
                    'background_color' => $template->loading_background_color ?: '#FFFFFF',
                    'image_url' => $loadingImage?->status === 'Y' ? FrontFile::url($loadingImage->hash_name) : null,
                ] : null,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private static function build(string $lang): array
    {
        $template = self::activeTemplate();
        $zones = [];
        $fonts = [PageTextStyle::DEFAULT_FONT];

        foreach (TemplateZone::ZONES as $zone) {
            $model = $template?->{$zone};
            $values = TemplateZone::toArray($zone, $model);
            $image = $model?->backgroundImage;
            unset($values['background_image_id']);
            $values['background_image_url'] = $image instanceof FileInfo && $image->status === 'Y' ? FrontFile::url($image->hash_name) : null;
            $zones[$zone] = $values;

            foreach ($values as $key => $value) {
                if (str_ends_with($key, 'font_family')) {
                    $fonts[] = $value;
                }
            }
        }

        $social = Setting::group('social');

        return [
            'lang' => $lang,
            'languages' => Setting::selectedLanguages(),
            'defaultLanguage' => Setting::defaultLanguage(),
            'site' => [
                'name' => Setting::siteName(),
                'description' => Setting::get('site', 'site_description'),
                'logoUrl' => AppAsset::logo() ? route('app.logo') : null,
            ],
            'contact' => [
                'owner' => Setting::get('site', 'copyright_owner'),
                'address' => Setting::get('contact', "address_{$lang}") ?? Setting::get('contact', 'address_'.Setting::defaultLanguage()),
                'phone' => Setting::get('contact', 'phone'),
                'fax' => Setting::get('contact', 'fax'),
                'mobile' => Setting::get('contact', 'mobile'),
                'email' => Setting::get('contact', 'email'),
            ],
            'social' => collect(self::SOCIAL_LABELS)
                ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label, 'url' => self::socialUrl($key, $social[$key] ?? null)])
                ->filter(fn (array $item) => $item['url'] !== null)
                ->values()
                ->all(),
            'copyright' => [
                'year' => (string) Setting::get('site', 'copyright_year', now()->format('Y')),
                'owner' => (string) Setting::get('site', 'copyright_owner', Setting::siteName()),
            ],
            'template' => $zones,
            'fontsUrl' => PageTextStyle::stylesheetUrlFor(array_unique($fonts)),
        ];
    }

    private static function activeTemplate(): ?SysTemplate
    {
        return SysTemplate::query()
            ->where('status', 'Y')
            ->with(['header.backgroundImage', 'body.backgroundImage', 'footer.backgroundImage', 'aside.backgroundImage', 'loadingImage'])
            ->orderBy('id')
            ->first();
    }

    /**
     * URL ของช่องทาง social จากค่าที่ตั้งไว้ — ค่าเป็น URL (http/https) ใช้ตรง ๆ; LINE ที่กรอกเป็น ID (เช่น @shop) สร้างลิงก์เพิ่มเพื่อนให้;
     * ค่าอื่นที่ไม่ใช่ URL ไม่แสดง (ไม่รู้ว่าจะลิงก์ไปไหน)
     */
    private static function socialUrl(string $key, mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $value)) {
            return FrontUrl::safeExternal($value);
        }

        if ($key === 'line' && preg_match('/^@?[A-Za-z0-9._-]{2,50}$/', $value)) {
            return 'https://line.me/R/ti/p/'.rawurlencode(str_starts_with($value, '@') ? $value : '@'.$value);
        }

        return null;
    }
}
