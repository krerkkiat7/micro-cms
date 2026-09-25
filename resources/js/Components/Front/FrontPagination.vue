<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useFront } from '@/composables/useFront';

/**
 * การแบ่งหน้าของหน้าบ้าน — <nav aria-label> + ลิงก์จริง (?page=n — crawler ตามไปได้), หน้าปัจจุบัน aria-current="page"
 * เลขหน้าที่แสดง: หน้าแรก/สุดท้าย + รอบหน้าปัจจุบัน ±2 (ที่เหลือเป็น …)
 */
const props = defineProps<{
    currentPage: number;
    lastPage: number;
    /** URL ของหน้าที่ n */
    urlFor: (page: number) => string;
}>();

const { t } = useFront();

const pages = computed<(number | '…')[]>(() => {
    const result: (number | '…')[] = [];

    for (let page = 1; page <= props.lastPage; page++) {
        if (page === 1 || page === props.lastPage || Math.abs(page - props.currentPage) <= 2) {
            result.push(page);
        } else if (result[result.length - 1] !== '…') {
            result.push('…');
        }
    }

    return result;
});

const item = 'inline-flex min-h-10 min-w-10 items-center justify-center rounded-md border px-3 text-sm';
</script>

<template>
    <nav v-if="lastPage > 1" :aria-label="t('pagination')" class="flex justify-center">
        <ul class="flex flex-wrap items-center gap-1.5">
            <li>
                <Link v-if="currentPage > 1" :href="urlFor(currentPage - 1)" :class="[item, 'border-gray-300 bg-white hover:bg-gray-50']" rel="prev">
                    <ChevronLeft class="size-4" aria-hidden="true" /><span class="sr-only">{{ t('previous') }}</span>
                </Link>
            </li>
            <li v-for="(page, index) in pages" :key="index">
                <span v-if="page === '…'" class="px-1 text-gray-500" aria-hidden="true">…</span>
                <span v-else-if="page === currentPage" :class="[item, 'border-brand-700 bg-brand-700 font-semibold text-white']" aria-current="page">
                    <span class="sr-only">{{ t('page_of', { current: page, total: lastPage }) }}</span><span aria-hidden="true">{{ page }}</span>
                </span>
                <Link v-else :href="urlFor(page)" :class="[item, 'border-gray-300 bg-white hover:bg-gray-50']" :aria-label="t('go_to_page', { number: page })">
                    {{ page }}
                </Link>
            </li>
            <li>
                <Link v-if="currentPage < lastPage" :href="urlFor(currentPage + 1)" :class="[item, 'border-gray-300 bg-white hover:bg-gray-50']" rel="next">
                    <span class="sr-only">{{ t('next') }}</span><ChevronRight class="size-4" aria-hidden="true" />
                </Link>
            </li>
        </ul>
    </nav>
</template>
