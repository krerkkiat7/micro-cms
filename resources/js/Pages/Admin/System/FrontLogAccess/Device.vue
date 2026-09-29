<script setup lang="ts">
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import { formatDateTime } from '@/utils/date';
import type { IpStatRow } from '@/utils/logStats';
import { deviceLabel, formatNumber, percent, unknownLabel } from '@/utils/report';
import type { BreakdownItem, ReportFilters, ReportSummary } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    summary: ReportSummary;
    breakdowns: { device_type: BreakdownItem[]; browser: BreakdownItem[]; platform: BreakdownItem[] };
    ips: IpStatRow[];
    robots: { key: string; views: number }[];
}>();

const toItems = (items: BreakdownItem[], label: (key: string | null) => string) => items.map((i) => ({ label: label(i.key), views: i.views }));

const device = computed(() => toItems(props.breakdowns.device_type, deviceLabel));
const browser = computed(() => toItems(props.breakdowns.browser, unknownLabel));
const platform = computed(() => toItems(props.breakdowns.platform, unknownLabel));
const robots = computed(() => props.robots.map((r) => ({ label: r.key, views: r.views })));
</script>

<template>
    <StatsShell log="frontAccess" tab="device" :filters="filters" :show-period="false" :show-chart-type="false">
        <ReportStatCards :summary="summary" />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <BreakdownList title="อุปกรณ์" :items="device" />
            <BreakdownList title="เบราว์เซอร์" :items="browser" />
            <BreakdownList title="ระบบปฏิบัติการ" :items="platform" />
            <BreakdownList title="บอท (ไม่นับรวมในสถิติอื่น)" :items="robots" empty-text="ไม่มีบอทเข้าชมในช่วงนี้" />
        </div>

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">IP Address ที่เข้าชมมากที่สุด (30 อันดับแรก)</h3>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">IP Address</th>
                                <th class="w-36 px-4 py-3 text-right font-medium">เปิดหน้า</th>
                                <th class="w-24 px-4 py-3 text-right font-medium">สัดส่วน</th>
                                <th class="w-56 px-4 py-3 font-medium">เข้าชมล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in ips" :key="row.ip ?? 'none'" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-gray-800">{{ row.ip ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 tabular-nums">{{ formatNumber(row.views) }}</td>
                                <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ percent(row.views, summary.views) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ formatDateTime(row.last_at) }}</td>
                            </tr>
                            <tr v-if="ips.length === 0">
                                <td colspan="4" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">IP ที่เปิดหน้าจำนวนมากผิดปกติอาจเป็นโปรแกรมดึงข้อมูลที่ไม่ได้ระบุตัวเป็นบอท</p>
        </section>
    </StatsShell>
</template>
