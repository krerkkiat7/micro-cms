/**
 * ตัวเลือกตั้งค่าโมดูลบทความ — ค่าต้องตรงกับ App\Support\ArticleSetting (SORTS / SHARE_POSITIONS / อัตราส่วนรูป)
 * ใช้ทั้งหน้าตั้งค่าหลังบ้านและหน้ารายการบทความหน้าบ้าน (ป้ายภาษาของหน้าบ้านอยู่ lang/{th,en}/front.php แยกต่างหาก)
 */
export const ARTICLE_SORTS = ['newest', 'oldest', 'title_asc', 'title_desc', 'views_desc', 'views_asc'] as const;
export type ArticleSort = (typeof ARTICLE_SORTS)[number];

export const ARTICLE_SORT_OPTIONS: { value: ArticleSort; label: string }[] = [
    { value: 'newest', label: 'ใหม่สุด' },
    { value: 'oldest', label: 'เก่าสุด' },
    { value: 'title_asc', label: 'ตัวอักษร ก ถึง ฮ' },
    { value: 'title_desc', label: 'ตัวอักษร ฮ ถึง ก' },
    { value: 'views_desc', label: 'จำนวนเข้าชมมากสุด' },
    { value: 'views_asc', label: 'จำนวนเข้าชมน้อยสุด' },
];

export const ARTICLE_SHARE_POSITION_OPTIONS = [
    { value: 'top', label: 'บน' },
    { value: 'bottom', label: 'ล่าง' },
    { value: 'both', label: 'ทั้งบนและล่าง' },
    { value: 'none', label: 'ไม่แสดง' },
];

export const ARTICLE_ASPECT_OPTIONS = [
    { value: '16:9', label: '16:9 (มาตรฐาน)' },
    { value: '21:9', label: '21:9 (แบนเนอร์กว้าง)' },
    { value: '4:3', label: '4:3' },
    { value: '1:1', label: '1:1 (จัตุรัส)' },
];

/** มุมมองแถวเลือก "ตามขนาดของรูป" ได้เพิ่ม (ไม่ครอป → ไม่มีประเภทการแสดงรูป/สีพื้นหลังให้ตั้ง) */
export const ARTICLE_ROW_ASPECT_OPTIONS = [{ value: 'natural', label: 'ตามขนาดของรูป' }, ...ARTICLE_ASPECT_OPTIONS];

/** ค่าตั้งค่าการแสดงรูป/บรรทัดของมุมมองการ์ดหรือแถว (ArticleSetting::listSetting() → card / row) */
export interface ArticleListViewSetting {
    aspect_ratio: string;
    image_fit: 'cover' | 'contain';
    image_background: string;
    title_lines: number;
    intro_lines: number;
}

export interface ArticleListSetting {
    show_category_intro: boolean;
    show_category_detail: boolean;
    per_page: number;
    display_mode: 'card' | 'row';
    default_sort: ArticleSort;
    show_date: boolean;
    show_views: boolean;
    card: ArticleListViewSetting;
    row: ArticleListViewSetting;
}

export interface ArticleDetailSetting {
    show_cover: boolean;
    show_print: boolean;
    share_position: 'top' | 'bottom' | 'both' | 'none';
}
