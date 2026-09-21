<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetSlidesetArticle;
use App\Models\PageItemWidgetSlidesetArticleDetail;
use Illuminate\Database\Eloquent\Builder;

/**
 * widget "Slideset จาก article" — การ์ดบทความหลายใบที่เลื่อนดูได้ (รูป, หัวเรื่อง, ข้อความเกริ่นนำ, วันที่เผยแพร่, จำนวนเข้าชม — แต่ละส่วนเปิด/ปิดและ
 * จัดรูปแบบตัวอักษรเองได้) + ปุ่ม "อ่านทั้งหมด" (ตำแหน่ง ข้อความแยกภาษา ไอคอน รูปแบบ ลิงก์ปลายทาง) ตั้งค่าส่วนร่วมอยู่ใน SlidesetWidget
 * ต่างจาก Slideshow ตรงที่บทความที่ไม่มีรูปหน้าปกก็ยังแสดง (ไม่มีรูป = กรอบว่าง) และแต่ละส่วนของการ์ดกดลิงก์ไปหน้าบทความได้แยกกัน
 * ตาราง `page_item_widget_slidesetarticle` (+ `_detail` เก็บข้อความปุ่มแยกภาษา) PK = `page_item_widget.id`
 */
class SlidesetArticleWidget extends SlidesetWidget
{
    use ReadsArticles;

    public const TYPE = 'slidesetarticle';

    /** ตำแหน่งปุ่ม "อ่านทั้งหมด" เทียบกับการ์ด (บน/ล่าง × ซ้าย/กึ่งกลาง/ขวา) */
    public const READ_ALL_POSITIONS = ['top_left', 'top_center', 'top_right', 'bottom_left', 'bottom_center', 'bottom_right'];

    /** ไอคอนที่แสดงร่วมกับข้อความปุ่ม (`none` = ไม่แสดง) — ตัวเลือกและชื่อที่หน้าจอต้องตรงกัน */
    public const READ_ALL_ICONS = ['none', 'plus', 'plus_circle', 'arrow_right', 'arrow_right_circle', 'chevron_right', 'chevron_right_circle', 'arrow_up_right'];

    public const READ_ALL_ICON_POSITIONS = ['before', 'after'];

    /** รูปแบบของปุ่ม: ปุ่ม / ลิงก์ข้อความ / ปุ่มมนใหญ่ (คล้ายวงรี) */
    public const READ_ALL_STYLES = ['button', 'link', 'pill'];

    /** ลิงก์ปลายทางที่รับ: URL เต็ม, path ภายในเว็บ (ขึ้นต้น /), anchor (#), mailto:, tel: */
    private const READ_ALL_URL_REGEX = '/^(https?:\/\/|\/|#|mailto:|tel:)\S*$/i';

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'slidesetArticle';
    }

    protected function model(): string
    {
        return PageItemWidgetSlidesetArticle::class;
    }

    protected function detailModel(): ?string
    {
        return PageItemWidgetSlidesetArticleDetail::class;
    }

    protected function detailFields(): array
    {
        // ข้อความแทน "อ่านทั้งหมด" แยกภาษา (ว่าง = ใช้ข้อความมาตรฐานของหน้าบ้าน)
        return ['read_all_text' => ['label' => 'ข้อความของปุ่มอ่านทั้งหมด', 'max' => 100]];
    }

    protected function introShownByDefault(): string
    {
        return 'Y';
    }

    protected function extraFields(): array
    {
        return $this->metaFields('date', 'วันที่เผยแพร่', showDefault: 'Y')
            + $this->metaFields('views', 'จำนวนเข้าชม', showDefault: 'N')
            + [
                'show_read_all' => self::flag('การแสดงปุ่มอ่านทั้งหมด', 'N'),
                'read_all_position' => self::choice('ตำแหน่งของปุ่มอ่านทั้งหมด', 'bottom_center', self::READ_ALL_POSITIONS),
                'read_all_icon' => self::choice('ไอคอนของปุ่มอ่านทั้งหมด', 'arrow_right', self::READ_ALL_ICONS),
                'read_all_icon_position' => self::choice('ตำแหน่งไอคอนของปุ่มอ่านทั้งหมด', 'after', self::READ_ALL_ICON_POSITIONS),
                'read_all_style' => self::choice('รูปแบบของปุ่มอ่านทั้งหมด', 'button', self::READ_ALL_STYLES),
                'read_all_url' => [
                    'label' => 'ลิงก์ปลายทางของปุ่มอ่านทั้งหมด', 'default' => '', 'type' => 'nullstring',
                    // จำเป็นต้องกรอกเมื่อแสดงปุ่ม (ภายหลังอาจเลือกจากเมนูหน้าบ้านแทนการกรอก URL)
                    'rules' => ['nullable', 'string', 'max:500', 'regex:'.self::READ_ALL_URL_REGEX, 'required_if:show_read_all,Y'],
                    'messages' => [
                        'required_if' => 'กรุณากรอกลิงก์ปลายทางของปุ่มอ่านทั้งหมด (ต้องกรอกเมื่อเปิดแสดงปุ่ม)',
                        'regex' => 'ลิงก์ปลายทางของปุ่มอ่านทั้งหมดต้องขึ้นต้นด้วย http://, https:// หรือ / (หรือ #, mailto:, tel:)',
                        'max' => 'ลิงก์ปลายทางของปุ่มอ่านทั้งหมดต้องไม่เกิน 500 ตัวอักษร',
                    ],
                ],
                'read_all_link_target' => self::choice('เป้าหมายการเปิดลิงก์ของปุ่มอ่านทั้งหมด', '_self', self::LINK_TARGETS),
            ];
    }

    protected function normalize(array $values): array
    {
        $values = parent::normalize($values);
        $url = $values['read_all_url'] ?? null;
        $values['read_all_url'] = is_string($url) && trim($url) !== '' ? trim($url) : null;

        return $values;
    }

    protected function previewQuery(int $categoryId): Builder
    {
        // ไม่บังคับมีรูปหน้าปก (การ์ดแสดงกรอบว่างแทน) — วันที่ที่แสดงใช้วันที่เผยแพร่ (ถ้าไม่มีใช้วันที่สร้าง เหมือนตอนเรียงลำดับ)
        return $this->articleQuery($categoryId, requireImage: false)
            ->selectRaw('article_item_info.id, img.hash_name as image, d.title, d.intro_text, COALESCE(article_item_info.publish_date, article_item_info.created_at) as shown_date, article_item_info.view_amount');
    }

    protected function previewRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'image' => $row->image, // null = บทความไม่มีรูปหน้าปก
            'title' => $row->title ?? '',
            'intro_text' => $row->intro_text ?? '',
            'date' => $row->shown_date !== null ? substr((string) $row->shown_date, 0, 10) : null, // Y-m-d
            'views' => (int) $row->view_amount,
        ];
    }
}
