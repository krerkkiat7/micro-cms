<?php

namespace Database\Seeders\Support;

use App\Support\Setting;
use Illuminate\Database\Eloquent\Model;

/**
 * ตัวช่วยร่วมของ seeder ข้อมูลตัวอย่าง — ภาษาที่เปิดใช้ + เลือกข้อความตามภาษา (ไม่มีคำแปล = ใช้ภาษาไทย)
 */
trait SeedsSampleData
{
    /** @var list<string>|null */
    private ?array $sampleLanguages = null;

    /**
     * @return list<string>
     */
    protected function languages(): array
    {
        return $this->sampleLanguages ??= (Setting::selectedLanguages() ?: ['th', 'en']);
    }

    /**
     * @param  array<string, mixed>|null  $values  ข้อความแยกภาษา ['th' => ..., 'en' => ...]
     */
    protected function t(?array $values, string $lang): mixed
    {
        if ($values === null) {
            return null;
        }

        return $values[$lang] ?? $values['th'] ?? reset($values);
    }

    /**
     * สร้างแถวข้อมูลแยกภาษา (ตาราง *_detail, PK = id + lang) ครบทุกภาษาที่เปิดใช้
     *
     * @param  class-string<Model>  $model
     * @param  callable(string): array<string, mixed>  $values  คืนค่าคอลัมน์ของภาษานั้น
     */
    protected function createDetails(string $model, int $id, callable $values): void
    {
        foreach ($this->languages() as $lang) {
            $model::create(['id' => $id, 'lang' => $lang] + $values($lang));
        }
    }

    protected function adminId(): ?int
    {
        return SampleFiles::adminId();
    }
}
