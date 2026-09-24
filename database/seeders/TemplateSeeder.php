<?php

namespace Database\Seeders;

use App\Models\SysTemplate;
use App\Support\Template\TemplatePreset;
use App\Support\Template\TemplateZone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่าง Template หน้าบ้าน — 3 รายการจากแม่แบบตั้งต้น (TemplatePreset) โดย "องค์กร / หน่วยงาน" เป็นรายการที่ใช้งาน
 * รันซ้ำได้: ค้นด้วยชื่อแล้ว updateOrCreate ทั้งตัว template และตั้งค่าทั้ง 4 โซน (ค่าในโซนถูกรีเซ็ตกลับเป็นค่าของแม่แบบ)
 * ถ้ามี template อื่นที่ผู้ใช้เปิดใช้งานไว้แล้ว จะไม่แย่งสถานะใช้งาน (ต้องมีรายการใช้งานได้ครั้งละ 1 รายการ)
 *
 * รันเดี่ยว: php artisan db:seed --class=TemplateSeeder
 */
class TemplateSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $samples = [
            ['name' => 'Template องค์กร / หน่วยงาน', 'preset' => 'classic', 'active' => true],
            ['name' => 'Template แถวเมนูเด่น', 'preset' => 'corporate', 'active' => false],
            ['name' => 'Template เรียบง่าย', 'preset' => 'minimal', 'active' => false],
        ];

        $sampleNames = array_column($samples, 'name');
        $otherActive = SysTemplate::where('status', 'Y')->whereNotIn('name', $sampleNames)->exists();

        foreach ($samples as $sample) {
            $template = SysTemplate::updateOrCreate(
                ['name' => $sample['name']],
                [
                    'preset' => $sample['preset'],
                    'status' => $sample['active'] && ! $otherActive ? 'Y' : 'N',
                ],
            );

            foreach (TemplatePreset::zones($sample['preset']) as $zone => $values) {
                $modelClass = TemplateZone::MODELS[$zone];
                $modelClass::updateOrCreate(['sys_template_id' => $template->id], $values);
            }
        }
    }
}
