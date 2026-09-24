<?php

namespace App\Support\Front;

/**
 * ตัวสร้าง URL หน้าบ้าน — slug ไม่บังคับ (มีก็ใส่ ไม่มีก็ตัดท้ายทิ้ง) ถ้า slug มี "/" จะใช้ไม่ได้ใน path (route param ไม่รับ /)
 * จึงตัดทิ้งให้เหลือ URL ที่มีแต่ id (ยังเปิดได้)
 */
final class FrontUrl
{
    public static function page(string $lang, int $id, ?string $slug = null): string
    {
        return route('front.page.item', self::params(['lang' => $lang, 'id' => $id], $slug));
    }

    public static function articleCategory(string $lang, int $id, ?string $slug = null): string
    {
        return route('front.article.category', self::params(['lang' => $lang, 'id' => $id], $slug));
    }

    public static function articleCategoryItem(string $lang, int $categoryId, int $articleId, ?string $slug = null): string
    {
        return route('front.article.category.item', self::params(['lang' => $lang, 'id' => $categoryId, 'article_id' => $articleId], $slug));
    }

    public static function articleItem(string $lang, int $id, ?string $slug = null): string
    {
        return route('front.article.item', self::params(['lang' => $lang, 'id' => $id], $slug));
    }

    public static function home(string $lang): string
    {
        return route('front.home', ['lang' => $lang]);
    }

    /**
     * ลิงก์ภายนอกที่ปลอดภัย (กัน javascript:/data: ฯลฯ) — รับ http(s)://, path ภายใน (/...), #anchor, mailto:, tel:
     */
    public static function safeExternal(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        return preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $url) ? $url : null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private static function params(array $params, ?string $slug): array
    {
        $slug = trim((string) $slug);

        if ($slug !== '' && ! str_contains($slug, '/')) {
            $params['slug'] = $slug;
        }

        return $params;
    }
}
