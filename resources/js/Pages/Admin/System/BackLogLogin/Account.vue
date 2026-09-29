<script setup lang="ts">
import StatTiles from '@/Components/Admin/LogStats/StatTiles.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import { formatDateTime } from '@/utils/date';
import type { LoginAccountRow, LoginCounts } from '@/utils/logStats';
import { formatDecimal, formatNumber } from '@/utils/report';
import type { CategoryOption, ReportFilters } from '@/utils/report';
import { ArrowDown, ArrowUp, ArrowUpDown, TriangleAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: LoginCounts;
    accounts: LoginAccountRow[];
}>();

/** บัญชีที่ไม่สำเร็จ/ถูกบล็อกรวมตั้งแต่เท่านี้ขึ้นไป = ไฮไลต์ให้ตรวจสอบ */
const ALERT_FAILS = 5;

type SortKey = 'success' | 'fail' | 'block' | 'logout' | 'ips' | 'last_success_at' | 'last_fail_at';
const sortKey = ref<SortKey>('fail');
const sortDir = ref<'asc' | 'desc'>('desc');

const columns: { key: SortKey; label: string }[] = [
    { key: 'success', label: 'สำเร็จ' },
    { key: 'fail', label: 'ไม่สำเร็จ' },
    { key: 'block', label: 'ถูกบล็อก' },
    { key: 'logout', label: 'ออกจากระบบ' },
    { key: 'ips', label: 'IP ที่ใช้' },
    { key: 'last_success_at', label: 'สำเร็จล่าสุด' },
    { key: 'last_fail_at', label: 'ไม่สำเร็จล่าสุด' },
];

const sorted = computed(() =>
    [...props.accounts].sort((a, b) => {
        const x = a[sortKey.value] ?? '';
        const y = b[sortKey.value] ?? '';
        const cmp = x < y ? -1 : x > y ? 1 : 0;
        return sortDir.value === 'asc' ? cmp : -cmp;
    }),
);

function sortBy(key: SortKey) {
    sortDir.value = sortKey.value === key && sortDir.value === 'desc' ? 'asc' : 'desc';
    sortKey.value = key;
}

function sortIcon(key: SortKey) {
    if (sortKey.value !== key) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function cell(row: LoginAccountRow, key: SortKey): string {
    if (key === 'last_success_at' || key === 'last_fail_at') return formatDateTime(row[key]);
    return formatNumber(row[key]);
}

const alerts = computed(() => props.accounts.filter((a) => a.fail + a.block >= ALERT_FAILS).length);
const unknown = computed(() => props.accounts.filter((a) => a.user_id === null && a.success === 0).length);

const tiles = computed(() => [
    { label: 'บัญชีที่มีการเข้าสู่ระบบ', value: formatNumber(props.counts.accounts), hint: 'นับตาม username ที่กรอก' },
    { label: 'อัตราสำเร็จ', value: `${formatDecimal(props.counts.success_rate)}%`, hint: `${formatNumber(props.counts.success)} จาก ${formatNumber(props.counts.attempts)} ครั้ง` },
    {
        label: `บัญชีที่ผิดพลาด ≥ ${ALERT_FAILS} ครั้ง`,
        value: formatNumber(alerts.value),
        hint: 'ควรตรวจสอบ (ลืมรหัสผ่าน / ถูกเดารหัสผ่าน)',
        tone: alerts.value > 0 ? ('danger' as const) : undefined,
    },
    {
        label: 'username ที่ไม่เคยสำเร็จ',
        value: formatNumber(unknown.value),
        hint: 'ไม่พบบัญชี / พิมพ์ผิด / ลองสุ่ม',
        tone: unknown.value > 0 ? ('warning' as const) : undefined,
    },
]);
</script>

<template>
    <StatsShell log="backLogin" tab="account" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <StatTiles :items="tiles" />

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Username / ผู้ใช้งาน</th>
                            <th v-for="col in columns" :key="col.key" class="px-4 py-3 text-right font-medium whitespace-nowrap">
                                <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy(col.key)">
                                    {{ col.label }}
                                    <component :is="sortIcon(col.key)" class="size-3.5" :class="sortKey === col.key ? 'text-brand-500' : 'text-gray-400'" />
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in sorted" :key="row.username ?? 'none'" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 text-gray-800">
                                    <TriangleAlert
                                        v-if="row.fail + row.block >= ALERT_FAILS"
                                        class="size-4 text-red-600"
                                        aria-label="ผิดพลาดบ่อย"
                                    />
                                    {{ row.username ?? '-' }}
                                </span>
                                <p class="text-xs text-gray-500">{{ row.name ?? (row.success === 0 ? 'ไม่เคยเข้าสู่ระบบสำเร็จในช่วงนี้' : '-') }}</p>
                            </td>
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                                :class="{
                                    'font-medium text-red-700': col.key === 'fail' && row.fail > 0,
                                    'font-medium text-amber-700': col.key === 'block' && row.block > 0,
                                    'text-gray-700': !((col.key === 'fail' && row.fail > 0) || (col.key === 'block' && row.block > 0)),
                                }"
                            >
                                {{ cell(row, col.key) }}
                            </td>
                        </tr>
                        <tr v-if="accounts.length === 0">
                            <td :colspan="columns.length + 1" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-xs text-gray-500">แสดงสูงสุด 100 บัญชี (ส่งออก CSV ได้ครบ) — ไอคอนเตือน = ผิดพลาด/ถูกบล็อกรวม {{ ALERT_FAILS }} ครั้งขึ้นไปในช่วงนี้</p>
    </StatsShell>
</template>
