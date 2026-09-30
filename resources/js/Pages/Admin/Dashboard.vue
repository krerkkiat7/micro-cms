<script setup lang="ts">
import AttentionList from '@/Components/Admin/Dashboard/AttentionList.vue';
import ContentOverview from '@/Components/Admin/Dashboard/ContentOverview.vue';
import DashboardCard from '@/Components/Admin/Dashboard/DashboardCard.vue';
import KpiCards from '@/Components/Admin/Dashboard/KpiCards.vue';
import RecentActionList from '@/Components/Admin/Dashboard/RecentActionList.vue';
import RecentContactList from '@/Components/Admin/Dashboard/RecentContactList.vue';
import TopArticleList from '@/Components/Admin/Dashboard/TopArticleList.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import type { PageProps } from '@/types';
import { METRIC_COLORS } from '@/utils/dashboard';
import type { DashboardData, DashboardShortcut } from '@/utils/dashboard';
import { formatDate } from '@/utils/date';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

/**
 * Dashboard หลังบ้าน — ทุกส่วนมาจาก App\Support\Report\DashboardReport ซึ่งส่งเฉพาะส่วนที่ผู้ใช้มีสิทธิ์
 * (ไม่มีสิทธิ์ = null / array ว่าง) หน้านี้จึงแสดงตามข้อมูลที่ได้รับอย่างเดียว ไม่เช็กสิทธิ์ซ้ำ
 */
const props = defineProps<{
    shortcuts: DashboardShortcut[];
    dashboard: DashboardData;
}>();

const page = usePage<PageProps>();
const userName = computed(() => page.props.auth.user?.firstname || page.props.auth.user?.name || '');
const today = formatDate(new Date().toISOString());

const trendDatasets = computed(() =>
    (props.dashboard.trend?.datasets ?? []).map((ds) => ({ label: ds.label, data: ds.data, color: METRIC_COLORS[ds.key] ?? '#9ca3af' })),
);

// การ์ดคู่ — มีข้างเดียวให้เต็มแถว
const firstRow = computed(() => [props.dashboard.topArticles, props.dashboard.recentContacts].filter(Boolean).length);
const secondRow = computed(() => [props.dashboard.content.length > 0, props.dashboard.recentActions].filter(Boolean).length);

const isEmpty = computed(
    () =>
        props.dashboard.attention.length === 0 &&
        props.dashboard.kpis.length === 0 &&
        !props.dashboard.trend &&
        firstRow.value === 0 &&
        secondRow.value === 0,
);
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">สวัสดี{{ userName ? `, ${userName}` : '' }}</h1>
                    <p class="mt-1 text-sm text-gray-500">ภาพรวมของระบบ ณ วันที่ {{ today }}</p>
                </div>
                <div v-if="shortcuts.length" class="flex flex-wrap gap-2">
                    <Link
                        v-for="shortcut in shortcuts"
                        :key="shortcut.key"
                        :href="shortcut.href"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-xs hover:border-brand-300 hover:text-brand-600"
                    >
                        <Plus class="size-4" aria-hidden="true" />
                        {{ shortcut.label }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <AttentionList v-if="dashboard.attention.length" :items="dashboard.attention" />

            <section v-if="dashboard.kpis.length" aria-labelledby="kpi-title">
                <h2 id="kpi-title" class="mb-2 text-sm font-semibold text-gray-600">7 วันล่าสุด <span class="font-normal text-gray-400">(เทียบกับ 7 วันก่อนหน้า)</span></h2>
                <KpiCards :items="dashboard.kpis" />
            </section>

            <DashboardCard v-if="dashboard.trend" title="แนวโน้ม 30 วันล่าสุด" subtitle="ยอดรายวัน — ผู้เข้าชมเว็บไซต์นับเป็น session ไม่ซ้ำ">
                <ViewTrendChart :labels="dashboard.trend.labels" :datasets="trendDatasets" type="line" :height="280" />
            </DashboardCard>

            <div v-if="firstRow" class="grid gap-6" :class="firstRow === 2 ? 'lg:grid-cols-2' : ''">
                <DashboardCard v-if="dashboard.topArticles" title="บทความยอดนิยม" subtitle="ยอดเข้าชม 7 วันล่าสุด" :href="dashboard.topArticles.href" link-label="ดูอันดับทั้งหมด">
                    <TopArticleList :items="dashboard.topArticles.items" />
                </DashboardCard>
                <DashboardCard v-if="dashboard.recentContacts" title="ข้อความติดต่อล่าสุด" :href="dashboard.recentContacts.href">
                    <RecentContactList :items="dashboard.recentContacts.items" />
                </DashboardCard>
            </div>

            <div v-if="secondRow" class="grid gap-6" :class="secondRow === 2 ? 'lg:grid-cols-2' : ''">
                <DashboardCard v-if="dashboard.content.length" title="ภาพรวมเนื้อหา" subtitle="จำนวนที่เผยแพร่อยู่ตอนนี้ / ทั้งหมด">
                    <ContentOverview :items="dashboard.content" />
                </DashboardCard>
                <DashboardCard v-if="dashboard.recentActions" title="กิจกรรมล่าสุดในหลังบ้าน" :href="dashboard.recentActions.href">
                    <RecentActionList :items="dashboard.recentActions.items" />
                </DashboardCard>
            </div>

            <div v-if="isEmpty" class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <LayoutDashboard class="mx-auto size-10 text-gray-300" aria-hidden="true" />
                <p class="mt-3 text-sm font-medium text-gray-700">ยังไม่มีข้อมูลที่คุณมีสิทธิ์ดู</p>
                <p class="mt-1 text-xs text-gray-500">เลือกเมนูทางซ้ายเพื่อเริ่มใช้งาน หรือติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์เพิ่มเติม</p>
            </div>
        </div>
    </AdminLayout>
</template>
