<script setup lang="ts">
import ReportShell from '@/Components/Admin/Report/ReportShell.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Paginated } from '@/types';
import { formatDateTime } from '@/utils/date';
import { reportTerms } from '@/utils/report';
import type { ReportModule } from '@/utils/report';
import { Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, Download, RotateCcw, Search } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

interface Row {
    id: number;
    item_id: number;
    title: string | null;
    remote_ip: string | null;
    created_at: string | null;
}

const props = defineProps<{
    module: ReportModule;
    logs: Paginated<Row>;
    filters: {
        q: string | null;
        date_from: string | null;
        date_to: string | null;
        per_page: number;
    };
    sort: string;
    direction: 'asc' | 'desc';
    perPageOptions: number[];
    can: { view_item: boolean };
}>();

const form = reactive({
    q: props.filters.q ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    per_page: String(props.filters.per_page),
});

const perPageSelectOptions = computed(() => props.perPageOptions.map((n) => ({ value: String(n), label: String(n) })));

const terms = reportTerms(props.module.metric, props.module.item_label);

const columns: { key: string; label: string }[] = [
    { key: 'title', label: `ชื่อ${terms.item}` },
    { key: 'remote_ip', label: 'IP Address' },
    { key: 'created_at', label: `วันเวลาที่${terms.verb}` },
];

const indexRoute = `${props.module.route_prefix}.index`;

function query(extra: Record<string, unknown> = {}) {
    return {
        q: form.q !== '' ? form.q : undefined,
        date_from: form.date_from !== '' ? form.date_from : undefined,
        date_to: form.date_to !== '' ? form.date_to : undefined,
        per_page: form.per_page,
        sort: props.sort,
        direction: props.direction,
        ...extra,
    };
}

function visit(extra: Record<string, unknown> = {}) {
    router.get(route(indexRoute), query(extra), { preserveState: true, preserveScroll: true, replace: true });
}

function search() {
    visit({ page: undefined });
}

function resetFilters() {
    form.q = '';
    form.date_from = '';
    form.date_to = '';
    form.per_page = String(props.perPageOptions[0]);
    router.get(route(indexRoute), {}, { preserveScroll: true, replace: true });
}

function sortBy(column: string) {
    const direction = props.sort === column && props.direction === 'asc' ? 'desc' : 'asc';
    visit({ sort: column, direction, page: undefined });
}

function sortIcon(column: string) {
    if (props.sort !== column) return ArrowUpDown;
    return props.direction === 'asc' ? ArrowUp : ArrowDown;
}

// ส่งออกตามตัวกรองที่ใช้อยู่ (ค่าจาก server ไม่ใช่ค่าที่พิมพ์ค้างไว้ในฟอร์ม)
const exportHref = computed(() =>
    route(`${props.module.route_prefix}.export`, {
        tab: 'index',
        q: props.filters.q ?? undefined,
        date_from: props.filters.date_from ?? undefined,
        date_to: props.filters.date_to ?? undefined,
    }),
);
</script>

<template>
    <ReportShell :module="module" tab="index">
        <!-- ตัวกรอง -->
        <form class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs" @submit.prevent="search">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <TextInput v-model="form.q" type="text" :placeholder="`ค้นหาจากชื่อ${terms.item}, IP Address`" @keyup.enter="search" />
                </div>
                <label class="flex items-center gap-2">
                    <span class="whitespace-nowrap text-sm text-gray-500">ตั้งแต่</span>
                    <TextInput v-model="form.date_from" type="date" />
                </label>
                <label class="flex items-center gap-2">
                    <span class="whitespace-nowrap text-sm text-gray-500">ถึง</span>
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
                <a
                    :href="exportHref"
                    class="ml-auto inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-xs transition hover:bg-gray-50"
                    title="ส่งออกข้อมูลดิบตามตัวกรอง (รวมภาษา/อุปกรณ์/เบราว์เซอร์/แหล่งที่มา) สูงสุด 100,000 แถว"
                >
                    <Download class="mr-1.5 size-4" /> ส่งออก CSV
                </a>
            </div>
        </form>

        <!-- ตาราง -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th v-for="col in columns" :key="col.key" class="px-4 py-3 font-medium">
                                <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy(col.key)">
                                    {{ col.label }}
                                    <component :is="sortIcon(col.key)" class="size-3.5" :class="sort === col.key ? 'text-brand-500' : 'text-gray-400'" />
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in logs.data" :key="row.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span v-if="!can.view_item" class="text-gray-800">{{ row.title ?? `${terms.item} #${row.item_id}` }}</span>
                                <Link v-else :href="route(module.item_report_route, row.item_id)" class="text-brand-600 hover:text-brand-700">
                                    {{ row.title ?? `${terms.item} #${row.item_id}` }}
                                </Link>
                            </td>
                            <td class="w-48 px-4 py-3 text-gray-600">{{ row.remote_ip ?? '-' }}</td>
                            <td class="w-56 whitespace-nowrap px-4 py-3 text-gray-600">{{ formatDateTime(row.created_at) }}</td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td :colspan="columns.length" class="px-4 py-10 text-center text-gray-500">ไม่พบข้อมูลการ{{ terms.verb }}ตามเงื่อนไข</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="order-1 shrink-0 text-sm text-gray-500">แสดง {{ logs.from ?? 0 }}–{{ logs.to ?? 0 }} จาก {{ logs.total }} รายการ</p>

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
    </ReportShell>
</template>
