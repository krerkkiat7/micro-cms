<script setup lang="ts">
import ActionTypeTable from '@/Components/Admin/LogStats/ActionTypeTable.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import { formatDateTime } from '@/utils/date';
import type { ActionCounts, ActionModuleRow, ActionRecordRow } from '@/utils/logStats';
import { formatNumber } from '@/utils/report';
import type { CategoryOption, ReportFilters } from '@/utils/report';

defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: ActionCounts;
    modules: ActionModuleRow[];
    records: ActionRecordRow[];
}>();
</script>

<template>
    <StatsShell log="backAction" tab="module" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">การกระทำรายโมดูล</h3>
            <ActionTypeTable
                :rows="modules"
                name-label="โมดูล (module_code)"
                :extra="[{ key: 'users', label: 'ผู้ใช้งาน' }]"
                :row-key="(r) => String(r.module ?? 'none')"
            >
                <template #name="{ row }">
                    <span class="font-mono text-gray-800">{{ row.module ?? '-' }}</span>
                </template>
            </ActionTypeTable>
        </section>

        <section>
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ข้อมูลที่ถูกเปลี่ยนแปลงบ่อยที่สุด (เพิ่ม/แก้ไข/ลบ) 30 อันดับ</h3>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="w-56 px-4 py-3 font-medium">โมดูล</th>
                                <th class="w-24 px-4 py-3 text-right font-medium">ID</th>
                                <th class="px-4 py-3 font-medium">ข้อมูล (ชื่อล่าสุด)</th>
                                <th class="w-32 px-4 py-3 text-right font-medium">เปลี่ยนแปลง</th>
                                <th class="w-28 px-4 py-3 text-right font-medium">ผู้ใช้งาน</th>
                                <th class="w-56 px-4 py-3 font-medium">ล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in records" :key="`${row.module}:${row.ref_id}`" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-gray-700">{{ row.module ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ row.ref_id }}</td>
                                <td class="px-4 py-3 text-gray-800">{{ row.name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 tabular-nums">{{ formatNumber(row.changes) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 tabular-nums">{{ formatNumber(row.users) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ formatDateTime(row.last_at) }}</td>
                            </tr>
                            <tr v-if="records.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-gray-500">ไม่มีการเปลี่ยนแปลงข้อมูลในช่วงนี้</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </StatsShell>
</template>
