<?php

namespace App\Support;

use App\Support\PageWidget\CategoryListWidget;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ทะเบียนตั้งค่าโมดูลบทความ (sys_setting group = "article") ที่เดียว — ค่าเริ่มต้น + validation rules ของทุกคีย์
 * ใช้ทั้งฝั่งหลังบ้าน (UpdateArticleSettingRequest / ArticleSettingController / ArticleSeeder)
 * และฝั่งหน้าบ้าน (หน้ารายการบทความของหมวดหมู่/แท็ก + หน้ารายละเอียดบทความ)
 * เพิ่มคีย์ใหม่ = เพิ่มใน defaults() + rules() + ฟอร์ม `Pages/Admin/Article/Setting/Index.vue`
 * ตัวเลือกรูปภาพ/จำนวนบรรทัดใช้ค่าคงที่ชุดเดียวกับ widget ของโมดูล page (CategoryListWidget)
 */
class ArticleSetting
{
    /** ตัวเลือกการเรียงลำดับรายการบทความ (หน้าบ้านเปลี่ยนได้ผ่าน ?sort=) */
    public const SORTS = ['newest', 'oldest', 'title_asc', 'title_desc', 'views_desc', 'views_asc'];

    /** ตำแหน่งปุ่มแชร์ในหน้ารายละเอียดบทความ */
    public const SHARE_POSITIONS = ['top', 'bottom', 'both', 'none'];

    /** อัตราส่วนรูปของมุมมองแถว — เพิ่ม natural (ตามขนาดของรูป ไม่ครอป) จากชุดของการ์ด */
    public const NATURAL_ASPECT = 'natural';

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        $view = [
            'aspect_ratio' => '16:9',
            'image_fit' => 'cover',
            'image_background' => CategoryListWidget::DEFAULT_IMAGE_BACKGROUND,
            'title_lines' => '1',
            'intro_lines' => '2',
        ];

        return [
            // รายการบทความ
            'list_show_category_intro' => 'N',
            'list_show_category_detail' => 'N',
            'list_per_page' => '10',
            'list_display_mode' => 'card',
            'list_default_sort' => 'newest',
            'list_show_date' => 'Y',
            'list_show_views' => 'Y',
            ...self::prefixed('card_', $view),
            ...self::prefixed('row_', $view),
            // รายละเอียดบทความ
            'detail_show_cover' => 'N',
            'detail_show_print' => 'N',
            'detail_share_position' => 'bottom',
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        $yesNo = ['required', Rule::in(['Y', 'N'])];
        $lines = ['required', 'integer', 'between:'.CategoryListWidget::LINES_MIN.','.CategoryListWidget::LINES_MAX];
        $color = ['required', 'string', 'max:20', 'regex:/^(transparent|#[0-9a-fA-F]{3,8})$/'];
        $fit = ['required', Rule::in(CategoryListWidget::IMAGE_FITS)];

        return [
            'list_show_category_intro' => $yesNo,
            'list_show_category_detail' => $yesNo,
            'list_per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'list_display_mode' => ['required', Rule::in(['card', 'row'])],
            'list_default_sort' => ['required', Rule::in(self::SORTS)],
            'list_show_date' => $yesNo,
            'list_show_views' => $yesNo,
            'card_aspect_ratio' => ['required', Rule::in(CategoryListWidget::ASPECT_RATIOS)],
            'card_image_fit' => $fit,
            'card_image_background' => $color,
            'card_title_lines' => $lines,
            'card_intro_lines' => $lines,
            'row_aspect_ratio' => ['required', Rule::in([...CategoryListWidget::ASPECT_RATIOS, self::NATURAL_ASPECT])],
            'row_image_fit' => $fit,
            'row_image_background' => $color,
            'row_title_lines' => $lines,
            'row_intro_lines' => $lines,
            'detail_show_cover' => $yesNo,
            'detail_show_print' => $yesNo,
            'detail_share_position' => ['required', Rule::in(self::SHARE_POSITIONS)],
        ];
    }

    /**
     * ค่าตั้งค่าทุกคีย์ (ค่าที่บันทึกไว้ ทับค่าเริ่มต้น) — ค่าที่บันทึกไว้แต่ไม่ผ่าน rules (เช่น ข้อมูลเก่า/แก้ใน DB ตรง ๆ) ใช้ค่าเริ่มต้นแทน
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $rules = self::rules();
        $values = [];

        foreach (self::defaults() as $name => $default) {
            $value = (string) Setting::get('article', $name, $default);
            $values[$name] = Validator::make([$name => $value], [$name => $rules[$name]])->passes() ? $value : $default;
        }

        return $values;
    }

    /**
     * ตั้งค่าที่หน้าบ้านใช้แสดงรายการบทความ (หมวดหมู่ / แท็ก)
     *
     * @return array<string, mixed>
     */
    public static function listSetting(): array
    {
        $all = self::all();

        $view = fn (string $prefix) => [
            'aspect_ratio' => $all[$prefix.'aspect_ratio'],
            'image_fit' => $all[$prefix.'image_fit'],
            'image_background' => $all[$prefix.'image_background'],
            'title_lines' => (int) $all[$prefix.'title_lines'],
            'intro_lines' => (int) $all[$prefix.'intro_lines'],
        ];

        return [
            'show_category_intro' => $all['list_show_category_intro'] === 'Y',
            'show_category_detail' => $all['list_show_category_detail'] === 'Y',
            'per_page' => (int) $all['list_per_page'],
            'display_mode' => $all['list_display_mode'],
            'default_sort' => $all['list_default_sort'],
            'show_date' => $all['list_show_date'] === 'Y',
            'show_views' => $all['list_show_views'] === 'Y',
            'card' => $view('card_'),
            'row' => $view('row_'),
        ];
    }

    /**
     * ตั้งค่าที่หน้าบ้านใช้แสดงรายละเอียดบทความ
     *
     * @return array{show_cover: bool, show_print: bool, share_position: string}
     */
    public static function detailSetting(): array
    {
        $all = self::all();

        return [
            'show_cover' => $all['detail_show_cover'] === 'Y',
            'show_print' => $all['detail_show_print'] === 'Y',
            'share_position' => $all['detail_share_position'],
        ];
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    private static function prefixed(string $prefix, array $fields): array
    {
        $out = [];
        foreach ($fields as $name => $value) {
            $out[$prefix.$name] = $value;
        }

        return $out;
    }
}
