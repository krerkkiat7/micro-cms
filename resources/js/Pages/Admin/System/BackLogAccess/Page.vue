<script setup lang="ts">
import DurationCards from '@/Components/Admin/BackLogAccess/DurationCards.vue';
import PageStatsTable from '@/Components/Admin/BackLogAccess/PageStatsTable.vue';
import StatsShell from '@/Components/Admin/BackLogAccess/StatsShell.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import type { DurationSummary, PageStatRow } from '@/utils/backLogAccessReport';
import { SERIES_COLORS } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportSummary } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[];
    durationCap: number;
    summary: ReportSummary;
    duration: DurationSummary;
    pages: PageStatRow[];
}>();

const TOP = 15;

const label = (row: PageStatRow) => {
    const text = row.title ?? 'ไม่ระบุชื่อหน้า';
    return text.length > 36 ? `${text.slice(0, 36)}…` : text;
};

const byViews = computed(() => props.pages.slice(0, TOP));
// เวลาเฉลี่ยนับเฉพาะหน้าที่เปิดอย่างน้อย 3 ครั้ง กันค่าจากการเปิดครั้งเดียวดันอันดับ
const byAvg = computed(() => props.pages.filter((p) => p.views >= 3).sort((a, b) => b.avg_seconds - a.avg_seconds).slice(0, TOP));

const viewsDatasets = computed(() => [
    { label: 'จำนวนการเข้าหน้าจอ', data: byViews.value.map((p) => p.views), color: SERIES_COLORS[0] },
]);
const avgDatasets = computed(() => [
    { label: 'เวลาเฉลี่ย (นาที)', data: byAvg.value.map((p) => Math.round(p.avg_seconds / 6) / 10), color: SERIES_COLORS[2] },
]);
</script>

<template>
    <StatsShell tab="page" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <ReportStatCards :summary="summary" :show-items="!filters.user_id" />

        <DurationCards :duration="duration" :cap="durationCap" />

        <div v-if="pages.length > 0" class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">หน้าจอที่เปิดบ่อยที่สุด {{ byViews.length }} อันดับ</h3>
                <ViewTrendChart :labels="byViews.map(label)" :datasets="viewsDatasets" horizontal :height="Math.max(200, byViews.length * 30 + 40)" />
            </section>
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">หน้าจอที่ใช้เวลาเฉลี่ยนานที่สุด (นาที)</h3>
                <ViewTrendChart
                    v-if="byAvg.length > 0"
                    :labels="byAvg.map(label)"
                    :datasets="avgDatasets"
                    horizontal
                    :height="Math.max(200, byAvg.length * 30 + 40)"
                />
                <p v-else class="py-10 text-center text-sm text-gray-500">ยังไม่มีหน้าจอที่เปิดอย่างน้อย 3 ครั้ง</p>
            </section>
        </div>

        <PageStatsTable :rows="pages" :total="summary.views" />
        <p class="text-xs text-gray-500">แสดงสูงสุด 100 หน้าจอ (ส่งออก CSV ได้ครบ) — จัดกลุ่มตามชื่อหน้าที่บันทึกไว้</p>
    </StatsShell>
</template>
