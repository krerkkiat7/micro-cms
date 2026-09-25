<script setup lang="ts">
import { computed } from 'vue';
import { CalendarDays, Eye, Image as ImageIcon } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import { useFront } from '@/composables/useFront';
import { formatNumber, formatShortDate } from '@/utils/front';
import { JUSTIFY_CLASS, LINE_CLAMP, imageFrameCss, itemBoxCss, itemTarget, partCss } from '@/utils/frontPage';
import type { FrontWidgetItem } from '@/utils/frontPage';

/**
 * การ์ด 1 ใบของ Slideset / Grid (รูปแบบการ์ด) — รูป (อัตราส่วน/cover-contain) + หัวเรื่อง/ข้อความเกริ่นนำ (ตัดตามจำนวนบรรทัด)
 * + วันที่เผยแพร่/จำนวนเข้าชม (article) ตามตั้งค่า เหมือนตัวอย่างในหลังบ้าน
 *
 * ลิงก์: ส่วนที่ตั้ง "กดลิงก์ได้" เป็นลิงก์จริง — ลิงก์ซ้ำไปที่เดียวกัน (รูป/ข้อความเกริ่นนำเมื่อหัวเรื่องเป็นลิงก์อยู่แล้ว) ไม่อยู่ในลำดับ Tab
 * และซ่อนจาก screen reader เพื่อไม่ให้อ่านลิงก์ซ้ำ (WCAG 2.4.4)
 */
const props = defineProps<{
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
    item: FrontWidgetItem;
    hasMeta: boolean;
    /** ระดับหัวเรื่องของชื่อรายการ (ต่ำกว่าหัวเรื่องของ widget) */
    headingTag: string;
}>();

const { front, t } = useFront();

const s = computed(() => props.setting);
const target = computed(() => itemTarget(props.setting, props.item));
const titleLinked = computed(() => s.value.show_title === 'Y' && s.value.title_clickable === 'Y' && !!props.item.url && props.item.title !== '');
const imageLinked = computed(() => s.value.image_clickable === 'Y' && !!props.item.url);
const introLinked = computed(() => s.value.intro_text_clickable === 'Y' && !!props.item.url);

const showDate = computed(() => props.hasMeta && s.value.show_date === 'Y' && !!props.item.date);
const showViews = computed(() => props.hasMeta && s.value.show_views === 'Y');
const hasBody = computed(
    () => (s.value.show_title === 'Y' && !!props.item.title) || (s.value.show_intro_text === 'Y' && !!props.item.intro_text) || showDate.value || showViews.value,
);
</script>

<template>
    <article class="flex h-full flex-col overflow-hidden" :class="s.rounded_corners === 'Y' ? 'rounded-lg' : ''" :style="itemBoxCss(s)">
        <div v-if="s.show_image === 'Y'" class="relative flex items-center justify-center overflow-hidden" :style="imageFrameCss(s)">
            <component
                :is="imageLinked ? FrontLink : 'div'"
                v-bind="imageLinked ? { href: item.url, target, tabindex: titleLinked ? -1 : undefined, 'aria-hidden': titleLinked ? 'true' : undefined } : {}"
                class="flex size-full items-center justify-center"
            >
                <img
                    v-if="item.image_url"
                    :src="item.image_url"
                    :alt="imageLinked && !titleLinked ? item.title : ''"
                    class="size-full"
                    :style="{ objectFit: s.image_fit }"
                    loading="lazy"
                    draggable="false"
                />
                <ImageIcon v-else class="size-8 text-gray-300" aria-hidden="true" />
            </component>
        </div>

        <div v-if="hasBody" class="flex flex-1 flex-col gap-1 p-3">
            <component :is="headingTag" v-if="s.show_title === 'Y' && item.title" :class="LINE_CLAMP[s.title_lines]" class="leading-snug" :style="partCss(s, 'title', true)">
                <FrontLink v-if="titleLinked" :href="item.url!" :target="target">{{ item.title }}</FrontLink>
                <template v-else>{{ item.title }}</template>
            </component>

            <p v-if="s.show_intro_text === 'Y' && item.intro_text" :class="LINE_CLAMP[s.intro_text_lines]" class="whitespace-pre-line leading-snug" :style="partCss(s, 'intro_text', true)">
                <FrontLink v-if="introLinked" :href="item.url!" :target="target" :tabindex="titleLinked ? -1 : undefined">{{ item.intro_text }}</FrontLink>
                <template v-else>{{ item.intro_text }}</template>
            </p>

            <div v-if="showDate || showViews" class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-0.5 pt-1" :class="JUSTIFY_CLASS[s.title_align] ?? ''">
                <span v-if="showDate" class="inline-flex items-center gap-1" :style="partCss(s, 'date')">
                    <CalendarDays class="size-3.5 shrink-0" aria-hidden="true" />
                    <span class="sr-only">{{ t('published_on') }}</span>
                    <time :datetime="item.date!">{{ formatShortDate(item.date, front.lang) }}</time>
                </span>
                <span v-if="showViews" class="inline-flex items-center gap-1" :style="partCss(s, 'views')">
                    <Eye class="size-3.5 shrink-0" aria-hidden="true" />
                    <span class="sr-only">{{ t('views') }}</span>
                    {{ formatNumber(item.views ?? 0, front.lang) }}
                </span>
            </div>
        </div>
    </article>
</template>
