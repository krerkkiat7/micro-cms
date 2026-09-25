<script setup lang="ts">
import { computed } from 'vue';
import { Eye, Image as ImageIcon } from 'lucide-vue-next';
import FrontLink from '@/Components/Front/FrontLink.vue';
import ReadAllLink from '@/Components/Front/PageLayout/widgets/ReadAllLink.vue';
import WidgetCard from '@/Components/Front/PageLayout/widgets/WidgetCard.vue';
import { useFront } from '@/composables/useFront';
import { formatNumber, formatShortDate, intlLocale } from '@/utils/front';
import { LINE_CLAMP, imageFrameCss, itemBoxCss, itemTarget, partCss, perRowVars } from '@/utils/frontPage';
import type { FrontWidgetItem } from '@/utils/frontPage';

/**
 * widget Grid (จาก article / จาก banner) ที่หน้าบ้าน — กล่องเรียงต่อเนื่องหลายคอลัมน์ ไม่เลื่อน (จำนวนคอลัมน์ต่อขนาดหน้าจอผ่าน CSS
 * .front-grid ใน app.css) 3 รูปแบบตาม display_type: การ์ด / แถวที่มีรูปภาพ / แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ (เหมือน widgets/GridPreview.vue)
 */
const props = defineProps<{
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setting: Record<string, any>;
    items: FrontWidgetItem[];
    hasMeta: boolean;
    headingTag: string;
}>();

const { front, t } = useFront();

const s = computed(() => props.setting);
const readAllOnTop = computed(() => String(s.value.read_all_position ?? '').startsWith('top'));

/** กล่องวันที่: เลขวันตัวใหญ่ + เดือนย่อ/ปีตัวเล็ก (ไทยใช้ พ.ศ. 2 หลักท้าย) */
function dateBox(value: string | null | undefined): { day: string; monthYear: string } {
    if (!value) return { day: '-', monthYear: '' };

    const date = new Date(`${value}T00:00:00`);
    const month = date.toLocaleDateString(intlLocale(front.value.lang), { month: 'short' });
    const year = front.value.lang === 'th' ? String(date.getFullYear() + 543).slice(-2) : String(date.getFullYear());

    return { day: String(date.getDate()), monthYear: `${month} ${year}` };
}
</script>

<template>
    <div v-if="items.length">
        <ReadAllLink v-if="readAllOnTop" :setting="setting" class="mb-3" />

        <ul class="front-grid grid gap-4" :style="perRowVars(s)">
            <li v-for="item in items" :key="item.id" class="min-w-0">
                <WidgetCard v-if="s.display_type === 'card'" :setting="s" :item="item" :has-meta="hasMeta" :heading-tag="headingTag" />

                <article v-else class="flex h-full items-stretch gap-3 overflow-hidden p-2" :class="s.rounded_corners === 'Y' ? 'rounded-lg' : ''" :style="itemBoxCss(s)">
                    <!-- แถวที่มีรูปภาพ -->
                    <div
                        v-if="s.display_type === 'row_image' && s.show_image === 'Y'"
                        class="relative flex shrink-0 items-center justify-center overflow-hidden rounded-md"
                        :style="{ ...imageFrameCss(s), width: `${s.image_width_percent}%` }"
                    >
                        <component
                            :is="s.image_clickable === 'Y' && item.url ? FrontLink : 'div'"
                            v-bind="s.image_clickable === 'Y' && item.url ? { href: item.url, target: itemTarget(s, item), tabindex: -1, 'aria-hidden': 'true' } : {}"
                            class="flex size-full items-center justify-center"
                        >
                            <img v-if="item.image_url" :src="item.image_url" alt="" class="size-full" :style="{ objectFit: s.image_fit }" loading="lazy" />
                            <ImageIcon v-else class="size-6 text-gray-300" aria-hidden="true" />
                        </component>
                    </div>

                    <!-- แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ -->
                    <div
                        v-else-if="s.display_type === 'row_date'"
                        class="flex w-16 shrink-0 flex-col items-center justify-center rounded-md py-2 text-center"
                        :style="{ backgroundColor: s.date_box_background }"
                    >
                        <time :datetime="item.date ?? undefined" class="flex flex-col items-center">
                            <span class="sr-only">{{ t('published_on') }} {{ formatShortDate(item.date, front.lang) }}</span>
                            <span class="leading-none" :style="partCss(s, 'date_day')" aria-hidden="true">{{ dateBox(item.date).day }}</span>
                            <span class="mt-1 leading-none" :style="partCss(s, 'date_month')" aria-hidden="true">{{ dateBox(item.date).monthYear }}</span>
                        </time>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col justify-center gap-1 py-1">
                        <component :is="headingTag" v-if="item.title" :class="LINE_CLAMP[s.title_lines]" class="leading-snug" :style="partCss(s, 'title', true)">
                            <FrontLink v-if="s.title_clickable === 'Y' && item.url" :href="item.url" :target="itemTarget(s, item)" class="hover:underline">{{ item.title }}</FrontLink>
                            <template v-else>{{ item.title }}</template>
                        </component>
                        <p v-if="s.show_intro_text === 'Y' && item.intro_text" :class="LINE_CLAMP[s.intro_text_lines]" class="whitespace-pre-line leading-snug" :style="partCss(s, 'intro_text', true)">
                            {{ item.intro_text }}
                        </p>
                        <div
                            v-if="hasMeta && ((s.display_type === 'row_image' && s.show_date === 'Y' && item.date) || s.show_views === 'Y')"
                            class="flex flex-wrap items-center gap-x-3 gap-y-0.5"
                        >
                            <span v-if="s.display_type === 'row_image' && s.show_date === 'Y' && item.date" :style="partCss(s, 'date')">
                                <time :datetime="item.date">{{ formatShortDate(item.date, front.lang) }}</time>
                            </span>
                            <span v-if="s.show_views === 'Y'" class="inline-flex items-center gap-1" :style="partCss(s, 'views')">
                                <Eye class="size-3.5 shrink-0" aria-hidden="true" />
                                <span class="sr-only">{{ t('views') }}</span>
                                {{ formatNumber(item.views ?? 0, front.lang) }}
                            </span>
                        </div>
                    </div>
                </article>
            </li>
        </ul>

        <ReadAllLink v-if="!readAllOnTop" :setting="setting" class="mt-4" />
    </div>
</template>
