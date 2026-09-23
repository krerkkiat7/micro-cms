/**
 * หมวดหมู่ (article_category_info) ไม่มีคอลัมน์สีของตัวเอง — ใช้ชุดสีคงที่ไล่ตาม id
 * (deterministic) เพื่อให้ badge หมวดหมู่ในรายการ/ตัวเลือกที่เลือกไว้ แยกแยะด้วยสายตาได้
 */
const PALETTE = [
    'bg-brand-50 text-brand-700',
    'bg-emerald-50 text-emerald-700',
    'bg-amber-50 text-amber-700',
    'bg-rose-50 text-rose-700',
    'bg-violet-50 text-violet-700',
    'bg-cyan-50 text-cyan-700',
    'bg-orange-50 text-orange-700',
    'bg-lime-50 text-lime-700',
];

/**
 * รับได้ทั้ง id (จากรายการที่ดึงมาสด ๆ) หรือชื่อหมวดหมู่ (จากค่าที่โหลดมาตอนแก้ไขเมนู ซึ่งมีแค่ label
 * ไม่มี id ติดมาด้วย) — แฮชเป็นดัชนีของ PALETTE ให้ผลเหมือนเดิมเสมอสำหรับ id/ชื่อเดียวกัน
 */
export function categoryBadgeClass(key: number | string | null | undefined): string {
    if (key === null || key === undefined || key === '') {
        return 'bg-gray-100 text-gray-600';
    }

    if (typeof key === 'number') {
        return PALETTE[Math.abs(key) % PALETTE.length];
    }

    let hash = 0;
    for (let i = 0; i < key.length; i++) {
        hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
    }

    return PALETTE[hash % PALETTE.length];
}
