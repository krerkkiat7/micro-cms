<script setup lang="ts">
import DurationCards from '@/Components/Admin/BackLogAccess/DurationCards.vue';
import StatsShell from '@/Components/Admin/BackLogAccess/StatsShell.vue';
import UserStatsTable from '@/Components/Admin/BackLogAccess/UserStatsTable.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { userLabel } from '@/utils/backLogAccessReport';
import type { DurationSummary, UserStatRow } from '@/utils/backLogAccessReport';
import { SERIES_COLORS } from '@/utils/report';
import type { CategoryOption, ReportFilters, ReportSummary } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[];
    durationCap: number;
    summary: ReportSummary;
    duration: DurationSummary;
    users: UserStatRow[];
}>();

const TOP = 15;

const shorten = (text: string) => (text.length > 32 ? `${text.slice(0, 32)}…` : text);

// กราฟแยก 2 ตัว (จำนวนครั้ง / เวลา) — หน่วยต่างกันจึงไม่รวมแกนเดียว
const byViews = computed(() => props.users.slice(0, TOP));
const byTime = computed(() => [...props.users].sort((a, b) => b.total_seconds - a.total_seconds).slice(0, TOP));

const viewsDatasets = computed(() => [
    { label: 'จำนวนการเข้าหน้าจอ', data: byViews.value.map((u) => u.views), color: SERIES_COLORS[0] },
]);
const timeDatasets = computed(() => [
    { label: 'เวลาใช้งานรวม (นาที)', data: byTime.value.map((u) => Math.round(u.total_seconds / 6) / 10), color: SERIES_COLORS[2] },
]);
</script>

<template>
    <StatsShell tab="user" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <ReportStatCards :summary="summary" :show-items="!filters.user_id" />

        <DurationCards :duration="duration" :cap="durationCap" />

        <div v-if="users.length > 0" class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">จำนวนการเข้าหน้าจอสูงสุด {{ byViews.length }} อันดับ</h3>
                <ViewTrendChart
                    :labels="byViews.map((u) => shorten(userLabel(u)))"
                    :datasets="viewsDatasets"
                    horizontal
                    :height="Math.max(200, byViews.length * 30 + 40)"
                />
            </section>
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <h3 class="mb-2 text-sm font-semibold text-gray-800">เวลาใช้งานรวมสูงสุด {{ byTime.length }} อันดับ (นาที)</h3>
                <ViewTrendChart
                    :labels="byTime.map((u) => shorten(userLabel(u)))"
                    :datasets="timeDatasets"
                    horizontal
                    :height="Math.max(200, byTime.length * 30 + 40)"
                />
            </section>
        </div>

        <UserStatsTable :rows="users" :total="summary.views" :filters="filters" />
        <p class="text-xs text-gray-500">แสดงสูงสุด 100 คน (ส่งออก CSV ได้ครบ) — กดชื่อผู้ใช้งานเพื่อดูภาพรวมเฉพาะคนนั้น</p>
    </StatsShell>
</template>
