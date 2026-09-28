<script setup lang="ts">
import { CalendarDays, Eye, Printer, Tag } from 'lucide-vue-next';
import ShareButtons from '@/Components/Front/Article/ShareButtons.vue';
import PartList from '@/Components/Front/ContentPart/PartList.vue';
import FrontLink from '@/Components/Front/FrontLink.vue';
import FrontLayout from '@/Layouts/Front/FrontLayout.vue';
import { useFront } from '@/composables/useFront';
import { formatDate, formatNumber } from '@/utils/front';
import type { ArticleDetailSetting } from '@/utils/articleSetting';
import type { FrontFileData, FrontPart, PageHeaderData, SeoData } from '@/utils/front';

/**
 * รายละเอียดบทความ (front.article.item / front.article.category.item) — ชื่อบทความเป็น h1, หัวข้อของแต่ละ part เป็น h2
 * ลำดับ: หัวเรื่อง → วันที่เผยแพร่ + จำนวนเข้าชม + ปุ่มพิมพ์ → รูปหน้าปก → แชร์ (บน) → เนื้อหา → แชร์ (ล่าง) → แท็ก
 * ปุ่มพิมพ์ / รูปหน้าปก / ตำแหน่งแชร์ ตามตั้งค่าบทความ (detailSetting); แท็กลิงก์ไปหน้ารายการบทความตามแท็ก
 * วันที่เผยแพร่ใช้ <time datetime> (อ่านได้ทั้งคนและเครื่อง — SEO/AEO)
 */
const props = defineProps<{
    article: {
        id: number;
        title: string;
        image: FrontFileData | null;
        published_at: string | null;
        updated_at: string | null;
        views: number;
        parts: FrontPart[];
        tags: { name: string; url: string }[];
    };
    detailSetting: ArticleDetailSetting;
    shareUrl: string;
    header: PageHeaderData;
    seo: SeoData;
}>();

const { front, t } = useFront();

const shareTop = ['top', 'both'].includes(props.detailSetting.share_position);
const shareBottom = ['bottom', 'both'].includes(props.detailSetting.share_position);

function print(): void {
    window.print();
}
</script>

<template>
    <FrontLayout :seo="seo" :header="header">
        <article class="mx-auto max-w-4xl space-y-8">
            <header class="space-y-4">
                <h1 class="text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">{{ article.title }}</h1>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-600">
                    <span v-if="article.published_at" class="inline-flex items-center gap-1.5">
                        <CalendarDays class="size-4" aria-hidden="true" />
                        {{ t('published_on') }}
                        <time :datetime="article.published_at">{{ formatDate(article.published_at, front.lang) }}</time>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <Eye class="size-4" aria-hidden="true" />
                        {{ t('views_count', { count: formatNumber(article.views, front.lang) }) }}
                    </span>
                    <button
                        v-if="detailSetting.show_print"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1 text-gray-700 transition-colors hover:bg-gray-50 print:hidden"
                        @click="print"
                    >
                        <Printer class="size-4" aria-hidden="true" />
                        {{ t('print') }}
                    </button>
                </div>
            </header>

            <figure v-if="detailSetting.show_cover && article.image">
                <img :src="article.image.url" alt="" class="h-auto w-full rounded-lg" />
            </figure>

            <ShareButtons v-if="shareTop" :url="shareUrl" :title="article.title" />

            <PartList :parts="article.parts" heading-tag="h2" />

            <ShareButtons v-if="shareBottom" :url="shareUrl" :title="article.title" />

            <footer v-if="article.tags.length" class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-5">
                <span class="inline-flex items-center gap-1 text-sm font-medium text-gray-700"><Tag class="size-4" aria-hidden="true" /> {{ t('tags') }}:</span>
                <ul class="flex flex-wrap gap-2">
                    <li v-for="tag in article.tags" :key="tag.name">
                        <FrontLink
                            :href="tag.url"
                            class="inline-block cursor-pointer rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700 transition-colors hover:bg-brand-700 hover:text-white"
                        >
                            {{ tag.name }}
                        </FrontLink>
                    </li>
                </ul>
            </footer>
        </article>
    </FrontLayout>
</template>
