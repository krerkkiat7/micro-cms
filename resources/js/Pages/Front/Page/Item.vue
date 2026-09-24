<script setup lang="ts">
import PageRow from '@/Components/Front/PageLayout/PageRow.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import { backgroundCss } from '@/utils/front';
import type { PageHeaderData, SeoData } from '@/utils/front';
import type { FrontPageData } from '@/utils/frontPage';

/**
 * หน้าเพจ (front.page.item) — แสดงตามโครงสร้าง แถว → คอลัมน์ → widget เต็มความกว้างหน้าจอ (แต่ละแถวกำหนด container เอง)
 * ชื่อหน้าเป็น h1 ที่ซ่อนไว้ (screen reader / SEO อ่านได้ — หน้าตาของหน้ากำหนดด้วยแถว/widget เอง)
 */
defineProps<{
    page: FrontPageData;
    header: PageHeaderData;
    seo: SeoData;
}>();
</script>

<template>
    <FrontLayout :seo="seo" :header="header" full-width :fonts-url="page.fontsUrl">
        <article :style="backgroundCss(page.background)">
            <h1 class="sr-only">{{ page.title }}</h1>
            <PageRow v-for="row in page.rows" :key="row.id" :row="row" />
        </article>
    </FrontLayout>
</template>
