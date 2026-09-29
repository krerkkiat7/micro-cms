<script setup lang="ts">
import DurationCards from '@/Components/Admin/LogStats/DurationCards.vue';
import PageStatsTable from '@/Components/Admin/LogStats/PageStatsTable.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import type { DurationSummary, LandingRow, PageStatRow } from '@/utils/logStats';
import { SERIES_COLORS } from '@/utils/report';
import type { ReportFilters, ReportSummary } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    durationCap: number;
    summary: ReportSummary;
    duration: DurationSummary;
    pages: PageStatRow[];
    landing: LandingRow[];
}>();

const TOP = 15;

const label = (title: string | null) => {
    const text = title ?? 'ไม่ระบุชื่อหน้า';
    return text.length > 36 ? `${text.slice(0, 36)}…` : text;
};

const byViews = computed(() => props.pages.slice(0, TOP));
// เวลาเฉลี่ยนับเฉพาะหน้าที่เปิดอย่างน้อย 3 ครั้ง กันค่าจากการเปิดครั้งเดียวดันอันดับ
const byAvg = computed(() => props.pages.filter((p) => p.views >= 3).sort((a, b) => b.avg_seconds - a.avg_seconds).slice(0, TOP));

const landingItems = computed(() => props.landing.map((l) => ({ label: l.title ?? 'ไม่ระบุชื่อหน้า', views: l.sessions })));
</script>

<template>
    <StatsShell log="frontAccess" tab="page" :filters="filters" :show-period="false" :show-chart-type="false">
        <ReportStatCards :summary="summary" />

        <DurationCards :duration="duration" :cap="durationCap" />

        <div v-if="pages.length > 0" class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">หน้าที่เข้าชมมากที่สุด {{ byViews.length }} อันดับ</h3>
                <ViewTrendChart
                    :labels="byViews.map((p) => label(p.title))"
                    :datasets="[{ label: 'จำนวนการเปิดหน้า', data: byViews.map((p) => p.views), color: SERIES_COLORS[0] }]"
                    horizontal
                    :height="Math.max(200, byViews.length * 30 + 40)"
                />
            </section>
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">หน้าที่ผู้เข้าชมอยู่นานที่สุด (นาที)</h3>
                <ViewTrendChart
                    v-if="byAvg.length > 0"
                    :labels="byAvg.map((p) => label(p.title))"
                    :datasets="[{ label: 'เวลาเฉลี่ย (นาที)', data: byAvg.map((p) => Math.round(p.avg_seconds / 6) / 10), color: SERIES_COLORS[2] }]"
                    horizontal
                    :height="Math.max(200, byAvg.length * 30 + 40)"
                />
                <p v-else class="py-10 text-center text-sm text-gray-500">ยังไม่มีหน้าที่เปิดอย่างน้อย 3 ครั้ง</p>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="xl:col-span-2">
                <PageStatsTable :rows="pages" :total="summary.views" page-label="หน้า" :show-users="false" />
                <p class="mt-2 text-xs text-gray-500">แสดงสูงสุด 100 หน้า (ส่งออก CSV ได้ครบ) — จัดกลุ่มตามชื่อหน้าที่บันทึกไว้</p>
            </div>
            <BreakdownList title="หน้าแรกที่เข้าชม (landing page)" :items="landingItems" />
        </div>
    </StatsShell>
</template>
