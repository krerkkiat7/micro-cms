<script setup lang="ts">
import { formatDateTime } from '@/utils/date';
import { userLabel } from '@/utils/backLogAccessReport';
import type { UserStatRow } from '@/utils/backLogAccessReport';
import { filterQuery, formatDuration, formatNumber, percent } from '@/utils/report';
import type { ReportFilters } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/**
 * ตารางสถิติรายผู้ใช้งาน — เรียงคอลัมน์ได้ในหน้าจอ (ข้อมูลชุดเดียวกัน ไม่ต้องโหลดใหม่),
 * กดชื่อ = ดูภาพรวมเฉพาะผู้ใช้งานคนนั้นในช่วงวันที่เดียวกัน; compact = ย่อคอลัมน์ (ใช้ในภาพรวม)
 */
const props = defineProps<{
    rows: UserStatRow[];
    total: number;
    filters: ReportFilters;
    compact?: boolean;
}>();

type SortKey = 'views' | 'sessions' | 'active_days' | 'pages' | 'ips' | 'total_seconds' | 'avg_seconds' | 'last_at';

const sortKey = ref<SortKey>('views');
const sortDir = ref<'asc' | 'desc'>('desc');

const columns = computed<{ key: SortKey; label: string }[]>(() => [
    { key: 'views', label: 'เข้าหน้าจอ' },
    { key: 'sessions', label: 'เข้าระบบ' },
    ...(props.compact
        ? []
        : [
              { key: 'active_days' as const, label: 'วันที่ใช้งาน' },
              { key: 'pages' as const, label: 'หน้าจอต่างกัน' },
              { key: 'ips' as const, label: 'IP ที่ใช้' },
          ]),
    { key: 'total_seconds', label: 'เวลาใช้งานรวม' },
    ...(props.compact ? [] : [{ key: 'avg_seconds' as const, label: 'เฉลี่ย/หน้าจอ' }]),
    { key: 'last_at', label: 'ใช้งานล่าสุด' },
]);

const sorted = computed(() =>
    [...props.rows].sort((a, b) => {
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

function cell(row: UserStatRow, key: SortKey): string {
    switch (key) {
        case 'total_seconds':
        case 'avg_seconds':
            return formatDuration(row[key]);
        case 'last_at':
            return formatDateTime(row.last_at);
        default:
            return formatNumber(row[key]);
    }
}

const userHref = (row: UserStatRow) =>
    route('admin.system.backlog.access.overview', { ...filterQuery(props.filters), user_id: String(row.user_id) });
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" :class="compact ? 'min-w-[720px]' : 'min-w-[1100px]'">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">ผู้ใช้งาน</th>
                        <th v-if="!compact" class="w-40 px-4 py-3 font-medium">กลุ่มผู้ใช้งาน</th>
                        <th v-for="col in columns" :key="col.key" class="px-4 py-3 text-right font-medium whitespace-nowrap">
                            <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy(col.key)">
                                {{ col.label }}
                                <component :is="sortIcon(col.key)" class="size-3.5" :class="sortKey === col.key ? 'text-brand-500' : 'text-gray-400'" />
                            </button>
                        </th>
                        <th class="w-20 px-4 py-3 text-right font-medium">สัดส่วน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in sorted" :key="row.user_id ?? 'none'" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <Link
                                v-if="row.user_id !== null"
                                :href="userHref(row)"
                                class="text-brand-600 hover:text-brand-700"
                                title="ดูภาพรวมเฉพาะผู้ใช้งานนี้"
                            >
                                {{ userLabel(row) }}
                            </Link>
                            <span v-else class="text-gray-500">{{ userLabel(row) }}</span>
                            <p v-if="row.email && !compact" class="text-xs text-gray-500">{{ row.email }}</p>
                        </td>
                        <td v-if="!compact" class="px-4 py-3 text-gray-600">{{ row.group ?? '-' }}</td>
                        <td v-for="col in columns" :key="col.key" class="px-4 py-3 text-right whitespace-nowrap text-gray-700 tabular-nums">
                            {{ cell(row, col.key) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ percent(row.views, total) }}</td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + (compact ? 2 : 3)" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
