<script setup lang="ts">
import StatTiles from '@/Components/Admin/LogStats/StatTiles.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import WeekHourHeatmap from '@/Components/Admin/Report/WeekHourHeatmap.vue';
import { officeHoursSplit } from '@/utils/logStats';
import type { LoginCounts } from '@/utils/logStats';
import { formatNumber, percent } from '@/utils/report';
import type { CategoryOption, ReportFilters } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: LoginCounts;
    successHeatmap: number[][];
    failedHeatmap: number[][];
}>();

const success = computed(() => officeHoursSplit(props.successHeatmap));
const failed = computed(() => officeHoursSplit(props.failedHeatmap));

const tiles = computed(() => [
    { label: 'เข้าสู่ระบบสำเร็จในเวลาทำการ', value: formatNumber(success.value.work), hint: `${percent(success.value.work, success.value.total)} (จ.–ศ. 08:00–17:59)` },
    {
        label: 'เข้าสู่ระบบสำเร็จนอกเวลา/วันหยุด',
        value: formatNumber(success.value.off + success.value.weekend),
        hint: `${percent(success.value.off + success.value.weekend, success.value.total)} — ควรตรวจสอบถ้าไม่ปกติ`,
        tone: success.value.off + success.value.weekend > 0 ? ('warning' as const) : undefined,
    },
    { label: 'ผิดพลาดในเวลาทำการ', value: formatNumber(failed.value.work), hint: percent(failed.value.work, failed.value.total) },
    {
        label: 'ผิดพลาดนอกเวลา/วันหยุด',
        value: formatNumber(failed.value.off + failed.value.weekend),
        hint: percent(failed.value.off + failed.value.weekend, failed.value.total),
        tone: failed.value.off + failed.value.weekend > 0 ? ('danger' as const) : undefined,
    },
]);
</script>

<template>
    <StatsShell log="backLogin" tab="time" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <StatTiles :items="tiles" />

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">เข้าสู่ระบบสำเร็จ (วัน × ชั่วโมง)</h3>
            <WeekHourHeatmap :grid="successHeatmap" />
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">เข้าสู่ระบบไม่สำเร็จ / ถูกบล็อก (วัน × ชั่วโมง)</h3>
            <WeekHourHeatmap :grid="failedHeatmap" />
        </section>
    </StatsShell>
</template>
