<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Setting;
use Database\Seeders\Support\SampleFiles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ตั้งค่าระบบตัวอย่าง — โลโก้/favicon, ข้อมูลติดต่อสมมติ, social, คีย์ทดสอบของ Cloudflare Turnstile + รูปโปรไฟล์ผู้ดูแล
 *
 * Turnstile ใช้ "test keys" ของ Cloudflare ที่ผ่านการตรวจสอบเสมอ (https://developers.cloudflare.com/turnstile/troubleshooting/testing/)
 * เพื่อให้เห็นแบบฟอร์มติดต่อเราได้ทันที — ต้องเปลี่ยนเป็นคีย์จริงก่อนใช้งานจริง. Google Analytics / Google Map / SMTP ไม่ใส่
 * (ค่าปลอมจะยิงไปบริการจริง) บทความหมวด "การตั้งค่าบริการภายนอก" อธิบายวิธีตั้งค่าเอง. เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class SiteSettingSeeder extends Seeder
{
    use WithoutModelEvents;

    public const TURNSTILE_TEST_SITE_KEY = '1x00000000000000000000AA';

    public const TURNSTILE_TEST_SECRET = '1x0000000000000000000000000000000AA';

    public function run(): void
    {
        $folder = 'ตัวอย่าง - ตั้งค่าระบบ';

        $settings = [
            'site' => [
                'logo_id' => SampleFiles::import('images/site/logo.png', $folder, 'microcms-logo.png'),
                'favicon_id' => SampleFiles::import('images/site/favicon.ico', $folder, 'microcms-favicon.ico'),
                'copyright_year' => now()->format('Y'),
                'copyright_owner' => 'MicroCMS',
            ],
            'contact' => [
                'address_th' => '99/9 อาคารไมโครทาวเวอร์ ชั้น 12 ถนนตัวอย่าง แขวงตัวอย่าง เขตตัวอย่าง กรุงเทพมหานคร 10000',
                'address_en' => '99/9 Micro Tower, 12th Floor, Sample Road, Sample District, Bangkok 10000, Thailand',
                'phone' => '02-000-0000',
                'fax' => '02-000-0001',
                'mobile' => '080-000-0000',
                'email' => 'info@microcms.com',
            ],
            'social' => [
                'facebook' => 'https://www.facebook.com/',
                'youtube' => 'https://www.youtube.com/',
                'x' => 'https://x.com/',
                'instagram' => 'https://www.instagram.com/',
                'tiktok' => 'https://www.tiktok.com/',
                'line' => 'https://line.me/',
            ],
            'turnstile' => [
                'site_key' => self::TURNSTILE_TEST_SITE_KEY,
                // hook saving ของ SysSetting (เข้ารหัสค่าลับ) ไม่ทำงานใต้ WithoutModelEvents/upsert — เข้ารหัสเอง
                'key_secret' => Setting::encryptSecret(self::TURNSTILE_TEST_SECRET),
            ],
        ];

        $rows = [];
        foreach ($settings as $group => $values) {
            foreach ($values as $name => $value) {
                $rows[] = ['group' => $group, 'name' => $name, 'value' => (string) $value, 'created_at' => now(), 'updated_at' => now()];
            }
        }

        DB::table('sys_setting')->upsert($rows, ['group', 'name'], ['value', 'updated_at']);

        foreach (array_keys($settings) as $group) {
            Setting::forget($group);
        }

        User::where('id', SampleFiles::adminId())->update([
            'profile_image_id' => SampleFiles::import('images/site/avatar-admin.jpg', $folder, 'admin-avatar.jpg'),
        ]);
    }
}
