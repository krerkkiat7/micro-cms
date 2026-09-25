<script setup lang="ts">
import { computed, ref } from 'vue';
import type { CSSProperties } from 'vue';
import { CalendarDays, Eye, Image as ImageIcon, Laptop, Link2, Monitor, Smartphone, Tablet } from 'lucide-vue-next';
import ReadAllButton from './ReadAllButton.vue';
import { PREVIEW_LIMIT, useWidgetPreview } from '@/composables/useWidgetPreview';
import { SLIDESET_DEVICES, currentDevice, gridConfig, gridContentAlignClasses } from '@/utils/pageWidget';
import type { GridSetting, SlidesetDevice } from '@/utils/pageWidget';
import { READ_ALL_DEFAULT_TEXT } from '@/utils/readAllButton';
import type { LanguageOption } from '@/types';

/**
 * ตัวอย่างการแสดงผลของ widget Grid จาก article ในหน้าโครงสร้าง — รายการจริงของหมวดหมู่ที่เลือก (ตามลำดับ/จำนวนสูงสุดที่ตั้งไว้) จัดเรียงเป็น
 * CSS grid ตามจำนวนคอลัมน์ต่อแถวของขนาดหน้าจอที่เลือกดู (ไม่เลื่อนเหมือน Slideset) หน้าตาของแต่ละรายการเปลี่ยนตาม `display_type`:
 * `card` = การ์ดแนวตั้ง (รูปบน ข้อมูลล่าง, ซ่อนส่วนข้อมูลทั้งหมดถ้าไม่มีอะไรแสดง), `row_image` = แถวแนวนอน รูปซ้าย (กว้างเป็น %) ข้อมูลขวา,
 * `row_date` = แถวแนวนอน กล่องวันที่ซ้าย (เลขวันที่ + เดือนย่อปี) ข้อมูลขวา — ลิงก์เป็นแค่เครื่องหมาย (ไอคอนลิงก์) กดไม่ได้และไม่มี URL
 */
const props = defineProps<{
    widgetType: string;
    setting: GridSetting;
    /** ภาษาที่เปิดใช้ — ไว้หาข้อความของปุ่มอ่านทั้งหมดในภาษาหลัก */
    languages: LanguageOption[];
}>();

const config = gridConfig(props.widgetType)!;

// คีย์หมวดหมู่ใน setting ต่างกันตามแหล่งข้อมูล (article_category_info_id / banner_category_info_id)
const categoryId = () => (props.setting as unknown as Record<string, number | null>)[config.categoryKey];

const { items, loading, failed } = useWidgetPreview(props.widgetType, () =>
    categoryId() ? { [config.categoryKey]: categoryId(), sort_by: props.setting.sort_by, max_items: props.setting.max_items } : null,
);

// ---- ขนาดหน้าจอที่ดูตัวอย่าง (กำหนดจำนวนคอลัมน์) ----
const device = ref<SlidesetDevice>(currentDevice());
const DEVICE_ICONS = { pc: Monitor, notebook: Laptop, tablet: Tablet, mobile: Smartphone };
const columns = computed(() => Math.max(1, props.setting[`per_row_${device.value}` as const]));
const gridStyle = computed<CSSProperties>(() => ({ gridTemplateColumns: `repeat(${columns.value}, minmax(0, 1fr))` }));

// ---- รูปแบบข้อความ ----
const LINE_CLAMP: Record<number, string> = { 1: 'line-clamp-1', 2: 'line-clamp-2', 3: 'line-clamp-3' };

function textCss(part: string): CSSProperties {
    const s = props.setting as unknown as Record<string, string | number>;

    return {
        fontSize: `${s[`${part}_font_size`]}px`,
        fontFamily: `'${s[`${part}_font_family`]}', sans-serif`,
        color: String(s[`${part}_color`]),
        fontWeight: s[`${part}_bold`] === 'Y' ? 700 : 400,
        textAlign: part === 'title' || part === 'intro_text' ? (s[`${part}_align`] as CSSProperties['textAlign']) : undefined,
    };
}

const frameStyle = computed<CSSProperties>(() => ({
    aspectRatio: props.setting.aspect_ratio.replace(':', ' / '),
    backgroundColor: props.setting.image_fit === 'contain' ? props.setting.image_background : '#F3F4F6',
}));

// ---- กล่องของการ์ด/แถว: เส้นขอบ + สี / มุมมน / สีพื้นหลังของแต่ละรายการ ----
const itemStyle = computed<CSSProperties>(() => ({
    backgroundColor: props.setting.item_background,
    borderWidth: props.setting.show_border === 'Y' ? '1px' : '0',
    borderStyle: 'solid',
    borderColor: props.setting.border_color,
}));

const dateBoxStyle = computed<CSSProperties>(() => ({ backgroundColor: props.setting.date_box_background }));

// ---- ปุ่ม "อ่านทั้งหมด" ----
const showReadAll = computed(() => props.setting.show_read_all === 'Y');
const readAllOnTop = computed(() => props.setting.read_all_position?.startsWith('top') ?? false);
// ตำแหน่งแนวตั้งของส่วนข้อมูลในรูปแบบแถว (บน/กึ่งกลาง/ล่าง — ตัวเดียวกับหน้าบ้าน)
const contentAlign = computed(() => gridContentAlignClasses(props.setting.content_align));

const READ_ALL_ALIGN = { left: 'justify-start', center: 'justify-center', right: 'justify-end' } as const;
const readAllAlign = computed(() => READ_ALL_ALIGN[(props.setting.read_all_position?.split('_')[1] ?? 'center') as keyof typeof READ_ALL_ALIGN]);
const readAllText = computed(() => {
    const main = props.languages.find((l) => l.is_default)?.code;

    return (main ? props.setting.read_all_text?.[main]?.trim() : '') || READ_ALL_DEFAULT_TEXT;
});

function formatDate(value: string | null): string {
    return value ? new Date(`${value}T00:00:00`).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
}

// กล่องวันที่ของรูปแบบ "แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ" — เลขวันที่บรรทัดใหญ่ + เดือนย่อ/ปี พ.ศ. 2 หลักท้ายบรรทัดเล็ก
function dateBox(value: string | null): { day: string; monthYear: string } {
    if (!value) {
        return { day: '-', monthYear: '' };
    }

    const date = new Date(`${value}T00:00:00`);
    const month = date.toLocaleDateString('th-TH', { month: 'short' });
    const buddhistYear = String(date.getFullYear() + 543).slice(-2);

    return { day: String(date.getDate()), monthYear: `${month} ${buddhistYear}` };
}

// ตัวอย่างแสดงไม่เกิน PREVIEW_LIMIT รายการ — บอกผู้ใช้เมื่อจำนวนจริงที่จะแสดง (ตามจำนวนสูงสุดที่ตั้งไว้) อาจมากกว่านี้
const previewTruncated = computed(() => items.value.length >= PREVIEW_LIMIT && (props.setting.max_items === 0 || props.setting.max_items > PREVIEW_LIMIT));

// รูปแบบการ์ด: ถ้าทุกส่วนของข้อมูลถูกซ่อนหรือไม่มีข้อมูลให้ไม่แสดงส่วนข้อมูลเลย (ไม่เหลือพื้นที่ว่าง) — รูปแบบแถวบังคับแสดงหัวเรื่องเสมออยู่แล้วจึงไม่มีทางว่าง
function hasBody(item: { title: string; intro_text: string; date?: string | null }): boolean {
    return (
        (props.setting.show_title === 'Y' && !!item.title) ||
        (props.setting.show_intro_text === 'Y' && !!item.intro_text) ||
        (props.setting.show_date === 'Y' && !!item.date) ||
        props.setting.show_views === 'Y'
    );
}

const linkIcon = 'ml-1 inline size-3 shrink-0 align-baseline opacity-60';
</script>

<template>
    <div class="space-y-2">
        <!-- เลือกดูตามขนาดหน้าจอ -->
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="text-[11px] text-gray-500">ดูตัวอย่างตามขนาดหน้าจอ</span>
            <div class="inline-flex overflow-hidden rounded-md border border-gray-200 bg-white">
                <button
                    v-for="d in SLIDESET_DEVICES"
                    :key="d.key"
                    type="button"
                    :title="`${d.label} (${d.range})`"
                    class="flex items-center gap-1 px-2 py-1 text-[11px] transition-colors"
                    :class="device === d.key ? 'bg-brand-50 font-medium text-brand-700' : 'text-gray-500 hover:bg-gray-50'"
                    @click="device = d.key"
                >
                    <component :is="DEVICE_ICONS[d.key]" class="size-3.5" /> {{ d.label }}
                </button>
            </div>
            <span class="text-[11px] text-gray-400">{{ columns }} คอลัมน์ต่อแถว</span>
        </div>

        <div v-if="!categoryId()" class="rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-6 text-center text-xs text-gray-500">
            ยังไม่ได้เลือก{{ config.categoryLabel }}
        </div>
        <div v-else-if="loading" class="rounded-md bg-gray-100 px-3 py-6 text-center text-xs text-gray-500">กำลังโหลดตัวอย่าง…</div>
        <div v-else-if="failed" class="rounded-md border border-dashed border-red-200 bg-red-50 px-3 py-6 text-center text-xs text-red-600">
            โหลดตัวอย่างไม่สำเร็จ (ตรวจสอบหมวดหมู่ที่เลือก)
        </div>
        <div v-else-if="items.length === 0" class="rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-6 text-center text-xs text-gray-500">
            {{ config.emptyText }}
        </div>

        <div v-else>
            <div v-if="showReadAll && readAllOnTop" class="mb-2 flex" :class="readAllAlign">
                <ReadAllButton
                    :text="readAllText"
                    :icon="setting.read_all_icon ?? 'none'"
                    :icon-position="setting.read_all_icon_position ?? 'after'"
                    :style-type="setting.read_all_style ?? 'button'"
                    :font-size="setting.read_all_font_size"
                    :font-family="setting.read_all_font_family"
                    :color="setting.read_all_color"
                    :background="setting.read_all_background"
                />
            </div>

            <div class="grid gap-4" :style="gridStyle">
                <article
                    v-for="item in items"
                    :key="item.id"
                    class="overflow-hidden"
                    :class="[setting.rounded_corners === 'Y' ? 'rounded-lg' : '', setting.display_type === 'card' ? 'flex h-full flex-col' : 'flex items-stretch gap-3 p-2']"
                    :style="itemStyle"
                >
                    <!-- การ์ด: รูปด้านบน ข้อมูลด้านล่าง -->
                    <template v-if="setting.display_type === 'card'">
                        <div v-if="setting.show_image === 'Y'" class="relative flex items-center justify-center" :style="frameStyle">
                            <img
                                v-if="item.image"
                                :src="route('admin.system.file.get.thumbnail.size', { size: 480, hashname: item.image })"
                                :alt="item.title"
                                class="size-full"
                                :style="{ objectFit: setting.image_fit }"
                                draggable="false"
                            />
                            <ImageIcon v-else class="size-8 text-gray-300" />
                            <span
                                v-if="setting.image_clickable === 'Y' && item.has_link !== false"
                                class="absolute right-1.5 top-1.5 inline-flex items-center gap-1 rounded-full bg-black/55 px-1.5 py-0.5 text-[10px] text-white"
                            >
                                <Link2 class="size-3" /> ลิงก์
                            </span>
                        </div>

                        <div v-if="hasBody(item)" class="flex flex-1 flex-col gap-1 p-3">
                            <div v-if="setting.show_title === 'Y' && item.title" :class="LINE_CLAMP[setting.title_lines]" class="leading-snug" :style="textCss('title')">
                                {{ item.title }}<Link2 v-if="setting.title_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                            </div>
                            <div
                                v-if="setting.show_intro_text === 'Y' && item.intro_text"
                                :class="LINE_CLAMP[setting.intro_text_lines]"
                                class="whitespace-pre-line leading-snug"
                                :style="textCss('intro_text')"
                            >
                                {{ item.intro_text }}<Link2 v-if="setting.intro_text_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                            </div>
                            <div v-if="(setting.show_date === 'Y' && item.date) || setting.show_views === 'Y'" class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-0.5 pt-1">
                                <span v-if="setting.show_date === 'Y' && item.date" class="inline-flex items-center gap-1" :style="textCss('date')">
                                    <CalendarDays class="size-3.5 shrink-0" /> {{ formatDate(item.date ?? null) }}
                                </span>
                                <span v-if="setting.show_views === 'Y'" class="inline-flex items-center gap-1" :style="textCss('views')">
                                    <Eye class="size-3.5 shrink-0" /> {{ (item.views ?? 0).toLocaleString('th-TH') }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- แถวที่มีรูปภาพ: รูปซ้าย (กว้างเป็น %) ข้อมูลขวา -->
                    <template v-else-if="setting.display_type === 'row_image'">
                        <div
                            v-if="setting.show_image === 'Y'"
                            class="relative flex shrink-0 items-center justify-center overflow-hidden rounded-md"
                            :style="{ ...frameStyle, width: `${setting.image_width_percent}%` }"
                        >
                            <img
                                v-if="item.image"
                                :src="route('admin.system.file.get.thumbnail.size', { size: 480, hashname: item.image })"
                                :alt="item.title"
                                class="size-full"
                                :style="{ objectFit: setting.image_fit }"
                                draggable="false"
                            />
                            <ImageIcon v-else class="size-6 text-gray-300" />
                            <span
                                v-if="setting.image_clickable === 'Y' && item.has_link !== false"
                                class="absolute right-1 top-1 inline-flex items-center gap-0.5 rounded-full bg-black/55 px-1 py-0.5 text-[9px] text-white"
                            >
                                <Link2 class="size-2.5" />
                            </span>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-1 py-1" :class="contentAlign.column">
                            <div v-if="item.title" :class="LINE_CLAMP[setting.title_lines]" class="leading-snug" :style="textCss('title')">
                                {{ item.title }}<Link2 v-if="setting.title_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                            </div>
                            <div
                                v-if="setting.show_intro_text === 'Y' && item.intro_text"
                                :class="LINE_CLAMP[setting.intro_text_lines]"
                                class="whitespace-pre-line leading-snug"
                                :style="textCss('intro_text')"
                            >
                                {{ item.intro_text }}<Link2 v-if="setting.intro_text_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                            </div>
                            <div
                                v-if="(setting.show_date === 'Y' && item.date) || setting.show_views === 'Y'"
                                class="flex flex-wrap items-center gap-x-3 gap-y-0.5"
                                :class="contentAlign.meta"
                            >
                                <span v-if="setting.show_date === 'Y' && item.date" class="inline-flex items-center gap-1" :style="textCss('date')">
                                    <CalendarDays class="size-3.5 shrink-0" /> {{ formatDate(item.date ?? null) }}
                                </span>
                                <span v-if="setting.show_views === 'Y'" class="inline-flex items-center gap-1" :style="textCss('views')">
                                    <Eye class="size-3.5 shrink-0" /> {{ (item.views ?? 0).toLocaleString('th-TH') }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ: กล่องวันที่ซ้าย ข้อมูลขวา -->
                    <template v-else>
                        <div class="flex w-16 shrink-0 flex-col items-center justify-center rounded-md py-2 text-center" :style="dateBoxStyle">
                            <span class="leading-none" :style="textCss('date_day')">{{ dateBox(item.date ?? null).day }}</span>
                            <span class="mt-1 leading-none" :style="textCss('date_month')">{{ dateBox(item.date ?? null).monthYear }}</span>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-1 py-1" :class="contentAlign.column">
                            <div v-if="item.title" :class="LINE_CLAMP[setting.title_lines]" class="leading-snug" :style="textCss('title')">
                                {{ item.title }}<Link2 v-if="setting.title_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                            </div>
                            <div
                                v-if="setting.show_intro_text === 'Y' && item.intro_text"
                                :class="LINE_CLAMP[setting.intro_text_lines]"
                                class="whitespace-pre-line leading-snug"
                                :style="textCss('intro_text')"
                            >
                                {{ item.intro_text }}<Link2 v-if="setting.intro_text_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                            </div>
                            <div v-if="setting.show_views === 'Y'" class="flex items-center gap-1" :class="contentAlign.meta" :style="textCss('views')">
                                <Eye class="size-3.5 shrink-0" /> {{ (item.views ?? 0).toLocaleString('th-TH') }}
                            </div>
                        </div>
                    </template>
                </article>
            </div>

            <div v-if="showReadAll && !readAllOnTop" class="mt-3 flex" :class="readAllAlign">
                <ReadAllButton
                    :text="readAllText"
                    :icon="setting.read_all_icon ?? 'none'"
                    :icon-position="setting.read_all_icon_position ?? 'after'"
                    :style-type="setting.read_all_style ?? 'button'"
                    :font-size="setting.read_all_font_size"
                    :font-family="setting.read_all_font_family"
                    :color="setting.read_all_color"
                    :background="setting.read_all_background"
                />
            </div>

            <p v-if="previewTruncated" class="mt-1 text-[11px] text-gray-400">ตัวอย่างแสดง {{ PREVIEW_LIMIT }} รายการแรก</p>
        </div>
    </div>
</template>
