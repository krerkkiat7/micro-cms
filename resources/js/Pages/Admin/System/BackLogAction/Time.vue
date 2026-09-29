<script setup lang="ts">
import StatTiles from '@/Components/Admin/LogStats/StatTiles.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import WeekHourHeatmap from '@/Components/Admin/Report/WeekHourHeatmap.vue';
import { officeHoursSplit } from '@/utils/logStats';
import type { ActionCounts } from '@/utils/logStats';
import { formatNumber, percent } from '@/utils/report';
import type { CategoryOption, ReportFilters } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: ActionCounts;
    heatmap: number[][];
    changeHeatmap: number[][];
}>();

const all = computed(() => officeHoursSplit(props.heatmap));
const changes = computed(() => officeHoursSplit(props.changeHeatmap));

const tiles = computed(() => [
    { label: 'การกระทำในเวลาทำการ', value: formatNumber(all.value.work), hint: `${percent(all.value.work, all.value.total)} (จ.–ศ. 08:00–17:59)` },
    { label: 'การกระทำนอกเวลา/วันหยุด', value: formatNumber(all.value.off + all.value.weekend), hint: percent(all.value.off + all.value.weekend, all.value.total) },
    { label: 'เปลี่ยนแปลงข้อมูลในเวลาทำการ', value: formatNumber(changes.value.work), hint: percent(changes.value.work, changes.value.total) },
    {
        label: 'เปลี่ยนแปลงข้อมูลนอกเวลา/วันหยุด',
        value: formatNumber(changes.value.off + changes.value.weekend),
        hint: `${percent(changes.value.off + changes.value.weekend, changes.value.total)} — ควรตรวจสอบถ้าไม่ปกติ`,
        tone: changes.value.off + changes.value.weekend > 0 ? ('warning' as const) : undefined,
    },
]);
</script>

<template>
    <StatsShell log="backAction" tab="time" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <StatTiles :items="tiles" />

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">การกระทำทั้งหมด (วัน × ชั่วโมง)</h3>
            <WeekHourHeatmap :grid="heatmap" />
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">เฉพาะการเปลี่ยนแปลงข้อมูล — เพิ่ม/แก้ไข/ลบ (วัน × ชั่วโมง)</h3>
            <WeekHourHeatmap :grid="changeHeatmap" />
        </section>
    </StatsShell>
</template>
