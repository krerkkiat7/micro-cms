<?php

namespace Database\Seeders;

use App\Models\SysTemplate;
use App\Support\Template\TemplatePreset;
use App\Support\Template\TemplateZone;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Template ตัวอย่าง 1 รายการ (ใช้งานอยู่) โทนน้ำเงิน — ตั้งต้นจากแม่แบบ classic แล้วเปิดความสามารถให้เห็นมากที่สุด:
 * แถบบน + ส่วนหัวติดด้านบน, เมนูแบบ pill, เลือกภาษาแบบธง, social, ปรับขนาดตัวอักษร/ความคมชัด, ส่วนท้ายพร้อมเมนู,
 * เมนูด้านข้างแบบ drawer, หน้า Loading และ Custom CSS. เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class TemplateSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    public const NAME = 'MicroCMS Blue';

    private const ZONES = [
        'header' => [
            'layout_type' => 'topbar_main',
            'sticky' => 'Y',
            'logo_status' => 'Y',
            'logo_align' => 'left',
            'logo_display' => 'image_name',
            'logo_action' => 'home',
            'menu_align' => 'right',
            'menu_style' => 'pill',
            'menu_text_color' => '#1E293B',
            'menu_active_color' => '#2563EB',
            'lang_status' => 'Y',
            'lang_display' => 'flag_code',
            'lang_select' => 'dropdown',
            'social_status' => 'Y',
            'fontsize_status' => 'Y',
            'fontsize_display' => 'icon',
            'contrast_status' => 'Y',
            'contrast_display' => 'icon',
            'main_width' => 'container',
            'main_text_color' => '#0F2A5C',
            'background_color' => '#FFFFFF',
            'topbar_width' => 'full',
            'topbar_background_color' => '#1E3A8A',
            'topbar_text_color' => '#FFFFFF',
        ],
        'body' => [
            'background_color' => '#F5F8FF',
        ],
        'footer' => [
            'status' => 'Y',
            'layout_type' => 'site_contact_menu',
            'width' => 'container',
            'background_color' => '#0F2A5C',
            'heading_color' => '#FFFFFF',
            'heading_font_family' => 'Prompt',
            'text_color' => '#C7D7F5',
            'show_address' => 'Y',
            'show_phone' => 'Y',
            'show_fax' => 'Y',
            'show_mobile' => 'Y',
            'show_email' => 'Y',
            'show_social' => 'Y',
            'show_menu' => 'Y',
            'copyright_status' => 'Y',
            'copyright_width' => 'full',
            'copyright_background_color' => '#0B1F44',
            'copyright_text_color' => '#93A9D1',
            'copyright_align' => 'center',
            'copyright_show_owner' => 'Y',
        ],
        'aside' => [
            'status' => 'Y',
            'toggle_position' => 'right',
            'display_type' => 'drawer',
            'background_color' => '#FFFFFF',
            'text_color' => '#0F2A5C',
            'menu_style' => 'accordion',
        ],
    ];

    private const CUSTOM_CSS = <<<'CSS'
/* ตัวอย่าง Custom CSS — เลื่อนหน้าแบบนุ่มนวล และสีเมื่อเลือกข้อความเป็นโทนน้ำเงิน */
html { scroll-behavior: smooth; }
::selection { background: #BFDBFE; color: #0F2A5C; }
CSS;

    public function run(): void
    {
        $template = SysTemplate::create([
            'name' => self::NAME,
            'preset' => 'classic',
            'custom_css_status' => 'Y',
            'custom_css' => self::CUSTOM_CSS,
            'custom_js_status' => 'N',
            'loading_status' => 'Y',
            'loading_show_logo' => 'Y',
            'loading_type' => 'spinner',
            'loading_spinner' => 'dots',
            'loading_color' => '#2563EB',
            'loading_background_color' => '#FFFFFF',
            'layout_updated_at' => now(),
            'layout_updated_by' => $this->adminId(),
            'status' => 'Y',
            'created_by' => $this->adminId(),
        ]);

        foreach (TemplatePreset::zones('classic') as $zone => $values) {
            $overrides = self::ZONES[$zone] ?? [];

            // กันพิมพ์ชื่อคอลัมน์ผิด — ทุกคีย์ต้องมีอยู่ในทะเบียนฟิลด์ของโซน (TemplateZone)
            $unknown = array_diff_key($overrides, $values);
            if ($unknown !== []) {
                throw new RuntimeException("Template zone {$zone}: unknown fields ".implode(', ', array_keys($unknown)));
            }

            TemplateZone::MODELS[$zone]::create(['sys_template_id' => $template->id] + $overrides + $values);
        }
    }
}
