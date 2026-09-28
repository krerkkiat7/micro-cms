<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, Eye, Image as ImageIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { useFront } from '@/composables/useFront';
import { formatDate, formatNumber } from '@/utils/front';
import { imageFrameCss, LINE_CLAMP } from '@/utils/frontPage';
import type { ArticleListViewSetting } from '@/utils/articleSetting';

/**
 * บทความ 1 รายการในหน้ารายการ (หมวดหมู่ / แท็ก) — แบบการ์ด (รูปบน) หรือแถว (รูปซ้าย) ชื่อบทความเป็น h2 และเป็นลิงก์หลัก
 * (รูปเป็นลิงก์ซ้ำ — ไม่อยู่ในลำดับ Tab และซ่อนจาก screen reader)
 * อัตราส่วน/การแสดงรูป/จำนวนบรรทัดตามตั้งค่าบทความของมุมมองนั้น (`setting`) — อัตราส่วน natural (เฉพาะแถว) = รูปตามขนาดจริง ไม่ครอป
 */
export interface ArticleListData {
    id: number;
    title: string;
    intro_text: string;
    url: string;
    image_url: string | null;
    date: string | null;
    views: number;
}

const props = defineProps<{
    article: ArticleListData;
    view: 'card' | 'row';
    setting: ArticleListViewSetting;
    showDate: boolean;
    showViews: boolean;
}>();

const { front, t } = useFront();

const natural = computed(() => props.setting.aspect_ratio === 'natural');
const frameStyle = computed(() => (natural.value ? {} : imageFrameCss(props.setting)));
const imageClass = computed(() => {
    if (natural.value) return 'h-auto w-full';

    return ['size-full', props.setting.image_fit === 'contain' ? 'object-contain' : 'object-cover transition-transform duration-300 group-hover:scale-105'];
});
</script>

<template>
    <article
        class="group flex overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md"
        :class="view === 'card' ? 'h-full flex-col' : 'flex-col sm:flex-row sm:items-start'"
    >
        <Link
            :href="article.url"
            tabindex="-1"
            aria-hidden="true"
            class="relative block shrink-0 overflow-hidden"
            :class="[view === 'row' ? 'sm:w-72' : '', natural ? '' : 'bg-gray-100']"
            :style="frameStyle"
        >
            <img v-if="article.image_url" :src="article.image_url" alt="" :class="imageClass" loading="lazy" />
            <span v-else class="flex size-full min-h-32 items-center justify-center bg-gray-100 text-gray-300"><ImageIcon class="size-10" /></span>
        </Link>

        <div class="flex flex-1 flex-col gap-2 p-4" :class="view === 'row' ? 'self-stretch' : ''">
            <h2 class="text-lg font-semibold leading-snug text-gray-900" :class="LINE_CLAMP[setting.title_lines]">
                <Link :href="article.url" class="hover:text-brand-700">{{ article.title }}</Link>
            </h2>
            <p v-if="article.intro_text" class="whitespace-pre-line text-gray-700" :class="LINE_CLAMP[setting.intro_lines]">{{ article.intro_text }}</p>
            <div v-if="(showDate && article.date) || showViews" class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 pt-2 text-sm text-gray-600">
                <span v-if="showDate && article.date" class="inline-flex items-center gap-1">
                    <CalendarDays class="size-4" aria-hidden="true" />
                    <span class="sr-only">{{ t('published_on') }}</span>
                    <time :datetime="article.date">{{ formatDate(article.date, front.lang) }}</time>
                </span>
                <span v-if="showViews" class="inline-flex items-center gap-1">
                    <Eye class="size-4" aria-hidden="true" />
                    {{ t('views_count', { count: formatNumber(article.views, front.lang) }) }}
                </span>
            </div>
        </div>
    </article>
</template>
