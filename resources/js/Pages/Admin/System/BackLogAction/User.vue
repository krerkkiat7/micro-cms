<script setup lang="ts">
import ActionTypeTable from '@/Components/Admin/LogStats/ActionTypeTable.vue';
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import ViewTrendChart from '@/Components/Admin/Report/ViewTrendChart.vue';
import { userLabel } from '@/utils/logStats';
import type { ActionCounts, ActionUserRow } from '@/utils/logStats';
import { SERIES_COLORS, filterQuery } from '@/utils/report';
import type { CategoryOption, ReportFilters } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    userOptions: CategoryOption[] | null;
    counts: ActionCounts;
    users: ActionUserRow[];
}>();

const TOP = 15;

const shorten = (text: string) => (text.length > 32 ? `${text.slice(0, 32)}…` : text);

// เรียงตามจำนวน "เปลี่ยนแปลงข้อมูล" (เพิ่ม/แก้ไข/ลบ) — เห็นว่าใครทำงานกับข้อมูลมากที่สุด
const top = computed(() =>
    [...props.users].sort((a, b) => b.create + b.update + b.delete - (a.create + a.update + a.delete)).slice(0, TOP),
);

// แท่งแยก 3 ชุดในแกนเดียว (หน่วยเดียวกัน = ครั้ง), สีตามประเภทลำดับคงที่
const datasets = computed(() => [
    { label: 'เพิ่ม', data: top.value.map((u) => u.create), color: SERIES_COLORS[0] },
    { label: 'แก้ไข', data: top.value.map((u) => u.update), color: SERIES_COLORS[1] },
    { label: 'ลบ', data: top.value.map((u) => u.delete), color: SERIES_COLORS[2] },
]);

const userHref = (row: ActionUserRow) =>
    route('admin.system.backlog.action.overview', { ...filterQuery(props.filters), user_id: String(row.user_id) });
</script>

<template>
    <StatsShell log="backAction" tab="user" :filters="filters" :user-options="userOptions" :show-period="false" :show-chart-type="false">
        <section v-if="top.length > 0" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">ผู้ที่เปลี่ยนแปลงข้อมูลมากที่สุด {{ top.length }} อันดับ</h3>
            <ViewTrendChart
                :labels="top.map((u) => shorten(userLabel(u)))"
                :datasets="datasets"
                horizontal
                :height="Math.max(220, top.length * 36 + 60)"
            />
        </section>

        <ActionTypeTable
            :rows="users"
            name-label="ผู้ใช้งาน"
            :extra="[
                { key: 'modules', label: 'โมดูล' },
                { key: 'active_days', label: 'วันที่มีการกระทำ' },
            ]"
            :row-key="(r) => String(r.user_id ?? 'none')"
        >
            <template #name="{ row }">
                <Link v-if="row.user_id !== null" :href="userHref(row)" class="text-brand-600 hover:text-brand-700" title="ดูภาพรวมเฉพาะผู้ใช้งานนี้">
                    {{ userLabel(row) }}
                </Link>
                <span v-else class="text-gray-500">{{ userLabel(row) }}</span>
                <p v-if="row.group" class="text-xs text-gray-500">{{ row.group }}</p>
            </template>
        </ActionTypeTable>
        <p class="text-xs text-gray-500">แสดงสูงสุด 100 คน (ส่งออก CSV ได้ครบ) — กดชื่อผู้ใช้งานเพื่อดูภาพรวมเฉพาะคนนั้น</p>
    </StatsShell>
</template>
