<script setup lang="ts">
import StatTiles from '@/Components/Admin/LogStats/StatTiles.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { LOGIN_RESULT_COLORS, LOGIN_RESULT_LABELS } from '@/utils/logStats';
import type { LoginCounts, LoginReasonRow } from '@/utils/logStats';
import { PERIOD_OPTIONS, formatDecimal, formatNumber, percent, useChartType } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportSummary, SeriesRow } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: LoginCounts;
    summary: ReportSummary;
    buckets: Omit<SeriesRow, 'views' | 'sessions' | 'ips'>[];
    trend: Record<string, number[]>;
    reasons: LoginReasonRow[];
}>();

const chartType = useChartType();

const periodLabel = computed(() => PERIOD_OPTIONS.find((p) => p.value === props.filters.period)?.label ?? '');

const change = (value: number | null) => (value === null ? 'ช่วงก่อนหน้าไม่มีข้อมูล' : `${value > 0 ? '+' : ''}${formatDecimal(value)}% จากช่วงก่อนหน้า`);

const tiles = computed(() => [
    { label: 'พยายามเข้าสู่ระบบ', value: formatNumber(props.counts.attempts), hint: change(props.summary.change.views) },
    { label: 'สำเร็จ', value: formatNumber(props.counts.success), hint: `อัตราสำเร็จ ${formatDecimal(props.counts.success_rate)}%`, tone: 'success' as const },
    {
        label: 'ไม่สำเร็จ',
        value: formatNumber(props.counts.fail),
        hint: percent(props.counts.fail, props.counts.attempts),
        tone: props.counts.fail > 0 ? ('danger' as const) : undefined,
    },
    {
        label: 'ถูกบล็อก',
        value: formatNumber(props.counts.block),
        hint: percent(props.counts.block, props.counts.attempts),
        tone: props.counts.block > 0 ? ('warning' as const) : undefined,
    },
    { label: 'ออกจากระบบ', value: formatNumber(props.counts.logout), hint: 'กดออกจากระบบเอง' },
    { label: 'บัญชี / IP ที่ใช้', value: `${formatNumber(props.counts.accounts)} / ${formatNumber(props.counts.ips)}`, hint: 'username ที่กรอก / IP Address' },
]);

const datasets = computed(() =>
    (['success', 'fail', 'block'] as const).map((key) => ({
        label: LOGIN_RESULT_LABELS[key],
        data: props.trend[key] ?? [],
        color: LOGIN_RESULT_COLORS[key],
    })),
);

const reasonItems = computed(() => props.reasons.map((r) => ({ label: `${r.key} (${LOGIN_RESULT_LABELS[r.result] ?? r.result})`, views: r.views })));

const rows = computed(() =>
    props.buckets.map((b, i) => ({
        ...b,
        success: props.trend.success?.[i] ?? 0,
        fail: props.trend.fail?.[i] ?? 0,
        block: props.trend.block?.[i] ?? 0,
    })),
);
</script>

<template>
    <StatsShell v-model:chart-type="chartType" log="backLogin" tab="overview" :filters="filters" :user-options="userOptions">
        <StatTiles :items="tiles" :cols="6" />

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ผลการเข้าสู่ระบบ{{ periodLabel }}</h3>
            <ViewTrendChart :labels="buckets.map((b) => b.label)" :datasets="datasets" :type="chartType" />
        </section>

        <div class="grid gap-4 xl:grid-cols-3">
            <BreakdownList class="xl:col-span-1" title="สาเหตุที่เข้าสู่ระบบไม่สำเร็จ" :items="reasonItems" empty-text="ไม่มีการเข้าสู่ระบบที่ไม่สำเร็จในช่วงนี้" />

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs xl:col-span-2">
                <div class="max-h-[28rem] overflow-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="sticky top-0 border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">ช่วงเวลา</th>
                                <th class="w-28 px-4 py-3 text-right font-medium">สำเร็จ</th>
                                <th class="w-28 px-4 py-3 text-right font-medium">ไม่สำเร็จ</th>
                                <th class="w-28 px-4 py-3 text-right font-medium">ถูกบล็อก</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in rows" :key="row.key" class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 text-gray-700">{{ row.label }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-900 tabular-nums">{{ formatNumber(row.success) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums" :class="row.fail > 0 ? 'font-medium text-red-700' : 'text-gray-500'">{{ formatNumber(row.fail) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums" :class="row.block > 0 ? 'font-medium text-amber-700' : 'text-gray-500'">{{ formatNumber(row.block) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </StatsShell>
</template>
