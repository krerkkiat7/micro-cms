<?php

namespace Database\Seeders;

use App\Support\ContactusSetting;
use App\Support\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * โมดูลติดต่อเรา — ค่าตั้งต้นของตั้งค่า (sys_setting กลุ่ม contactus) จาก ContactusSetting::defaults()
 * ไม่มีข้อมูลตัวอย่างของข้อความติดต่อ (contactus_item มาจากผู้ชมหน้าบ้านเท่านั้น)
 *
 * รันซ้ำได้ — insertOrIgnore ไม่ทับค่าที่ตั้งไว้แล้ว
 * รันแยก: php artisan db:seed --class=ContactusSeeder
 */
class ContactusSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (ContactusSetting::defaults() as $name => $value) {
            $rows[] = [
                'group' => 'contactus',
                'name' => $name,
                'value' => $value !== '' ? $value : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('sys_setting')->insertOrIgnore($rows);

        Setting::forget('contactus');
    }
}
