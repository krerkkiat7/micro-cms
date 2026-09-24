<script setup lang="ts">
import DetailDialog from '@/Components/Admin/DetailDialog.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
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
    uri_string: string | null;
    title_name: string | null;
    remote_ip: string | null;
    created_at: string | null;
    last_visited: string | null;
    session_id: string | null;
    browser: string | null;
    browser_version: string | null;
    platform: string | null;
    device_type: string | null;
    mobile: string | null;
    robot: string | null;
    referrer: string | null;
    agent: string | null;
    accept_lang: string | null;
    accept_charset: string | null;
    geo_ip: string | null;
    geo_ip_city: string | null;
}

const props = defineProps<{
    logs: Paginated<Row>;
    filters: {
        q: string | null;
        visitor: 'human' | 'robot' | null;
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
    visitor: props.filters.visitor ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    per_page: String(props.filters.per_page),
});

// ผู้เข้าชม: ทั้งหมด / บุคคล / บอท (crawler — แยกดูได้เพราะหน้าบ้านมีบอทเข้ามาจำนวนมาก)
const visitorOptions = [
    { value: '', label: 'ผู้เข้าชมทั้งหมด' },
    { value: 'human', label: 'เฉพาะบุคคล' },
    { value: 'robot', label: 'เฉพาะบอท' },
];

const perPageSelectOptions = computed(() => props.perPageOptions.map((n) => ({ value: String(n), label: String(n) })));

const columns: { key: string; label: string; sortable: boolean }[] = [
    { key: 'name', label: 'ชื่อ - นามสกุล', sortable: true },
    { key: 'uri_string', label: 'URL', sortable: true },
    { key: 'title_name', label: 'ชื่อหน้า', sortable: true },
    { key: 'remote_ip', label: 'IP Address', sortable: true },
    { key: 'device_type', label: 'อุปกรณ์', sortable: true },
    { key: 'created_at', label: 'วันเวลาที่เข้าชม', sortable: true },
    { key: 'last_visited', label: 'วันเวลาที่ออกจากหน้า', sortable: true },
];

function visit(extra: Record<string, unknown> = {}) {
    router.get(
        route('admin.system.frontlog.access.index'),
        {
            q: form.q !== '' ? form.q : undefined,
            visitor: form.visitor !== '' ? form.visitor : undefined,
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
    form.visitor = '';
    form.date_from = '';
    form.date_to = '';
    form.per_page = String(props.perPageOptions[0]);
    router.get(
        route('admin.system.frontlog.access.index'),
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
    { label: 'ประวัติการใช้งานหน้าบ้าน' },
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

    const browser = [row.browser, row.browser_version].filter(Boolean).join(' ');
    const device = [row.device_type, row.mobile].filter(Boolean).join(' · ');
    const geo = [row.geo_ip_city, row.geo_ip].filter(Boolean).join(', ');

    return [
        { label: 'ชื่อ - นามสกุล', value: row.name ?? 'ผู้เยี่ยมชม (ไม่ได้เข้าสู่ระบบ)' },
        { label: 'URL', value: dash(row.uri_string) },
        { label: 'ชื่อหน้า', value: dash(row.title_name) },
        { label: 'IP Address', value: dash(row.remote_ip) },
        { label: 'วันเวลาที่เข้าชม', value: formatDateTime(row.created_at) },
        { label: 'วันเวลาที่ออกจากหน้า', value: formatDateTime(row.last_visited) },
        { label: 'เบราว์เซอร์', value: dash(browser) },
        { label: 'ระบบปฏิบัติการ', value: dash(row.platform) },
        { label: 'อุปกรณ์', value: dash(device) },
        { label: 'Bot', value: dash(row.robot) },
        { label: 'Session', value: dash(row.session_id) },
        { label: 'ตำแหน่ง (GeoIP)', value: dash(geo) },
        { label: 'อ้างอิงจาก (Referrer)', value: dash(row.referrer) },
        { label: 'Accept-Language', value: dash(row.accept_lang) },
        { label: 'Accept-Charset', value: dash(row.accept_charset) },
        { label: 'User-Agent', value: dash(row.agent) },
    ];
});
</script>

<template>
    <Head title="ประวัติการใช้งานหน้าบ้าน" />

    <AdminLayout>
        <template #header>
            <PageHeader
                title="ประวัติการใช้งานหน้าบ้าน"
                :breadcrumbs="breadcrumbs"
            />
        </template>

        <div class="space-y-4">
            <!-- ตัวกรอง -->
            <form
                class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs"
                @submit.prevent="search"
            >
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <TextInput
                            v-model="form.q"
                            type="text"
                            placeholder="ค้นหาจาก URL, ชื่อหน้า, IP Address"
                            @keyup.enter="search"
                        />
                    </div>
                    <SearchableSelect v-model="form.visitor" :options="visitorOptions" />
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
                                    {{ row.name ?? 'ผู้เยี่ยมชม' }}
                                </td>
                                <td
                                    class="max-w-[22rem] truncate px-4 py-3 text-gray-600"
                                    :title="row.uri_string ?? ''"
                                >
                                    {{ row.uri_string ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ row.title_name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ row.remote_ip ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <span v-if="row.robot" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">บอท · {{ row.robot }}</span>
                                    <span v-else>{{ row.device_type ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ formatDateTime(row.created_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ formatDateTime(row.last_visited) }}
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
                    <SearchableSelect v-model="form.per_page" :options="perPageSelectOptions" class="text-sm" @update:model-value="search" />
                </div>
            </div>
        </div>

        <DetailDialog
            :show="selected !== null"
            title="รายละเอียดการเข้าชม"
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
