<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { LayoutGrid, List } from 'lucide-vue-next';
import ArticleListItem from '@/Components/Front/Article/ArticleListItem.vue';
import type { ArticleListData } from '@/Components/Front/Article/ArticleListItem.vue';
import FrontPagination from '@/Components/Front/FrontPagination.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import { useFront } from '@/composables/useFront';
import type { FrontFileData, PageHeaderData, SeoData } from '@/utils/front';

/**
 * หน้ารายการบทความของหมวดหมู่ (front.article.category) — ชื่อหมวดหมู่เป็น h1, ชื่อบทความแต่ละรายการเป็น h2
 * สลับการ์ด/แถวได้ (ค่าเริ่มต้นตามตั้งค่าบทความ) จำนวนต่อหน้าตามตั้งค่า
 */
const props = defineProps<{
    category: { id: number; title: string; intro_text: string; detail_html: string; slug: string | null; image: FrontFileData | null };
    articles: { data: ArticleListData[]; current_page: number; last_page: number; per_page: number; total: number };
    view: 'card' | 'row';
    baseUrl: string;
    header: PageHeaderData;
    seo: SeoData;
}>();

const { t } = useFront();

function urlFor(page: number, view: string = props.view): string {
    const params = new URLSearchParams();
    if (page > 1) params.set('page', String(page));
    if (view) params.set('view', view);
    const query = params.toString();

    return query ? `${props.baseUrl}?${query}` : props.baseUrl;
}

function setView(view: 'card' | 'row'): void {
    if (view === props.view) return;
    router.get(urlFor(props.articles.current_page, view), {}, { preserveScroll: true, preserveState: true });
}

const views = [
    { value: 'card' as const, icon: LayoutGrid, key: 'view_card' },
    { value: 'row' as const, icon: List, key: 'view_row' },
];
</script>

<template>
    <FrontLayout :seo="seo" :header="header">
        <div class="space-y-6">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold leading-tight text-gray-900">{{ category.title }}</h1>
                <p v-if="category.intro_text" class="whitespace-pre-line text-lg text-gray-700">{{ category.intro_text }}</p>
                <div v-if="category.detail_html" class="front-prose" v-html="category.detail_html" />
            </header>

            <div class="flex flex-wrap items-center justify-between gap-3 border-y border-gray-200 py-3">
                <p class="text-sm text-gray-600">
                    {{ t('page_of', { current: articles.current_page, total: Math.max(1, articles.last_page) }) }}
                </p>
                <div role="group" :aria-label="t('view_mode')" class="inline-flex overflow-hidden rounded-md border border-gray-300">
                    <button
                        v-for="option in views"
                        :key="option.value"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm"
                        :class="view === option.value ? 'bg-brand-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        :aria-pressed="view === option.value"
                        @click="setView(option.value)"
                    >
                        <component :is="option.icon" class="size-4" aria-hidden="true" />
                        {{ t(option.key) }}
                    </button>
                </div>
            </div>

            <p v-if="articles.data.length === 0" class="rounded-lg border border-dashed border-gray-300 py-12 text-center text-gray-600">{{ t('no_articles') }}</p>

            <ul v-else :class="view === 'card' ? 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3' : 'space-y-4'">
                <li v-for="article in articles.data" :key="article.id">
                    <ArticleListItem :article="article" :view="view" />
                </li>
            </ul>

            <FrontPagination :current-page="articles.current_page" :last-page="articles.last_page" :url-for="(page) => urlFor(page)" />
        </div>
    </FrontLayout>
</template>
