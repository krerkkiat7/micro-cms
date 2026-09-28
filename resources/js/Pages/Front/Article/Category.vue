<script setup lang="ts">
import ArticleListView from '@/Components/Front/Article/ArticleListView.vue';
import type { ArticleListData } from '@/Components/Front/Article/ArticleListItem.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import { useFront } from '@/composables/useFront';
import type { ArticleListSetting, ArticleSort } from '@/utils/articleSetting';
import type { PageHeaderData, SeoData } from '@/utils/front';

/**
 * หน้ารายการบทความของหมวดหมู่ (front.article.category) — ชื่อหมวดหมู่เป็น h1, ชื่อบทความแต่ละรายการเป็น h2
 * ข้อความเกริ่นนำ/รายละเอียดของหมวดหมู่แสดงตามตั้งค่า (server ส่งค่าว่างมาถ้าปิด) ค้นหาจากชื่อ + เรียงลำดับ + สลับการ์ด/แถวได้
 */
defineProps<{
    category: { id: number; title: string; intro_text: string; detail_html: string; slug: string | null };
    articles: { data: ArticleListData[]; current_page: number; last_page: number; per_page: number; total: number };
    listSetting: ArticleListSetting;
    view: 'card' | 'row';
    sort: ArticleSort;
    q: string;
    baseUrl: string;
    header: PageHeaderData;
    seo: SeoData;
}>();

const { t } = useFront();
</script>

<template>
    <FrontLayout :seo="seo" :header="header">
        <div class="space-y-6">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold leading-tight text-gray-900">{{ category.title }}</h1>
                <p v-if="category.intro_text" class="whitespace-pre-line text-lg text-gray-700">{{ category.intro_text }}</p>
                <div v-if="category.detail_html" class="front-prose" v-html="category.detail_html" />
            </header>

            <ArticleListView
                :articles="articles"
                :list-setting="listSetting"
                :view="view"
                :sort="sort"
                :q="q"
                :base-url="baseUrl"
                searchable
                :empty-text="q ? t('no_data') : t('no_articles')"
            />
        </div>
    </FrontLayout>
</template>
