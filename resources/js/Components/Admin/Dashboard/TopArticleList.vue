<script setup lang="ts">
import type { DashboardTopArticle } from '@/utils/dashboard';
import { formatNumber } from '@/utils/report';
import { Link } from '@inertiajs/vue3';

/** บทความยอดนิยม — แถบสัดส่วนเทียบกับอันดับ 1 */
const props = defineProps<{
    items: DashboardTopArticle[];
}>();

function barWidth(views: number): string {
    const max = props.items[0]?.views ?? 0;
    return max > 0 ? `${Math.max(4, (views / max) * 100)}%` : '0%';
}
</script>

<template>
    <p v-if="items.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีการเข้าชมบทความใน 7 วันล่าสุด</p>
    <ol v-else class="space-y-3">
        <li v-for="item in items" :key="item.rank" class="flex items-start gap-3">
            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                {{ item.rank }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex items-baseline justify-between gap-3">
                    <Link v-if="item.href" :href="item.href" class="truncate text-sm font-medium text-gray-800 hover:text-brand-600">
                        {{ item.title ?? '(ไม่มีชื่อ)' }}
                    </Link>
                    <span v-else class="truncate text-sm font-medium text-gray-800">
                        {{ item.title ?? '(ไม่มีชื่อ)' }}
                        <span v-if="item.deleted" class="text-xs font-normal text-gray-400">(ถูกลบแล้ว)</span>
                    </span>
                    <span class="shrink-0 text-sm font-semibold text-gray-900 tabular-nums">{{ formatNumber(item.views) }}</span>
                </div>
                <p v-if="item.category_title" class="truncate text-xs text-gray-500">{{ item.category_title }}</p>
                <div class="mt-1.5 h-1.5 rounded-full bg-gray-100">
                    <div class="h-1.5 rounded-full bg-[#eb6834]" :style="{ width: barWidth(item.views) }"></div>
                </div>
            </div>
        </li>
    </ol>
</template>
