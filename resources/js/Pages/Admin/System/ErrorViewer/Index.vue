<script setup lang="ts">
import AvailableDatePicker from '@/Components/Admin/ErrorViewer/AvailableDatePicker.vue';
import ErrorLogDetailDialog from '@/Components/Admin/ErrorViewer/ErrorLogDetailDialog.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { formatDateTime } from '@/utils/date';
import { REFERENCE_PATTERN, SIDE_LABEL, shortClass } from '@/utils/errorViewer';
import type { ErrorDetail, ErrorGroup, ErrorRowPage, ErrorSide } from '@/utils/errorViewer';
import { formatNumber } from '@/utils/report';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { AlertTriangle, Bug, RotateCcw, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

/**
 * ตรวจสอบ Error — รายการ error รายวันจากไฟล์ json-error-{front,admin}-*.log (Admin\System\ErrorViewerController)
 * แท็บหน้าบ้าน/หลังบ้าน, datepicker กดได้เฉพาะวันที่มีไฟล์, ค้นด้วยรหัส ERR-XXXXXXXX (ข้ามฝั่ง → เปิดรายละเอียดทันที) หรือข้อความ (กรองวันนั้น)
 */
const props = defineProps<{
    side: ErrorSide;
    dates: string[];
    date: string | null;
    filters: { q: string };
    rows: ErrorRowPage;
    summary: ErrorGroup[];
    truncated: boolean;
    maxEntries: number;
    counts: Record<ErrorSide, number>;
}>();

const q = ref(props.filters.q);
const date = ref(props.date);

watch(
    () => props.date,
    (value) => (date.value = value),
);

const tabs = computed(() =>
    (['front', 'admin'] as ErrorSide[]).map((side) => ({
        label: `${SIDE_LABEL[side]} (${props.counts[side]} วัน)`,
        href: route('admin.system.errorviewer.index', { side }),
        active: props.side === side,
    })),
);

const breadcrumbs = [{ label: 'Dashboard', href: route('admin.dashboard') }, { label: 'ตรวจสอบ Error' }];

function visit(extra: Record<string, unknown> = {}): void {
    router.get(
        route('admin.system.errorviewer.index'),
        { side: props.side, date: date.value ?? undefined, q: q.value.trim() || undefined, ...extra },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch(date, (value, old) => {
    if (value && value !== old && value !== props.date) visit({ page: undefined });
});

// ---- รายละเอียด
const detailOpen = ref(false);
const detail = ref<ErrorDetail | null>(null);
const detailLoading = ref(false);
const detailError = ref<string | null>(null);

async function openDetail(reference: string): Promise<void> {
    detailOpen.value = true;
    detail.value = null;
    detailError.value = null;
    detailLoading.value = true;

    try {
        const { data } = await axios.get<ErrorDetail>(route('admin.system.errorviewer.show', { reference }));
        detail.value = data;
    } catch (e) {
        detailError.value = axios.isAxiosError(e) && e.response?.data?.message ? e.response.data.message : 'โหลดรายละเอียดไม่สำเร็จ';
    } finally {
        detailLoading.value = false;
    }
}

/** รหัสอ้างอิง → เปิดรายละเอียดทันที (ค้นทุกฝั่งทุกวัน) · ข้อความอื่น → กรองรายการของวันที่เลือก */
function search(): void {
    const term = q.value.trim().toUpperCase();

    if (REFERENCE_PATTERN.test(term)) {
        q.value = '';
        openDetail(term);
        return;
    }

    visit({ page: undefined });
}

function reset(): void {
    q.value = '';
    visit({ q: undefined, page: undefined });
}
</script>

<template>
    <Head title="ตรวจสอบ Error" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ตรวจสอบ Error" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-5">
            <TabNav :tabs="tabs" />

            <!-- ตัวกรอง -->
            <form class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-xs" @submit.prevent="search">
                <div class="min-w-64 flex-1">
                    <TextInput v-model="q" type="text" class="w-full" placeholder="รหัสอ้างอิง (ERR-XXXXXXXX) หรือข้อความ / URL / ประเภท" />
                </div>
                <AvailableDatePicker v-model="date" :dates="dates" />
                <PrimaryButton type="submit"><Search class="mr-1.5 size-4" /> ค้นหา</PrimaryButton>
                <SecondaryButton v-if="filters.q" type="button" @click="reset"><RotateCcw class="mr-1.5 size-4" /> ล้างคำค้น</SecondaryButton>
            </form>

            <!-- ไม่มีไฟล์ของฝั่งนี้ -->
            <div v-if="dates.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <Bug class="mx-auto size-10 text-gray-300" aria-hidden="true" />
                <p class="mt-3 text-sm font-medium text-gray-700">ยังไม่มี error ของ{{ SIDE_LABEL[side] }}</p>
                <p class="mt-1 text-xs text-gray-500">เก็บย้อนหลังตามที่ตั้งค่าไว้ (LOG_ERROR_DAYS) — ค้นรหัสอ้างอิงของอีกฝั่งได้จากช่องค้นหา</p>
            </div>

            <template v-else>
                <p v-if="truncated" class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
                    <AlertTriangle class="size-4 shrink-0" aria-hidden="true" />
                    วันนี้มี error มากกว่า {{ formatNumber(maxEntries) }} รายการ — แสดงเฉพาะ {{ formatNumber(maxEntries) }} รายการแรกของวัน ค้นด้วยรหัสอ้างอิงเพื่อดูรายการที่เหลือ
                </p>

                <!-- กลุ่มที่เกิดซ้ำ -->
                <section v-if="summary.length" class="rounded-2xl border border-gray-200 bg-white shadow-xs">
                    <h2 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-800">กลุ่ม error ที่พบบ่อยของวันนี้</h2>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="group in summary" :key="`${group.class}|${group.file}`">
                            <button type="button" class="flex w-full items-center gap-4 px-5 py-2.5 text-left hover:bg-gray-50" @click="group.reference && openDetail(group.reference)">
                                <span class="w-12 shrink-0 text-right text-lg font-semibold text-red-600 tabular-nums">{{ formatNumber(group.count) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-800">{{ shortClass(group.class) }}</span>
                                    <span class="block truncate font-mono text-xs text-gray-500">{{ group.file ?? '-' }}</span>
                                </span>
                                <span class="shrink-0 text-xs text-gray-400">ล่าสุด {{ formatDateTime(group.last_at) }}</span>
                            </button>
                        </li>
                    </ul>
                </section>

                <!-- ตาราง -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[960px] text-left text-sm">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs tracking-wide text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3 font-medium">เวลา</th>
                                    <th class="px-4 py-3 font-medium">รหัสอ้างอิง</th>
                                    <th class="px-4 py-3 font-medium">ประเภท</th>
                                    <th class="px-4 py-3 font-medium">ข้อความ</th>
                                    <th class="px-4 py-3 font-medium">URL</th>
                                    <th class="px-4 py-3 font-medium">ผู้ใช้</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="row in rows.data"
                                    :key="row.reference + row.datetime"
                                    class="cursor-pointer align-top transition-colors hover:bg-gray-50"
                                    @click="openDetail(row.reference)"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600 tabular-nums">{{ formatDateTime(row.datetime) }}</td>
                                    <td class="px-4 py-3 font-mono text-xs font-semibold whitespace-nowrap text-gray-900">{{ row.reference || '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ row.class_short || '-' }}</td>
                                    <td class="max-w-md px-4 py-3 text-gray-700"><span class="line-clamp-2 break-words">{{ row.message || '-' }}</span></td>
                                    <td class="max-w-xs px-4 py-3 font-mono text-xs text-gray-500">
                                        <span class="line-clamp-2 break-all">{{ row.method ? `${row.method} ` : '' }}{{ row.url || '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ row.user_name ?? (row.user_id ? `id ${row.user_id}` : '-') }}</td>
                                </tr>
                                <tr v-if="rows.data.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">ไม่พบรายการที่ตรงกับคำค้นในวันที่เลือก</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3">
                        <p class="text-sm text-gray-500">
                            แสดง {{ formatNumber(rows.from) }}–{{ formatNumber(rows.to) }} จาก {{ formatNumber(rows.total) }} รายการ
                        </p>
                        <Pagination :current-page="rows.current_page" :last-page="rows.last_page" @navigate="(page) => visit({ page })" />
                    </div>
                </div>
            </template>
        </div>

        <ErrorLogDetailDialog :show="detailOpen" :detail="detail" :loading="detailLoading" :error="detailError" @close="detailOpen = false" />
    </AdminLayout>
</template>
