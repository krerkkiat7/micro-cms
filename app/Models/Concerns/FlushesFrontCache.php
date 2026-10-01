<?php

namespace App\Models\Concerns;

use App\Support\Front\FrontCache;

/**
 * ล้าง cache หน้าบ้านทั้งหมด (App\Support\Front\FrontCache) ทุกครั้งที่บันทึก/ลบ/กู้คืนแถวของ model ที่หน้าบ้านใช้แสดงผล
 * (เมนู, template, intropage, หน้าเพจ, บทความ, banner, ไฟล์) — หลังบ้านบันทึกแล้วหน้าบ้านเห็นทันทีโดยไม่ต้องกดล้างแคช
 *
 * หมายเหตุ: การอัปเดตแบบ query builder (Model::where()->update()) ไม่ผ่าน model event — จุดที่ทำแบบนั้นมักมีการ save model
 * อื่นใน request เดียวกันอยู่แล้ว (เช่น สลับ is_home/สถานะ template) และ TTL ของ cache (config front.cache) เป็นตาข่ายกันพลาดอีกชั้น
 * ยอดเข้าชม (view_amount) ตั้งใจอัปเดตผ่าน query builder เพื่อไม่ให้ล้าง cache ทุกครั้งที่มีคนเข้าชม
 */
trait FlushesFrontCache
{
    public static function bootFlushesFrontCache(): void
    {
        foreach (['saved', 'deleted', 'restored'] as $event) {
            static::registerModelEvent($event, function ($model) use ($event) {
                // save() ที่ไม่มีอะไรเปลี่ยน (Eloquent ยิง saved ให้เสมอ) ไม่ต้องล้าง cache
                if ($event === 'saved' && ! $model->wasRecentlyCreated && ! $model->wasChanged()) {
                    return;
                }

                FrontCache::flushForChange();
            });
        }
    }
}
