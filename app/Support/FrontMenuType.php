<?php

namespace App\Support;

/**
 * ประเภทเมนูหน้าบ้าน — ค่าคงที่ + label ไทยที่ใช้ร่วมกันระหว่าง FormRequest, Controller และหน้าจอ Vue
 * (ดู docs/PRD-system-frontmenu.md) "heading" เป็นชนิดเดียวที่เลือกเป็น Parent Menu ได้ (ไม่มีลิงก์ของตัวเอง)
 */
class FrontMenuType
{
    public const NONE = 'none';

    public const HEADING = 'heading';

    public const EXTERNAL = 'external';

    public const ARTICLE_CATEGORY = 'article_category';

    public const ARTICLE_ITEM = 'article_item';

    public const PAGE = 'page';

    /** หน้าติดต่อเรา (/{lang}/contactus) — ไม่มี id ปลายทาง มีหน้าเดียวทั้งระบบ */
    public const CONTACTUS = 'contactus';

    /**
     * @var array<string, string>
     */
    public const OPTIONS = [
        self::NONE => 'ไม่กำหนด',
        self::HEADING => 'เมนูหัวข้อ',
        self::EXTERNAL => 'ลิงค์ภายนอก',
        self::ARTICLE_CATEGORY => 'บทความ - รายการบทความตามหมวดหมู่',
        self::ARTICLE_ITEM => 'บทความ - รายละเอียดบทความ',
        self::PAGE => 'หน้าเพจ',
        self::CONTACTUS => 'ติดต่อเรา',
    ];

    /** ประเภทที่มีลิงก์ปลายทางของตัวเอง (โมดูลเนื้อหา + ลิงค์ภายนอก) — เลือกเป็นปลายทางของปุ่ม "อ่านทั้งหมด" ได้ */
    public const LINKABLE = [self::EXTERNAL, self::ARTICLE_CATEGORY, self::ARTICLE_ITEM, self::PAGE, self::CONTACTUS];

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::OPTIONS);
    }
}
