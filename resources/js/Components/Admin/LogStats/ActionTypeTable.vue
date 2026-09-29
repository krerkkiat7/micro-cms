<script setup lang="ts" generic="Row extends ActionTypeCounts & { total: number; last_at: string | null }">
import { formatDateTime } from '@/utils/date';
import type { ActionTypeCounts } from '@/utils/logStats';
import { formatNumber } from '@/utils/report';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/**
 * ตารางจำนวนการกระทำแยกประเภท (ใช้ทั้งรายผู้ใช้งานและรายโมดูล) — คอลัมน์แรกมาจาก slot `name`,
 * extra = คอลัมน์ตัวเลขเพิ่มเติม (เช่น จำนวนโมดูล / จำนวนผู้ใช้งาน), เรียงคอลัมน์ได้ในหน้าจอ
 */
const props = defineProps<{
    rows: Row[];
    nameLabel: string;
    extra: { key: string; label: string }[];
    rowKey: (row: Row) => string;
}>();

const sortKey = ref<string>('total');
const sortDir = ref<'asc' | 'desc'>('desc');

const columns = computed(() => [
    { key: 'total', label: 'ทั้งหมด' },
    { key: 'create', label: 'เพิ่ม' },
    { key: 'update', label: 'แก้ไข' },
    { key: 'delete', label: 'ลบ' },
    { key: 'view', label: 'ดู' },
    { key: 'other', label: 'อื่น ๆ' },
    ...props.extra,
    { key: 'last_at', label: 'ล่าสุด' },
]);

const sorted = computed(() =>
    [...props.rows].sort((a, b) => {
        const x = (field(a, sortKey.value) ?? '') as number | string;
        const y = (field(b, sortKey.value) ?? '') as number | string;
        const cmp = x < y ? -1 : x > y ? 1 : 0;
        return sortDir.value === 'asc' ? cmp : -cmp;
    }),
);

function sortBy(key: string) {
    sortDir.value = sortKey.value === key && sortDir.value === 'desc' ? 'asc' : 'desc';
    sortKey.value = key;
}

function sortIcon(key: string) {
    if (sortKey.value !== key) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

/** อ่านค่าคอลัมน์ตามชื่อ (คอลัมน์ extra มาจาก prop จึงเข้าถึงแบบ dynamic) */
function field(row: Row, key: string): unknown {
    return (row as unknown as Record<string, unknown>)[key];
}

function cell(row: Row, key: string): string {
    if (key === 'last_at') return formatDateTime(row.last_at);
    return formatNumber(Number(field(row, key) ?? 0));
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ nameLabel }}</th>
                        <th v-for="col in columns" :key="col.key" class="px-4 py-3 text-right font-medium whitespace-nowrap">
                            <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy(col.key)">
                                {{ col.label }}
                                <component :is="sortIcon(col.key)" class="size-3.5" :class="sortKey === col.key ? 'text-brand-500' : 'text-gray-400'" />
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in sorted" :key="rowKey(row)" class="hover:bg-gray-50">
                        <td class="px-4 py-3"><slot name="name" :row="row" /></td>
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            :class="col.key === 'delete' && row.delete > 0 ? 'font-medium text-red-700' : col.key === 'total' ? 'font-medium text-gray-900' : 'text-gray-700'"
                        >
                            {{ cell(row, col.key) }}
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + 1" class="px-4 py-10 text-center text-gray-500">ไม่มีข้อมูลในช่วงนี้</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
