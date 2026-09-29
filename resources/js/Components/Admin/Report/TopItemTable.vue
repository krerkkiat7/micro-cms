<script setup lang="ts">
import { formatNumber, percent, useReportTerms } from '@/utils/report';
import type { TopRow } from '@/utils/report';
import { Link } from '@inertiajs/vue3';

/**
 * ตารางรายการยอดนิยม — ชื่อลิงก์ไปรายงานของรายการนั้น (เฉพาะเมื่อ itemRoute ไม่เป็น null = มีสิทธิ์ดูรายการ),
 * % เทียบยอดรวมทั้งช่วง, คอลัมน์หมวดหมู่เฉพาะโมดูลที่มีหมวดหมู่
 */
defineProps<{
    rows: TopRow[];
    total: number;
    itemRoute: string | null;
    showCategory: boolean;
}>();

const terms = useReportTerms();
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="w-16 px-4 py-3 text-center font-medium">อันดับ</th>
                        <th class="px-4 py-3 font-medium">ชื่อ{{ terms.item }}</th>
                        <th v-if="showCategory" class="w-44 px-4 py-3 font-medium">หมวดหมู่</th>
                        <th class="w-28 px-4 py-3 text-right font-medium">{{ terms.count }}</th>
                        <th class="w-36 px-4 py-3 text-right font-medium">{{ terms.unique }}</th>
                        <th class="w-24 px-4 py-3 text-right font-medium">สัดส่วน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center font-semibold text-gray-700 tabular-nums">{{ row.rank }}</td>
                        <td class="px-4 py-3">
                            <span v-if="row.deleted" class="text-gray-500">{{ row.title ?? `${terms.item} #${row.id}` }} (ถูกลบแล้ว)</span>
                            <span v-else-if="!itemRoute" class="text-gray-800">{{ row.title ?? `${terms.item} #${row.id}` }}</span>
                            <Link v-else :href="route(itemRoute, row.id)" class="text-brand-600 hover:text-brand-700">
                                {{ row.title ?? `${terms.item} #${row.id}` }}
                            </Link>
                        </td>
                        <td v-if="showCategory" class="px-4 py-3 text-gray-600">{{ row.category_title ?? '-' }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 tabular-nums">{{ formatNumber(row.views) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ formatNumber(row.sessions) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ percent(row.views, total) }}</td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="showCategory ? 6 : 5" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
