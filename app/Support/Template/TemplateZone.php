<?php

namespace App\Support\Template;

use App\Models\SysTemplateAside;
use App\Models\SysTemplateBody;
use App\Models\SysTemplateFooter;
use App\Models\SysTemplateHeader;
use App\Support\PageTextStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * ทะเบียนฟิลด์ตั้งค่าของแต่ละโซนใน template (header / body / footer / aside) — ประกาศครั้งเดียวแล้วใช้ร่วมทั้ง
 * ค่าเริ่มต้น (สร้าง template ใหม่ + ฐานของแม่แบบ TemplatePreset), กฎ validation (UpdateTemplateLayoutRequest)
 * และรายชื่อคอลัมน์ที่บันทึก/ส่งให้หน้าโครงสร้าง (TemplateController) — เทียบเคียง SettingsWidget::fields() ของโมดูล page
 *
 * ค่า default ต้องตรงกับ default ของคอลัมน์ใน migration 2026_09_30_000001_create_sys_template_tables
 * ฟิลด์รูปพื้นหลังเก็บเป็น `background_image_id` (ฝั่งหน้าจอส่ง/รับเป็น FileItem[] ผ่าน `background_image`)
 */
final class TemplateZone
{
    public const ZONES = ['header', 'body', 'footer', 'aside'];

    /** model ของตารางตั้งค่าแต่ละโซน (PK = sys_template_id) */
    public const MODELS = [
        'header' => SysTemplateHeader::class,
        'body' => SysTemplateBody::class,
        'footer' => SysTemplateFooter::class,
        'aside' => SysTemplateAside::class,
    ];

    public const ALIGNS = ['left', 'center', 'right'];

    public const WIDTHS = ['full', 'container'];

    public const HEADER_LAYOUTS = ['topbar_main', 'main_menubar', 'main_only'];

    public const HEADER_MENU_STYLES = ['plain', 'underline', 'pill', 'divider'];

    public const FOOTER_LAYOUTS = ['site_contact_menu', 'site_contact_center', 'site_contact_block'];

    public const ASIDE_DISPLAYS = ['fullscreen', 'drawer'];

    public const ASIDE_MENU_STYLES = ['list', 'accordion', 'drilldown', 'large'];

    /** สีพื้นหลัง: hex หรือ transparent */
    public const COLOR_REGEX = '/^(transparent|#[0-9a-fA-F]{3,8})$/';

    /** สีตัวอักษร: hex เท่านั้น */
    public const TEXT_COLOR_REGEX = '/^#[0-9a-fA-F]{3,8}$/';

    /**
     * ฟิลด์ของโซน — ชื่อคอลัมน์ => [ค่าเริ่มต้น, กฎ validation]
     *
     * @return array<string, array{0: mixed, 1: array<int, mixed>}>
     */
    public static function fields(string $zone): array
    {
        return match ($zone) {
            'header' => [
                'status' => ['Y', self::yn()],
                'layout_type' => ['topbar_main', self::in(self::HEADER_LAYOUTS)],
                'sticky' => ['N', self::yn()],
                'logo_status' => ['Y', self::yn()],
                'logo_align' => ['left', self::in(self::ALIGNS)],
                'logo_display' => ['image_name', self::in(['image', 'image_name', 'name'])],
                'logo_action' => ['home', self::in(['none', 'home'])],
                'menu_align' => ['right', self::in(self::ALIGNS)],
                'menu_style' => ['underline', self::in(self::HEADER_MENU_STYLES)],
                'menu_text_color' => ['#1F2937', self::textColor()],
                'menu_active_color' => ['#2563EB', self::textColor()],
                'lang_status' => ['Y', self::yn()],
                'lang_display' => ['flag_code', self::in(['code', 'flag', 'flag_code'])],
                'lang_select' => ['dropdown', self::in(['all', 'dropdown'])],
                'social_status' => ['Y', self::yn()],
                // สงวนไว้ — ซ่อนใน UI จนกว่าหน้าบ้านจะมีหน้าค้นหา (ยังบันทึกค่าเดิม/ค่าเริ่มต้นไว้ตามปกติ)
                'search_status' => ['Y', self::yn()],
                'fontsize_status' => ['Y', self::yn()],
                'fontsize_display' => ['icon', self::in(['icon', 'text'])],
                'contrast_status' => ['Y', self::yn()],
                'contrast_display' => ['icon', self::in(['icon', 'text'])],
                'main_width' => ['container', self::in(self::WIDTHS)],
                'main_text_color' => ['#1F2937', self::textColor()],
                ...self::background('#FFFFFF'),
                'topbar_width' => ['full', self::in(self::WIDTHS)],
                'topbar_background_color' => ['#1E3A8A', self::color()],
                'topbar_text_color' => ['#FFFFFF', self::textColor()],
                'menubar_width' => ['full', self::in(self::WIDTHS)],
                'menubar_background_color' => ['#2563EB', self::color()],
            ],
            'body' => [
                ...self::background('#F9FAFB'),
            ],
            'footer' => [
                'status' => ['Y', self::yn()],
                'layout_type' => ['site_contact_menu', self::in(self::FOOTER_LAYOUTS)],
                'width' => ['full', self::in(self::WIDTHS)],
                ...self::background('#1F2937'),
                'heading_color' => ['#FFFFFF', self::textColor()],
                'heading_font_size' => [18, self::fontSize()],
                'heading_font_family' => [PageTextStyle::DEFAULT_FONT, self::font()],
                'heading_bold' => ['Y', self::yn()],
                'text_color' => ['#D1D5DB', self::textColor()],
                'text_font_size' => [14, self::fontSize()],
                'text_font_family' => [PageTextStyle::DEFAULT_FONT, self::font()],
                'text_bold' => ['N', self::yn()],
                'show_address' => ['Y', self::yn()],
                'show_phone' => ['Y', self::yn()],
                'show_fax' => ['Y', self::yn()],
                'show_mobile' => ['Y', self::yn()],
                'show_email' => ['Y', self::yn()],
                'show_social' => ['Y', self::yn()],
                'show_menu' => ['Y', self::yn()],
                'copyright_status' => ['Y', self::yn()],
                'copyright_width' => ['full', self::in(self::WIDTHS)],
                'copyright_background_color' => ['#111827', self::color()],
                'copyright_text_color' => ['#9CA3AF', self::textColor()],
                'copyright_font_size' => [13, self::fontSize()],
                'copyright_font_family' => [PageTextStyle::DEFAULT_FONT, self::font()],
                'copyright_align' => ['center', self::in(self::ALIGNS)],
                'copyright_show_owner' => ['Y', self::yn()],
            ],
            'aside' => [
                'status' => ['Y', self::yn()],
                'toggle_position' => ['right', self::in(['left', 'right'])],
                'display_type' => ['drawer', self::in(self::ASIDE_DISPLAYS)],
                ...self::background('#FFFFFF'),
                'text_color' => ['#1F2937', self::textColor()],
                'menu_style' => ['accordion', self::in(self::ASIDE_MENU_STYLES)],
            ],
        };
    }

    /**
     * ชื่อคอลัมน์ทั้งหมดของโซน (ตามลำดับที่ประกาศ)
     *
     * @return list<string>
     */
    public static function columns(string $zone): array
    {
        return array_keys(self::fields($zone));
    }

    /**
     * ค่าเริ่มต้นของโซน — คอลัมน์ => ค่า
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $zone): array
    {
        return array_map(fn (array $field) => $field[0], self::fields($zone));
    }

    /**
     * กฎ validation ของโซน โดยเติม prefix หน้าชื่อฟิลด์ (เช่น "header.")
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $zone, string $prefix = ''): array
    {
        $rules = [];

        foreach (self::fields($zone) as $column => [, $fieldRules]) {
            $rules[$prefix.$column] = $fieldRules;
        }

        return $rules;
    }

    /**
     * ดึงค่าตั้งค่าของโซนจาก model ในรูปแบบที่หน้าโครงสร้างใช้ — ไม่มีแถว (ข้อมูลเก่า/ผิดปกติ) ใช้ค่าเริ่มต้น
     *
     * @return array<string, mixed>
     */
    public static function toArray(string $zone, ?Model $model): array
    {
        $values = self::defaults($zone);

        if ($model !== null) {
            foreach ($values as $column => $default) {
                $values[$column] = $model->getAttribute($column) ?? $default;
            }
        }

        return $values;
    }

    /**
     * @return array<string, array{0: mixed, 1: array<int, mixed>}>
     */
    private static function background(string $color): array
    {
        return [
            'background_color' => [$color, ['nullable', 'string', 'max:20', 'regex:'.self::COLOR_REGEX]],
            'background_image_id' => [null, [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query->where('status', 'Y')->whereNull('deleted_at')),
            ]],
            'background_repeat' => [null, ['nullable', Rule::in(['repeat', 'no-repeat', 'repeat-x', 'repeat-y'])]],
            'background_size' => [null, ['nullable', Rule::in(['auto', 'cover', 'contain'])]],
            'background_attachment' => [null, ['nullable', Rule::in(['scroll', 'fixed'])]],
            'background_position' => [null, ['nullable', 'string', 'max:50']],
        ];
    }

    /** @return array<int, mixed> */
    private static function yn(): array
    {
        return ['required', Rule::in(['Y', 'N'])];
    }

    /**
     * @param  list<string>  $values
     * @return array<int, mixed>
     */
    private static function in(array $values): array
    {
        return ['required', Rule::in($values)];
    }

    /** @return array<int, mixed> */
    private static function color(): array
    {
        return ['required', 'string', 'max:20', 'regex:'.self::COLOR_REGEX];
    }

    /** @return array<int, mixed> */
    private static function textColor(): array
    {
        return ['required', 'string', 'max:20', 'regex:'.self::TEXT_COLOR_REGEX];
    }

    /** @return array<int, mixed> */
    private static function fontSize(): array
    {
        return ['required', 'integer', 'between:'.PageTextStyle::FONT_SIZE_MIN.','.PageTextStyle::FONT_SIZE_MAX];
    }

    /** @return array<int, mixed> */
    private static function font(): array
    {
        return ['required', Rule::in(PageTextStyle::fontNames())];
    }
}
