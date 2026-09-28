<script setup lang="ts">
import ArticleListView from '@/Components/Front/Article/ArticleListView.vue';
import type { ArticleListData } from '@/Components/Front/Article/ArticleListItem.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import { useFront } from '@/composables/useFront';
import type { ArticleListSetting, ArticleSort } from '@/utils/articleSetting';
import type { PageHeaderData, SeoData } from '@/utils/front';

/**
 * หน้ารายการบทความตามแท็ก (front.article.tag) — หัวเรื่อง h1 = แท็ก "ชื่อแท็ก", ตั้งค่าการแสดงผลชุดเดียวกับรายการของหมวดหมู่
 * ไม่มีข้อความเกริ่นนำ/รายละเอียด/ช่องค้นหา; ไม่พบแท็กหรือไม่มีบทความ = "ไม่พบข้อมูล"
 */
defineProps<{
    tag: string;
    title: string;
    articles: { data: ArticleListData[]; current_page: number; last_page: number; per_page: number; total: number };
    listSetting: ArticleListSetting;
    view: 'card' | 'row';
    sort: ArticleSort;
    baseUrl: string;
    header: PageHeaderData;
    seo: SeoData;
}>();

const { t } = useFront();
</script>

<template>
    <FrontLayout :seo="seo" :header="header">
        <div class="space-y-6">
            <header>
                <h1 class="text-3xl font-bold leading-tight text-gray-900">{{ title }}</h1>
            </header>

            <ArticleListView
                :articles="articles"
                :list-setting="listSetting"
                :view="view"
                :sort="sort"
                :base-url="baseUrl"
                :empty-text="t('no_data')"
            />
        </div>
    </FrontLayout>
</template>
