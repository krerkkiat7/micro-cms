<script setup lang="ts">
import { PERIOD_UNIT, formatNumber, percent } from '@/utils/report';
import type { ReportPeriod, SeriesRow } from '@/utils/report';
import { computed } from 'vue';

/**
 * ตารางข้อมูลรายช่วงเวลา (มุมมองตารางของกราฟ) + แถวรวม
 */
const props = defineProps<{
    rows: SeriesRow[];
    period: ReportPeriod;
}>();

const totals = computed(() => ({
    views: props.rows.reduce((sum, r) => sum + r.views, 0),
    sessions: props.rows.reduce((sum, r) => sum + r.sessions, 0),
    ips: props.rows.reduce((sum, r) => sum + r.ips, 0),
}));

const max = computed(() => Math.max(1, ...props.rows.map((r) => r.views)));
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
        <div class="max-h-[28rem] overflow-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="sticky top-0 border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ PERIOD_UNIT[period] }}</th>
                        <th class="w-32 px-4 py-3 text-right font-medium">ยอดเข้าชม</th>
                        <th class="w-40 px-4 py-3 text-right font-medium">ผู้เข้าชมไม่ซ้ำ</th>
                        <th class="w-32 px-4 py-3 text-right font-medium">IP ไม่ซ้ำ</th>
                        <th class="w-28 px-4 py-3 text-right font-medium">สัดส่วน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in rows" :key="row.key" class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-gray-700">
                            <div class="flex items-center gap-3">
                                <span class="w-48 shrink-0">{{ row.label }}</span>
                                <span class="hidden h-1.5 flex-1 rounded-full bg-gray-100 sm:block">
                                    <span class="block h-1.5 rounded-full bg-brand-500" :style="{ width: `${(row.views / max) * 100}%` }" />
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-right text-gray-900 tabular-nums">{{ formatNumber(row.views) }}</td>
                        <td class="px-4 py-2.5 text-right text-gray-600 tabular-nums">{{ formatNumber(row.sessions) }}</td>
                        <td class="px-4 py-2.5 text-right text-gray-600 tabular-nums">{{ formatNumber(row.ips) }}</td>
                        <td class="px-4 py-2.5 text-right text-gray-500 tabular-nums">{{ percent(row.views, totals.views) }}</td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูล</td>
                    </tr>
                </tbody>
                <tfoot class="sticky bottom-0 border-t border-gray-200 bg-gray-50 font-medium text-gray-800">
                    <tr>
                        <td class="px-4 py-3">รวม</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ formatNumber(totals.views) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums" title="ผลรวมของแต่ละช่วง — ผู้เข้าชมคนเดียวกันในหลายช่วงถูกนับซ้ำ">{{ formatNumber(totals.sessions) }}*</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ formatNumber(totals.ips) }}*</td>
                        <td class="px-4 py-3 text-right tabular-nums">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="border-t border-gray-100 px-4 py-2 text-xs text-gray-500">
            * ผลรวมของแต่ละช่วง ผู้เข้าชมคนเดียวกันที่เข้าหลายช่วงจะถูกนับซ้ำ (ยอดไม่ซ้ำทั้งช่วงดูที่การ์ดสรุปด้านบน)
        </p>
    </div>
</template>
