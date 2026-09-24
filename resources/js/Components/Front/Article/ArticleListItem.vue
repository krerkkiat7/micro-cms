<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, Eye, Image as ImageIcon } from 'lucide-vue-next';
import { useFront } from '@/composables/useFront';
import { formatDate, formatNumber } from '@/utils/front';

/**
 * บทความ 1 รายการในหน้ารายการหมวดหมู่ — แบบการ์ด (รูปบน) หรือแถว (รูปซ้าย) ชื่อบทความเป็น h2 และเป็นลิงก์หลัก
 * (รูปเป็นลิงก์ซ้ำ — ไม่อยู่ในลำดับ Tab และซ่อนจาก screen reader)
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

defineProps<{
    article: ArticleListData;
    view: 'card' | 'row';
}>();

const { front, t } = useFront();
</script>

<template>
    <article
        class="group flex overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md"
        :class="view === 'card' ? 'h-full flex-col' : 'flex-col sm:flex-row'"
    >
        <Link
            :href="article.url"
            tabindex="-1"
            aria-hidden="true"
            class="relative block shrink-0 overflow-hidden bg-gray-100"
            :class="view === 'card' ? 'aspect-video' : 'aspect-video sm:aspect-auto sm:w-72'"
        >
            <img v-if="article.image_url" :src="article.image_url" alt="" class="size-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy" />
            <span v-else class="flex size-full min-h-32 items-center justify-center text-gray-300"><ImageIcon class="size-10" /></span>
        </Link>

        <div class="flex flex-1 flex-col gap-2 p-4">
            <h2 class="text-lg font-semibold leading-snug text-gray-900">
                <Link :href="article.url" class="hover:text-brand-700 hover:underline">{{ article.title }}</Link>
            </h2>
            <p v-if="article.intro_text" class="line-clamp-3 whitespace-pre-line text-gray-700">{{ article.intro_text }}</p>
            <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 pt-2 text-sm text-gray-600">
                <span v-if="article.date" class="inline-flex items-center gap-1">
                    <CalendarDays class="size-4" aria-hidden="true" />
                    <span class="sr-only">{{ t('published_on') }}</span>
                    <time :datetime="article.date">{{ formatDate(article.date, front.lang) }}</time>
                </span>
                <span class="inline-flex items-center gap-1">
                    <Eye class="size-4" aria-hidden="true" />
                    {{ t('views_count', { count: formatNumber(article.views, front.lang) }) }}
                </span>
            </div>
        </div>
    </article>
</template>
