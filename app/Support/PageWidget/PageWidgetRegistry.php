<?php

namespace App\Support\PageWidget;

use App\Support\FrontMenuTree;

/**
 * ทะเบียนประเภท widget ของโมดูล Page — จุดเดียวที่ต้องเพิ่มเมื่อมีประเภทใหม่ (ควบคู่กับ utils/pageWidget.ts ฝั่งหน้าจอ)
 */
class PageWidgetRegistry
{
    /** @var array<string, PageWidgetType>|null */
    private static ?array $types = null;

    /**
     * @return array<string, PageWidgetType> keyed ด้วยชื่อประเภท
     */
    public static function all(): array
    {
        if (self::$types === null) {
            self::$types = [];

            foreach ([new SlideshowBannerWidget, new SlideshowArticleWidget, new SlidesetArticleWidget, new SlidesetBannerWidget, new GridArticleWidget, new GridBannerWidget, new CustomTextWidget] as $type) {
                self::$types[$type->type()] = $type;
            }
        }

        return self::$types;
    }

    public static function find(?string $type): ?PageWidgetType
    {
        return $type === null ? null : (self::all()[$type] ?? null);
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::all());
    }

    /**
     * relation ของแถวตั้งค่าทุกประเภทบน PageItemWidget — ใส่ prefix ได้ (เช่น `columns.widgets.`) เพื่อใช้กับ with()
     *
     * @return list<string>
     */
    public static function relations(string $prefix = ''): array
    {
        $relations = [];

        foreach (self::all() as $type) {
            foreach ($type->eagerRelations() as $relation) {
                $relations[] = $prefix.$relation;
            }
        }

        return $relations;
    }

    /**
     * ข้อมูลประกอบฟอร์มตั้งค่าของทุกประเภท รวมเป็น array เดียว
     *
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::all() as $type) {
            $options += $type->options();
        }

        // เมนูหน้าบ้านให้เลือกเป็นลิงก์ปลายทางของปุ่ม "อ่านทั้งหมด" (ใช้ร่วมทุกประเภทที่มีปุ่มนี้ — ดู HasReadAllButton)
        $options['front_menus'] = FrontMenuTree::pickerOptions();

        return $options;
    }
}
