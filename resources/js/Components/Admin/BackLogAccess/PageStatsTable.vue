<script setup lang="ts">
import { formatDateTime } from '@/utils/date';
import type { PageStatRow } from '@/utils/backLogAccessReport';
import { formatDuration, formatNumber, percent } from '@/utils/report';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/**
 * ตารางสถิติรายหน้าจอ (ตามชื่อหน้า) — เรียงคอลัมน์ได้ในหน้าจอ; compact = ย่อคอลัมน์ (ใช้ในภาพรวม)
 */
const props = defineProps<{
    rows: PageStatRow[];
    total: number;
    compact?: boolean;
}>();

type SortKey = 'views' | 'users' | 'sessions' | 'avg_seconds' | 'total_seconds' | 'last_at';

const sortKey = ref<SortKey>('views');
const sortDir = ref<'asc' | 'desc'>('desc');

const columns = computed<{ key: SortKey; label: string }[]>(() => [
    { key: 'views', label: 'เข้าหน้าจอ' },
    { key: 'users', label: 'ผู้ใช้งาน' },
    ...(props.compact ? [] : [{ key: 'sessions' as const, label: 'เข้าระบบ' }]),
    { key: 'avg_seconds', label: 'เวลาเฉลี่ย' },
    ...(props.compact
        ? []
        : [
              { key: 'total_seconds' as const, label: 'เวลารวม' },
              { key: 'last_at' as const, label: 'เข้าล่าสุด' },
          ]),
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

function cell(row: PageStatRow, key: SortKey): string {
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
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" :class="compact ? 'min-w-[640px]' : 'min-w-[960px]'">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">หน้าจอ</th>
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
                    <tr v-for="row in sorted" :key="row.title ?? 'none'" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800">{{ row.title ?? 'ไม่ระบุชื่อหน้า' }}</td>
                        <td v-for="col in columns" :key="col.key" class="px-4 py-3 text-right whitespace-nowrap text-gray-700 tabular-nums">
                            {{ cell(row, col.key) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-500 tabular-nums">{{ percent(row.views, total) }}</td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + 2" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
