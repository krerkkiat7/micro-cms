<script setup lang="ts">
import StatTiles from '@/Components/Admin/LogStats/StatTiles.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { formatDateTime } from '@/utils/date';
import { LOGIN_RESULT_COLORS, LOGIN_RESULT_LABELS } from '@/utils/logStats';
import type { FailedIpRow, LoginCounts, LoginReasonRow } from '@/utils/logStats';
import { formatNumber } from '@/utils/report';
import type { CategoryOption, ReportFilters, SeriesRow } from '@/utils/report';
import { TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: LoginCounts;
    ips: FailedIpRow[];
    reasons: LoginReasonRow[];
    buckets: Omit<SeriesRow, 'views' | 'sessions' | 'ips'>[];
    trend: Record<string, number[]>;
}>();

/** IP ที่ลองหลายบัญชีตั้งแต่เท่านี้ = สัญญาณการเดารหัสผ่านแบบไล่บัญชี */
const MULTI_ACCOUNT = 3;

const suspicious = computed(() => props.ips.filter((ip) => ip.usernames >= MULTI_ACCOUNT || (ip.failed >= 10 && ip.success === 0)));

const tiles = computed(() => [
    {
        label: 'ไม่สำเร็จ + ถูกบล็อก',
        value: formatNumber(props.counts.fail + props.counts.block),
        hint: `จาก ${formatNumber(props.counts.attempts)} ครั้งที่พยายาม`,
        tone: props.counts.fail + props.counts.block > 0 ? ('danger' as const) : undefined,
    },
    { label: 'IP ที่เคยผิดพลาด', value: formatNumber(props.ips.length), hint: 'แสดงสูงสุด 30 IP' },
    {
        label: 'IP น่าสงสัย',
        value: formatNumber(suspicious.value.length),
        hint: `ลอง ≥ ${MULTI_ACCOUNT} บัญชี หรือผิด ≥ 10 ครั้งโดยไม่เคยสำเร็จ`,
        tone: suspicious.value.length > 0 ? ('warning' as const) : undefined,
    },
    { label: 'ถูกบล็อก', value: formatNumber(props.counts.block), hint: 'ระงับชั่วคราว/อัตโนมัติ/บัญชีถูกระงับ' },
]);

const datasets = computed(() =>
    (['fail', 'block'] as const).map((key) => ({ label: LOGIN_RESULT_LABELS[key], data: props.trend[key] ?? [], color: LOGIN_RESULT_COLORS[key] })),
);

const reasonItems = computed(() => props.reasons.map((r) => ({ label: `${r.key} (${LOGIN_RESULT_LABELS[r.result] ?? r.result})`, views: r.views })));

const isSuspicious = (row: FailedIpRow) => row.usernames >= MULTI_ACCOUNT || (row.failed >= 10 && row.success === 0);
</script>

<template>
    <StatsShell log="backLogin" tab="security" :filters="filters" :user-options="userOptions" :show-chart-type="false">
        <StatTiles :items="tiles" />

        <div class="grid gap-4 xl:grid-cols-3">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs xl:col-span-2">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">การเข้าสู่ระบบที่ไม่สำเร็จ / ถูกบล็อก</h3>
                <ViewTrendChart :labels="buckets.map((b) => b.label)" :datasets="datasets" :height="280" />
            </section>
            <BreakdownList title="สาเหตุ" :items="reasonItems" empty-text="ไม่มีการเข้าสู่ระบบที่ไม่สำเร็จในช่วงนี้" />
        </div>

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">IP Address ที่เข้าสู่ระบบไม่สำเร็จ</h3>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">IP Address</th>
                                <th class="w-40 px-4 py-3 text-right font-medium">ไม่สำเร็จ/ถูกบล็อก</th>
                                <th class="w-28 px-4 py-3 text-right font-medium">สำเร็จ</th>
                                <th class="w-40 px-4 py-3 text-right font-medium">จำนวนบัญชีที่ลอง</th>
                                <th class="w-56 px-4 py-3 font-medium">ครั้งล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in ips" :key="row.ip ?? 'none'" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-gray-800">
                                    <span class="inline-flex items-center gap-1.5">
                                        <TriangleAlert v-if="isSuspicious(row)" class="size-4 text-amber-600" aria-label="น่าสงสัย" />
                                        {{ row.ip ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-red-700 tabular-nums">{{ formatNumber(row.failed) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 tabular-nums">{{ formatNumber(row.success) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums" :class="row.usernames >= MULTI_ACCOUNT ? 'font-medium text-amber-700' : 'text-gray-700'">
                                    {{ formatNumber(row.usernames) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ formatDateTime(row.last_at) }}</td>
                            </tr>
                            <tr v-if="ips.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">ไม่มีการเข้าสู่ระบบที่ไม่สำเร็จในช่วงนี้</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                ไอคอนเตือน = IP ที่ลองหลายบัญชี ({{ MULTI_ACCOUNT }} ขึ้นไป) หรือผิดพลาดตั้งแต่ 10 ครั้งโดยไม่เคยสำเร็จ — อาจเป็นการเดารหัสผ่าน ควรพิจารณาบล็อก IP
            </p>
        </section>
    </StatsShell>
</template>
