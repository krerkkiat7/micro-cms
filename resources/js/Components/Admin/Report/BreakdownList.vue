<script setup lang="ts">
import { formatNumber, percent } from '@/utils/report';
import { computed } from 'vue';

/**
 * รายการสัดส่วนแบบแถบแนวนอน (ภาษา / อุปกรณ์ / เบราว์เซอร์ / แหล่งที่มา ฯลฯ) — ตัวเลข + % อยู่ข้างแถบเสมอ ไม่พึ่งสีอย่างเดียว
 */
const props = defineProps<{
    title: string;
    items: { label: string; views: number }[];
    emptyText?: string;
}>();

const total = computed(() => props.items.reduce((sum, i) => sum + i.views, 0));
const max = computed(() => Math.max(1, ...props.items.map((i) => i.views)));
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
        <h3 class="mb-3 text-sm font-semibold text-gray-800">{{ title }}</h3>
        <ul v-if="items.length > 0 && total > 0" class="space-y-2.5">
            <li v-for="item in items" :key="item.label" class="text-sm">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="truncate text-gray-700" :title="item.label">{{ item.label }}</span>
                    <span class="shrink-0 text-gray-900 tabular-nums">
                        {{ formatNumber(item.views) }}
                        <span class="ml-1 text-xs text-gray-500">{{ percent(item.views, total) }}</span>
                    </span>
                </div>
                <div class="mt-1 h-1.5 rounded-full bg-gray-100">
                    <div class="h-1.5 rounded-full bg-brand-500" :style="{ width: `${(item.views / max) * 100}%` }" />
                </div>
            </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-gray-500">{{ emptyText ?? 'ไม่มีข้อมูล' }}</p>
    </div>
</template>
