<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { CSSProperties } from 'vue';
import { CalendarDays, ChevronLeft, ChevronRight, Eye, Image as ImageIcon, Laptop, Link2, Monitor, Smartphone, Tablet } from 'lucide-vue-next';
import ReadAllButton from './ReadAllButton.vue';
import { PREVIEW_LIMIT, useWidgetPreview } from '@/composables/useWidgetPreview';
import { SLIDESET_DEVICES, currentDevice, slidesetConfig } from '@/utils/pageWidget';
import type { SlidesetDevice, SlidesetSetting } from '@/utils/pageWidget';
import { READ_ALL_DEFAULT_TEXT } from '@/utils/readAllButton';
import type { LanguageOption } from '@/types';

/**
 * ตัวอย่างการแสดงผลของ widget Slideset (จาก article / จาก banner) ในหน้าโครงสร้าง — การ์ดจริงของหมวดหมู่ที่เลือก (ตามลำดับ/จำนวนสูงสุดที่ตั้งไว้)
 * แสดงตามค่าตั้งค่าทั้งหมด: รูป (อัตราส่วน/cover-contain + สีพื้นหลังเมื่อ contain), หัวเรื่อง/ข้อความเกริ่นนำ (ฟอนต์ ขนาด สี ตัวหนา ตำแหน่ง
 * ตัดที่จำนวนบรรทัดด้วย ...), วันที่เผยแพร่/จำนวนเข้าชม/ปุ่มอ่านทั้งหมด (เฉพาะ article), ลูกศร/จุด (ใต้การ์ด)/เลื่อนอัตโนมัติ — เลื่อนทีละ "หน้า" (ครั้งละเท่าจำนวนการ์ดต่อแถว)
 * ผู้ใช้เลือกดูตามขนาดหน้าจอ (PC/Notebook/Tablet/Mobile) เพื่อเห็นจำนวนการ์ดต่อแถวของแต่ละขนาด (ค่าเริ่มต้น = ขนาดของหน้าต่างที่เปิดอยู่)
 * ลิงก์เป็นแค่เครื่องหมาย (ไอคอนลิงก์) — กดไม่ได้และไม่มี URL
 */
const props = defineProps<{
    widgetType: string;
    setting: SlidesetSetting;
    /** ภาษาที่เปิดใช้ — ไว้หาข้อความของปุ่มอ่านทั้งหมดในภาษาหลัก */
    languages: LanguageOption[];
}>();

const config = slidesetConfig(props.widgetType)!;

// คีย์หมวดหมู่ใน setting ต่างกันตามแหล่งข้อมูล (article_category_info_id / banner_category_info_id)
const categoryId = () => (props.setting as unknown as Record<string, number | null>)[config.categoryKey];

const { items, loading, failed } = useWidgetPreview(props.widgetType, () =>
    categoryId() ? { [config.categoryKey]: categoryId(), sort_by: props.setting.sort_by, max_items: props.setting.max_items } : null,
);

// ---- ขนาดหน้าจอที่ดูตัวอย่าง ----
const device = ref<SlidesetDevice>(currentDevice());
const DEVICE_ICONS = { pc: Monitor, notebook: Laptop, tablet: Tablet, mobile: Smartphone };
const perView = computed(() => Math.max(1, props.setting[`per_row_${device.value}` as const]));

// ---- เลื่อนทีละหน้า ----
const page = ref(0);
const count = computed(() => items.value.length);
const pageCount = computed(() => Math.max(1, Math.ceil(count.value / perView.value)));
const canSlide = computed(() => count.value > perView.value);

// หน้าสุดท้ายถอยให้เต็มแถว (ไม่เหลือช่องว่างท้ายราง) — ตำแหน่งการ์ดแรกที่เห็นอยู่
const offset = computed(() => Math.min(page.value * perView.value, Math.max(count.value - perView.value, 0)));

watch([items, perView], () => {
    page.value = 0;
});

function go(target: number) {
    page.value = (target + pageCount.value) % pageCount.value;
}

let timer: ReturnType<typeof setInterval> | null = null;

function stopTimer() {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}

watch(
    () => [props.setting.autoplay, props.setting.autoplay_interval, canSlide.value] as const,
    ([autoplay, interval, slidable]) => {
        stopTimer();

        if (autoplay === 'Y' && slidable && interval > 0) {
            timer = setInterval(() => go(page.value + 1), interval * 1000);
        }
    },
    { immediate: true },
);

onBeforeUnmount(stopTimer);

const trackStyle = computed<CSSProperties>(() => ({
    transform: `translateX(-${offset.value * (100 / perView.value)}%)`,
    transition: `transform ${props.setting.transition_speed}ms ease`,
}));

const slotStyle = computed<CSSProperties>(() => ({ flex: `0 0 ${100 / perView.value}%`, maxWidth: `${100 / perView.value}%` }));

// ---- รูปแบบข้อความบนการ์ด ----
// จำนวนบรรทัดต้องเป็นชื่อ class ตัวเต็ม (Tailwind สแกนจากซอร์ส)
const LINE_CLAMP: Record<number, string> = { 1: 'line-clamp-1', 2: 'line-clamp-2', 3: 'line-clamp-3' };

function textCss(part: 'title' | 'intro_text' | 'date' | 'views'): CSSProperties {
    const s = props.setting as unknown as Record<string, string | number>;
    const css: CSSProperties = {
        fontSize: `${s[`${part}_font_size`]}px`,
        fontFamily: `'${s[`${part}_font_family`]}', sans-serif`,
        color: String(s[`${part}_color`]),
        fontWeight: s[`${part}_bold`] === 'Y' ? 700 : 400,
    };

    if (`${part}_align` in s) {
        css.textAlign = s[`${part}_align`] as CSSProperties['textAlign'];
    }

    return css;
}

const JUSTIFY = { left: 'justify-start', center: 'justify-center', right: 'justify-end' } as const;
const metaJustify = computed(() => JUSTIFY[props.setting.title_align]);

const frameStyle = computed<CSSProperties>(() => ({
    aspectRatio: props.setting.aspect_ratio.replace(':', ' / '),
    // สีพื้นหลังกรอบรูป: เลือกได้เมื่อแสดงแบบ contain (รวมโปร่งใส) — แบบ cover ใช้เทาอ่อนเป็นพื้นของกรอบว่าง (บทความไม่มีรูป)
    backgroundColor: props.setting.image_fit === 'contain' ? props.setting.image_background : '#F3F4F6',
}));

// ---- ปุ่ม "อ่านทั้งหมด" (เฉพาะ article) ----
const showReadAll = computed(() => props.setting.show_read_all === 'Y');
const readAllOnTop = computed(() => props.setting.read_all_position?.startsWith('top') ?? false);
const READ_ALL_ALIGN = { left: 'justify-start', center: 'justify-center', right: 'justify-end' } as const;
const readAllAlign = computed(() => READ_ALL_ALIGN[(props.setting.read_all_position?.split('_')[1] ?? 'center') as keyof typeof READ_ALL_ALIGN]);
// ข้อความของภาษาหลักที่กรอก (ถ้าว่างใช้ข้อความมาตรฐาน)
const readAllText = computed(() => {
    const main = props.languages.find((l) => l.is_default)?.code;

    return (main ? props.setting.read_all_text?.[main]?.trim() : '') || READ_ALL_DEFAULT_TEXT;
});

function formatDate(value: string | null): string {
    return value ? new Date(`${value}T00:00:00`).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
}

// ตัวอย่างแสดงไม่เกิน PREVIEW_LIMIT ใบ — บอกผู้ใช้เมื่อจำนวนจริงที่จะแสดง (ตามจำนวนสูงสุดที่ตั้งไว้) อาจมากกว่านี้
const previewTruncated = computed(() => count.value >= PREVIEW_LIMIT && (props.setting.max_items === 0 || props.setting.max_items > PREVIEW_LIMIT));

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
            <span class="text-[11px] text-gray-400">{{ perView }} รายการต่อแถว</span>
        </div>

        <div v-if="!categoryId()" class="rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-6 text-center text-xs text-gray-500">
            ยังไม่ได้เลือก{{ config.categoryLabel }}
        </div>
        <div v-else-if="loading" class="rounded-md bg-gray-100 px-3 py-6 text-center text-xs text-gray-500">กำลังโหลดตัวอย่าง…</div>
        <div v-else-if="failed" class="rounded-md border border-dashed border-red-200 bg-red-50 px-3 py-6 text-center text-xs text-red-600">
            โหลดตัวอย่างไม่สำเร็จ (ตรวจสอบหมวดหมู่ที่เลือก)
        </div>
        <div v-else-if="count === 0" class="rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-6 text-center text-xs text-gray-500">
            {{ config.emptyText }}
        </div>

        <div v-else>
            <div v-if="showReadAll && readAllOnTop" class="mb-2 flex" :class="readAllAlign">
                <ReadAllButton :text="readAllText" :icon="setting.read_all_icon ?? 'none'" :icon-position="setting.read_all_icon_position ?? 'after'" :style-type="setting.read_all_style ?? 'button'" />
            </div>

            <div class="relative">
                <div class="select-none overflow-hidden">
                    <div class="flex" :style="trackStyle">
                        <div v-for="item in items" :key="item.id" class="px-1.5" :style="slotStyle">
                            <article class="flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white">
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

                                <div class="flex flex-1 flex-col gap-1 p-3">
                                    <div v-if="setting.show_title === 'Y' && item.title" :class="LINE_CLAMP[setting.title_lines]" class="leading-snug" :style="textCss('title')">
                                        {{ item.title }}<Link2 v-if="setting.title_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                                    </div>
                                    <!-- whitespace-pre-line: ขึ้นบรรทัดใหม่ตามที่พิมพ์ในข้อความเกริ่นนำ -->
                                    <div
                                        v-if="setting.show_intro_text === 'Y' && item.intro_text"
                                        :class="LINE_CLAMP[setting.intro_text_lines]"
                                        class="whitespace-pre-line leading-snug"
                                        :style="textCss('intro_text')"
                                    >
                                        {{ item.intro_text }}<Link2 v-if="setting.intro_text_clickable === 'Y' && item.has_link !== false" :class="linkIcon" />
                                    </div>

                                    <div v-if="(setting.show_date === 'Y' && item.date) || setting.show_views === 'Y'" class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-0.5 pt-1" :class="metaJustify">
                                        <span v-if="setting.show_date === 'Y' && item.date" class="inline-flex items-center gap-1" :style="textCss('date')">
                                            <CalendarDays class="size-3.5 shrink-0" /> {{ formatDate(item.date ?? null) }}
                                        </span>
                                        <span v-if="setting.show_views === 'Y'" class="inline-flex items-center gap-1" :style="textCss('views')">
                                            <Eye class="size-3.5 shrink-0" /> {{ (item.views ?? 0).toLocaleString('th-TH') }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <template v-if="canSlide && setting.show_arrows === 'Y'">
                    <button
                        type="button"
                        class="absolute left-0 top-1/3 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white shadow hover:bg-black/65"
                        aria-label="ก่อนหน้า"
                        @click="go(page - 1)"
                    >
                        <ChevronLeft class="size-5" />
                    </button>
                    <button
                        type="button"
                        class="absolute right-0 top-1/3 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white shadow hover:bg-black/65"
                        aria-label="ถัดไป"
                        @click="go(page + 1)"
                    >
                        <ChevronRight class="size-5" />
                    </button>
                </template>
            </div>

            <!-- จุดอยู่ใต้การ์ด (พื้นที่ด้านล่าง ไม่ซ้อนบนการ์ด) -->
            <div v-if="canSlide && setting.show_dots === 'Y'" class="mt-2 flex justify-center gap-1.5">
                <button
                    v-for="n in pageCount"
                    :key="n"
                    type="button"
                    class="size-2 rounded-full transition-colors"
                    :class="n - 1 === page ? 'bg-brand-500' : 'bg-gray-300 hover:bg-gray-400'"
                    :aria-label="`หน้าที่ ${n}`"
                    @click="go(n - 1)"
                />
            </div>

            <div v-if="showReadAll && !readAllOnTop" class="mt-3 flex" :class="readAllAlign">
                <ReadAllButton :text="readAllText" :icon="setting.read_all_icon ?? 'none'" :icon-position="setting.read_all_icon_position ?? 'after'" :style-type="setting.read_all_style ?? 'button'" />
            </div>

            <p v-if="previewTruncated" class="mt-1 text-[11px] text-gray-400">ตัวอย่างแสดง {{ PREVIEW_LIMIT }} รายการแรก</p>
        </div>
    </div>
</template>
