<?php

namespace App\Support\Template;

/**
 * แม่แบบตั้งต้นให้เลือกตอนเพิ่ม template — แต่ละแม่แบบ = ค่าเริ่มต้นของทุกโซน (TemplateZone::defaults()) ทับด้วยค่าเฉพาะแม่แบบ
 * หลังสร้างแล้วปรับทุกอย่างต่อได้อิสระที่แท็บ "โครงสร้าง" (แม่แบบเป็นแค่จุดเริ่มต้น ไม่ผูกกันต่อ)
 * ภาพตัวอย่างของแต่ละแม่แบบวาดเป็น SVG ที่ resources/js/Components/Admin/Template/PresetPicker.vue — เพิ่มแม่แบบใหม่ต้องเพิ่มทั้งสองที่
 */
final class TemplatePreset
{
    public const DEFAULT = 'classic';

    /** ป้ายชื่อ/คำอธิบาย (ฝั่งหน้าจอมีชุดเดียวกันใน utils/template.ts — TEMPLATE_PRESETS) */
    public const OPTIONS = [
        'classic' => 'องค์กร / หน่วยงาน',
        'corporate' => 'แถวเมนูเด่น',
        'centered' => 'จัดกึ่งกลาง',
        'minimal' => 'เรียบง่าย',
        'dark' => 'โทนมืด',
    ];

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::OPTIONS);
    }

    /**
     * ค่าตั้งค่าครบทุกโซนของแม่แบบ — [โซน => [คอลัมน์ => ค่า]] (แม่แบบที่ไม่รู้จักใช้ค่าเริ่มต้นล้วน)
     *
     * @return array<string, array<string, mixed>>
     */
    public static function zones(?string $preset): array
    {
        $overrides = self::overrides()[$preset] ?? [];
        $zones = [];

        foreach (TemplateZone::ZONES as $zone) {
            $zones[$zone] = array_replace(TemplateZone::defaults($zone), $overrides[$zone] ?? []);
        }

        return $zones;
    }

    /**
     * ค่าที่ต่างจากค่าเริ่มต้นของแต่ละแม่แบบ ("classic" = ค่าเริ่มต้นของ TemplateZone เกือบทั้งหมด)
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function overrides(): array
    {
        return [
            // แถบบนสีเข้ม (social / ภาษา / เครื่องมือช่วยอ่าน) + แถบหลักสีขาว โลโก้ซ้าย เมนูขวา, footer 3 ส่วน
            'classic' => [],

            // แถบหลักสีขาว + แถวเมนูสีแบรนด์เต็มจอ (เหมาะกับเว็บที่มีเมนูเยอะ), footer แบบบล็อกพื้นอ่อน
            'corporate' => [
                'header' => [
                    'layout_type' => 'main_menubar',
                    'menu_align' => 'left',
                    'menu_style' => 'pill',
                    'menu_text_color' => '#FFFFFF',
                    'menu_active_color' => '#FFFFFF',
                    'menubar_background_color' => '#1D4ED8',
                    'lang_display' => 'code',
                ],
                'body' => ['background_color' => '#FFFFFF'],
                'footer' => [
                    'layout_type' => 'site_contact_block',
                    'background_color' => '#F3F4F6',
                    'heading_color' => '#111827',
                    'text_color' => '#4B5563',
                    'copyright_background_color' => '#E5E7EB',
                    'copyright_text_color' => '#4B5563',
                ],
                'aside' => ['menu_style' => 'list'],
            ],

            // โลโก้และเมนูกึ่งกลาง คั่นเมนูด้วยเส้นตั้ง, footer จัดกึ่งกลาง
            'centered' => [
                'header' => [
                    'layout_type' => 'main_menubar',
                    'logo_align' => 'center',
                    'menu_align' => 'center',
                    'menu_style' => 'divider',
                    'menubar_width' => 'container',
                    'menubar_background_color' => '#FFFFFF',
                    'fontsize_status' => 'N',
                    'contrast_status' => 'N',
                ],
                'footer' => [
                    'layout_type' => 'site_contact_center',
                    'background_color' => '#0F172A',
                    'show_fax' => 'N',
                ],
                'aside' => ['toggle_position' => 'left', 'menu_style' => 'drilldown'],
            ],

            // แถบหลักแถบเดียว ติดด้านบนตอนเลื่อน เมนูตัวอักษรเรียบ, aside เต็มจอตัวใหญ่
            'minimal' => [
                'header' => [
                    'layout_type' => 'main_only',
                    'sticky' => 'Y',
                    'logo_display' => 'image',
                    'menu_style' => 'plain',
                    'lang_display' => 'code',
                    'lang_select' => 'all',
                    'social_status' => 'N',
                    'fontsize_status' => 'N',
                    'contrast_status' => 'N',
                ],
                'body' => ['background_color' => '#FFFFFF'],
                'footer' => [
                    'layout_type' => 'site_contact_center',
                    'background_color' => '#FFFFFF',
                    'heading_color' => '#111827',
                    'text_color' => '#6B7280',
                    'show_fax' => 'N',
                    'show_mobile' => 'N',
                    'copyright_background_color' => '#FFFFFF',
                    'copyright_text_color' => '#9CA3AF',
                ],
                'aside' => ['display_type' => 'fullscreen', 'menu_style' => 'large'],
            ],

            // header/footer/aside โทนมืด เนื้อหาพื้นอ่อน
            'dark' => [
                'header' => [
                    'layout_type' => 'main_only',
                    'menu_style' => 'pill',
                    'menu_text_color' => '#E5E7EB',
                    'menu_active_color' => '#FFFFFF',
                    'main_text_color' => '#F9FAFB',
                    'background_color' => '#111827',
                    'main_width' => 'full',
                ],
                'body' => ['background_color' => '#F3F4F6'],
                'footer' => [
                    'background_color' => '#030712',
                    'copyright_background_color' => '#000000',
                ],
                'aside' => ['background_color' => '#111827', 'text_color' => '#F9FAFB'],
            ],
        ];
    }
}
