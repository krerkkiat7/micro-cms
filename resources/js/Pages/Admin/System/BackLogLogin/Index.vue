<script setup lang="ts">
import DetailDialog from '@/Components/Admin/DetailDialog.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import type { Paginated } from '@/types';
import { formatDateTime } from '@/utils/date';
import { Head, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, RotateCcw, Search } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Row {
    id: number;
    name: string | null;
    username: string | null;
    log_type: string | null;
    result: string | null;
    note: string | null;
    remote_ip: string | null;
    created_at: string | null;
}

const props = defineProps<{
    logs: Paginated<Row>;
    filters: {
        q: string | null;
        log_type: string | null;
        result: string | null;
        date_from: string | null;
        date_to: string | null;
        per_page: number;
    };
    sort: string;
    direction: 'asc' | 'desc';
    perPageOptions: number[];
}>();

const form = reactive({
    q: props.filters.q ?? '',
    log_type: props.filters.log_type ?? '',
    result: props.filters.result ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    per_page: String(props.filters.per_page),
});

const columns: { key: string; label: string; sortable: boolean }[] = [
    { key: 'name', label: 'ชื่อ - นามสกุล', sortable: true },
    { key: 'username', label: 'Username', sortable: true },
    { key: 'log_type', label: 'ประเภท', sortable: true },
    { key: 'result', label: 'ผลลัพธ์', sortable: true },
    { key: 'remote_ip', label: 'IP Address', sortable: true },
    { key: 'created_at', label: 'วันเวลา', sortable: true },
];

const TYPE_LABEL: Record<string, string> = {
    login: 'เข้าสู่ระบบ',
    logout: 'ออกจากระบบ',
};

const RESULT_LABEL: Record<string, string> = {
    success: 'สำเร็จ',
    fail: 'ไม่สำเร็จ',
    block: 'ถูกบล็อก',
};

const RESULT_CLASS: Record<string, string> = {
    success: 'bg-green-100 text-green-700',
    fail: 'bg-red-100 text-red-700',
    block: 'bg-amber-100 text-amber-700',
};

function typeLabel(value: string | null): string {
    return value ? (TYPE_LABEL[value] ?? value) : '-';
}

function resultLabel(value: string | null): string {
    return value ? (RESULT_LABEL[value] ?? value) : '-';
}

function resultClass(value: string | null): string {
    return (value && RESULT_CLASS[value]) || 'bg-gray-100 text-gray-600';
}

function visit(extra: Record<string, unknown> = {}) {
    router.get(
        route('admin.system.backlog.login.index'),
        {
            q: form.q !== '' ? form.q : undefined,
            log_type: form.log_type !== '' ? form.log_type : undefined,
            result: form.result !== '' ? form.result : undefined,
            date_from: form.date_from !== '' ? form.date_from : undefined,
            date_to: form.date_to !== '' ? form.date_to : undefined,
            per_page: form.per_page,
            sort: props.sort,
            direction: props.direction,
            ...extra,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function search() {
    visit({ page: undefined });
}

function resetFilters() {
    form.q = '';
    form.log_type = '';
    form.result = '';
    form.date_from = '';
    form.date_to = '';
    form.per_page = String(props.perPageOptions[0]);
    router.get(
        route('admin.system.backlog.login.index'),
        {},
        { preserveScroll: true, replace: true },
    );
}

function sortBy(column: string) {
    const direction =
        props.sort === column && props.direction === 'asc' ? 'desc' : 'asc';
    visit({ sort: column, direction, page: undefined });
}

function sortIcon(column: string) {
    if (props.sort !== column) return ArrowUpDown;
    return props.direction === 'asc' ? ArrowUp : ArrowDown;
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ประวัติการเข้าสู่ระบบหลังบ้าน' },
];

// --- dialog รายละเอียด ---
const selected = ref<Row | null>(null);

function openDetail(row: Row) {
    selected.value = row;
}

function dash(value: string | null | undefined): string {
    return value !== null && value !== undefined && value !== '' ? value : '-';
}

const detailRows = computed<{ label: string; value: string }[]>(() => {
    const row = selected.value;
    if (!row) return [];

    return [
        { label: 'ชื่อ - นามสกุล', value: dash(row.name) },
        { label: 'Username', value: dash(row.username) },
        { label: 'ประเภท', value: typeLabel(row.log_type) },
        { label: 'ผลลัพธ์', value: resultLabel(row.result) },
        { label: 'หมายเหตุ', value: dash(row.note) },
        { label: 'IP Address', value: dash(row.remote_ip) },
        { label: 'วันเวลาที่บันทึก', value: formatDateTime(row.created_at) },
    ];
});
</script>

<template>
    <Head title="ประวัติการเข้าสู่ระบบหลังบ้าน" />

    <AdminLayout>
        <template #header>
            <PageHeader
                title="ประวัติการเข้าสู่ระบบหลังบ้าน"
                :breadcrumbs="breadcrumbs"
            />
        </template>

        <div class="space-y-4">
            <!-- ตัวกรอง -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs"
                @submit.prevent="search"
            >
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <TextInput
                            v-model="form.q"
                            type="text"
                            placeholder="ค้นหาจาก Username, IP Address, หมายเหตุ"
                            @keyup.enter="search"
                        />
                    </div>
                    <SelectInput v-model="form.log_type">
                        <option value="">ทุกประเภท</option>
                        <option value="login">เข้าสู่ระบบ</option>
                        <option value="logout">ออกจากระบบ</option>
                    </SelectInput>
                    <SelectInput v-model="form.result">
                        <option value="">ทุกผลลัพธ์</option>
                        <option value="success">สำเร็จ</option>
                        <option value="fail">ไม่สำเร็จ</option>
                        <option value="block">ถูกบล็อก</option>
                    </SelectInput>
                    <label class="flex items-center gap-2">
                        <span class="whitespace-nowrap text-sm text-gray-500">
                            ตั้งแต่
                        </span>
                        <TextInput v-model="form.date_from" type="date" />
                    </label>
                    <label class="flex items-center gap-2">
                        <span class="whitespace-nowrap text-sm text-gray-500">
                            ถึง
                        </span>
                        <TextInput v-model="form.date_to" type="date" />
                    </label>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <PrimaryButton type="submit">
                        <Search class="mr-1.5 size-4" /> ค้นหา
                    </PrimaryButton>
                    <SecondaryButton type="button" @click="resetFilters">
                        <RotateCcw class="mr-1.5 size-4" /> เริ่มใหม่
                    </SecondaryButton>
                </div>
            </form>

            <!-- ตาราง -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500"
                        >
                            <tr>
                                <th
                                    v-for="col in columns"
                                    :key="col.key"
                                    class="px-4 py-3 font-medium"
                                >
                                    <button
                                        v-if="col.sortable"
                                        type="button"
                                        class="inline-flex items-center gap-1 transition-colors hover:text-gray-700"
                                        @click="sortBy(col.key)"
                                    >
                                        {{ col.label }}
                                        <component
                                            :is="sortIcon(col.key)"
                                            class="size-3.5"
                                            :class="
                                                sort === col.key
                                                    ? 'text-brand-500'
                                                    : 'text-gray-400'
                                            "
                                        />
                                    </button>
                                    <span v-else>{{ col.label }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in logs.data"
                                :key="row.id"
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                                @click="openDetail(row)"
                            >
                                <td class="px-4 py-3 text-gray-700">
                                    {{ row.name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ row.username ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ typeLabel(row.log_type) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="resultClass(row.result)"
                                    >
                                        {{ resultLabel(row.result) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ row.remote_ip ?? '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ formatDateTime(row.created_at) }}
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td
                                    :colspan="columns.length"
                                    class="px-4 py-10 text-center text-gray-500"
                                >
                                    ไม่พบประวัติตามเงื่อนไข
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ท้ายตาราง: จำนวนรายการอยู่ซ้าย, เลขหน้าอยู่กึ่งกลาง (ตกลงบรรทัดใหม่ถ้าที่ไม่พอ), จำนวนต่อหน้าชิดขวา -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="order-1 shrink-0 text-sm text-gray-500">
                    แสดง {{ logs.from ?? 0 }}–{{ logs.to ?? 0 }} จาก {{ logs.total }} รายการ
                </p>

                <Pagination
                    :current-page="logs.current_page"
                    :last-page="logs.last_page"
                    class="order-3 w-full sm:order-2 sm:w-auto sm:flex-1"
                    @navigate="(page) => visit({ page })"
                />

                <div class="order-2 w-20 shrink-0 sm:order-3">
                    <SelectInput v-model="form.per_page" class="text-sm" @update:model-value="search">
                        <option v-for="opt in perPageOptions" :key="opt" :value="String(opt)">{{ opt }}</option>
                    </SelectInput>
                </div>
            </div>
        </div>

        <DetailDialog
            :show="selected !== null"
            title="รายละเอียดการเข้าสู่ระบบ"
            @close="selected = null"
        >
            <dl class="divide-y divide-gray-100 text-sm">
                <div
                    v-for="item in detailRows"
                    :key="item.label"
                    class="grid grid-cols-3 gap-3 py-2.5"
                >
                    <dt class="text-gray-500">{{ item.label }}</dt>
                    <dd class="col-span-2 break-words text-gray-800">
                        {{ item.value }}
                    </dd>
                </div>
            </dl>
        </DetailDialog>
    </AdminLayout>
</template>
