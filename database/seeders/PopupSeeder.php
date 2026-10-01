<?php

namespace Database\Seeders;

use App\Models\ArticleItemDetail;
use App\Models\PopupItemInfo;
use App\Models\PopupItemPart;
use App\Models\PopupItemPartDetail;
use Database\Seeders\Support\SampleFiles;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Popup ตัวอย่าง 1 รายการ — modal ยินดีต้อนรับ แสดงเฉพาะเมนู "หน้าแรก" (part รูป + ข้อความ, ไม่แสดงวันนี้อีกได้)
 * ต้องรันหลัง FrontMenuSeeder. เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class PopupSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    public function run(): void
    {
        $adminId = $this->adminId();

        $popup = PopupItemInfo::create([
            'name' => 'ยินดีต้อนรับ (หน้าแรก)',
            'display_type' => 'modal',
            'show_dismiss_today' => 'Y',
            'show_arrows' => 'Y',
            'show_dots' => 'Y',
            'autoplay' => 'N',
            'slide_interval' => 5,
            'slide_speed' => 500,
            'menu_mode' => 'selected',
            'publish_date' => now()->subDay()->startOfDay(),
            'publish_down' => null,
            'sort_order' => 1,
            'status' => 'Y',
            'is_temp' => 'Y',
            'created_by' => $adminId,
        ]);

        $popup->menus()->attach(FrontMenuSeeder::id('home'), ['created_by' => $adminId, 'updated_by' => $adminId]);

        $part = PopupItemPart::create([
            'popup_item_info_id' => $popup->id,
            'part_type' => 'image_text',
            'image_id' => SampleFiles::import('images/popup/welcome.jpg', 'ตัวอย่าง - Popup'),
            'image_size' => 'full',
            'url' => '/article/item/'.ArticleItemDetail::where('slug', 'introducing-microcms')->value('id').'/introducing-microcms',
            'link_target' => '_self',
            'sort_order' => 0,
            'status' => 'Y',
            'created_by' => $adminId,
        ]);

        $this->createDetails(PopupItemPartDetail::class, $part->id, fn (string $lang) => [
            'detail' => $this->t([
                'th' => '<h3>ยินดีต้อนรับสู่เว็บไซต์ตัวอย่าง</h3><p>ทุกอย่างที่เห็นสร้างจากข้อมูลตัวอย่างของ MicroCMS — ข่าวสาร คู่มือการใช้งาน และการตั้งค่าบริการภายนอก คลิกที่รูปเพื่ออ่านแนะนำระบบ</p>',
                'en' => '<h3>Welcome to the sample website</h3><p>Everything here is built from the MicroCMS sample data — news, user guides and external service setup. Click the image to read about the system.</p>',
            ], $lang),
            'created_by' => $adminId,
        ]);
    }
}
