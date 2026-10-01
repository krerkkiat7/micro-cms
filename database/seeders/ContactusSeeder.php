<?php

namespace Database\Seeders;

use App\Support\ContactusSetting;
use App\Support\Setting;
use Database\Seeders\Support\SampleFiles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ตั้งค่าโมดูลติดต่อเรา (sys_setting กลุ่ม contactus) — ค่าเริ่มต้นจาก ContactusSetting::defaults() แล้วปรับเป็นชุดสาธิต:
 * แสดงข้อมูลติดต่อทุกส่วน + social, รูปแผนที่ (ไม่ต้องมี Google Maps API key), แบบฟอร์มครบทุกฟิลด์
 * (แบบฟอร์มแสดงได้เพราะ SiteSettingSeeder ใส่คีย์ทดสอบของ Turnstile ไว้). ไม่มีข้อความติดต่อตัวอย่าง
 * เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class ContactusSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $values = array_merge(ContactusSetting::defaults(), [
            'display_type' => 'split_info',
            'show_social' => 'Y',
            'owner_color' => '#0F2A5C',
            'owner_font_family' => 'Prompt',
            'show_map_image' => 'Y',
            'map_image_id' => (string) SampleFiles::import('images/contactus/map.jpg', 'ตัวอย่าง - ติดต่อเรา'),
            'show_google_map' => 'N',
            'show_form' => 'Y',
            'form_position_show' => 'Y',
            'form_company_show' => 'Y',
            'form_phone_show' => 'Y',
            'form_phone_required' => 'Y',
        ]);

        $unknown = array_diff_key($values, ContactusSetting::defaults());
        if ($unknown !== []) {
            throw new \RuntimeException('ContactusSetting: unknown keys '.implode(', ', array_keys($unknown)));
        }

        DB::table('sys_setting')->upsert(
            collect($values)->map(fn ($value, $name) => [
                'group' => 'contactus',
                'name' => $name,
                'value' => $value !== '' ? (string) $value : null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all(),
            ['group', 'name'],
            ['value', 'updated_at'],
        );

        Setting::forget('contactus');
    }
}
