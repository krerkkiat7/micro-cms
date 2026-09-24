<script setup lang="ts">
import { CalendarDays, Eye, Tag } from 'lucide-vue-next';
import PartList from '@/Components/Front/ContentPart/PartList.vue';
import FrontLink from '@/Components/Front/FrontLink.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import { useFront } from '@/composables/useFront';
import { formatDate, formatNumber } from '@/utils/front';
import type { FrontFileData, FrontPart, PageHeaderData, SeoData } from '@/utils/front';

/**
 * รายละเอียดบทความ (front.article.item / front.article.category.item) — ชื่อบทความเป็น h1, หัวข้อของแต่ละ part เป็น h2
 * วันที่เผยแพร่ใช้ <time datetime> (อ่านได้ทั้งคนและเครื่อง — SEO/AEO)
 */
defineProps<{
    article: {
        id: number;
        title: string;
        intro_text: string;
        image: FrontFileData | null;
        published_at: string | null;
        updated_at: string | null;
        views: number;
        parts: FrontPart[];
        tags: string[];
    };
    category: { id: number; title: string; url: string } | null;
    header: PageHeaderData;
    seo: SeoData;
}>();

const { front, t } = useFront();
</script>

<template>
    <FrontLayout :seo="seo" :header="header">
        <article class="mx-auto max-w-4xl space-y-8">
            <header class="space-y-4">
                <p v-if="category">
                    <FrontLink :href="category.url" class="text-sm font-medium text-brand-700 hover:underline">{{ category.title }}</FrontLink>
                </p>
                <h1 class="text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">{{ article.title }}</h1>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-gray-600">
                    <span v-if="article.published_at" class="inline-flex items-center gap-1.5">
                        <CalendarDays class="size-4" aria-hidden="true" />
                        {{ t('published_on') }}
                        <time :datetime="article.published_at">{{ formatDate(article.published_at, front.lang) }}</time>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <Eye class="size-4" aria-hidden="true" />
                        {{ t('views_count', { count: formatNumber(article.views, front.lang) }) }}
                    </span>
                </div>
                <p v-if="article.intro_text" class="whitespace-pre-line text-lg leading-relaxed text-gray-700">{{ article.intro_text }}</p>
            </header>

            <!-- ไม่มีเนื้อหาแบบ part เลย แสดงรูปหน้าปกแทน -->
            <figure v-if="article.image && article.parts.length === 0">
                <img :src="article.image.url" alt="" class="h-auto w-full rounded-lg" />
            </figure>

            <PartList :parts="article.parts" heading-tag="h2" />

            <footer v-if="article.tags.length" class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-5">
                <span class="inline-flex items-center gap-1 text-sm font-medium text-gray-700"><Tag class="size-4" aria-hidden="true" /> {{ t('tags') }}:</span>
                <ul class="flex flex-wrap gap-2">
                    <li v-for="tag in article.tags" :key="tag" class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ tag }}</li>
                </ul>
            </footer>
        </article>
    </FrontLayout>
</template>
