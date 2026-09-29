<script setup lang="ts">
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import { formatDateTime } from '@/utils/date';
import type { IpStatRow } from '@/utils/logStats';
import { deviceLabel, formatNumber, percent, unknownLabel } from '@/utils/report';
import type { BreakdownItem, CategoryOption, ReportFilters, ReportSummary } from '@/utils/report';
import { TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[];
    summary: ReportSummary;
    breakdowns: { device_type: BreakdownItem[]; browser: BreakdownItem[]; platform: BreakdownItem[] };
    ips: IpStatRow[];
}>();

const toItems = (items: BreakdownItem[], label: (key: string | null) => string) => items.map((i) => ({ label: label(i.key), views: i.views }));

const device = computed(() => toItems(props.breakdowns.device_type, deviceLabel));
const browser = computed(() => toItems(props.breakdowns.browser, unknownLabel));
const platform = computed(() => toItems(props.breakdowns.platform, unknownLabel));
</script>

<template>
    <StatsShell log="backAccess" tab="device" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <ReportStatCards :summary="summary" :show-items="!filters.user_id" />

        <div class="grid gap-4 md:grid-cols-3">
            <BreakdownList title="อุปกรณ์" :items="device" />
            <BreakdownList title="เบราว์เซอร์" :items="browser" />
            <BreakdownList title="ระบบปฏิบัติการ" :items="platform" />
        </div>

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">IP Address ที่เข้าใช้งาน (30 อันดับแรก)</h3>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">IP Address</th>
                                <th class="w-36 px-4 py-3 text-right font-medium">เข้าหน้าจอ</th>
                                <th class="w-36 px-4 py-3 text-right font-medium">จำนวนผู้ใช้งาน</th>
                                <th class="w-24 px-4 py-3 text-right font-medium">สัดส่วน</th>
                                <th class="w-56 px-4 py-3 font-medium">ใช้งานล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in ips" :key="row.ip ?? 'none'" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-gray-800">{{ row.ip ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 tabular-nums">{{ formatNumber(row.views) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <span v-if="row.users > 1" class="inline-flex items-center gap-1 font-medium text-amber-700" title="มีหลายบัญชีใช้งานจาก IP เดียวกัน">
                                        <TriangleAlert class="size-3.5" aria-hidden="true" />
                                        {{ formatNumber(row.users) }} บัญชี
                                    </span>
                                    <span v-else class="text-gray-700">{{ formatNumber(row.users) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ percent(row.views, summary.views) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ formatDateTime(row.last_at) }}</td>
                            </tr>
                            <tr v-if="ips.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">IP เดียวที่มีหลายบัญชีใช้งานอาจเป็นเครือข่ายสำนักงานเดียวกัน หรือควรตรวจสอบการใช้บัญชีร่วมกัน</p>
        </section>
    </StatsShell>
</template>
