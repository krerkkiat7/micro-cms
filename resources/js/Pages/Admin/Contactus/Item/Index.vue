<script setup lang="ts">
import PageHeader from '@/Components/Admin/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import type { Paginated } from '@/types';
import { formatDateTime } from '@/utils/date';
import {
    CONTACTUS_PROCESS_STATUS_CLASS,
    CONTACTUS_PROCESS_STATUS_OPTIONS,
    contactusProcessStatusLabel,
    type ContactusProcessStatus,
} from '@/utils/contactus';
import { Head, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, RotateCcw, Search } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

/**
 * รายการข้อมูลติดต่อเราที่ส่งมาจากหน้าบ้าน — ไม่มีปุ่มเพิ่ม, คลิกแถวไปหน้ารายละเอียด/แก้ไข
 */
interface Row {
    id: number;
    fullname: string;
    email: string | null;
    subject: string | null;
    process_status: ContactusProcessStatus;
    created_at: string | null;
}

const props = defineProps<{
    items: Paginated<Row>;
    filters: {
        q: string | null;
        process_status: string | null;
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
    process_status: props.filters.process_status ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    per_page: String(props.filters.per_page),
});

const columns: { key: string; label: string }[] = [
    { key: 'fullname', label: 'ชื่อ - นามสกุล' },
    { key: 'email', label: 'อีเมล' },
    { key: 'subject', label: 'หัวข้อ' },
    { key: 'created_at', label: 'วันเวลาที่ส่ง' },
    { key: 'process_status', label: 'สถานะ' },
];

const statusFilterOptions = [{ value: '', label: 'ทุกสถานะ' }, ...CONTACTUS_PROCESS_STATUS_OPTIONS];

const perPageSelectOptions = computed(() => props.perPageOptions.map((n) => ({ value: String(n), label: String(n) })));

function visit(extra: Record<string, unknown> = {}) {
    router.get(
        route('admin.contactus.item.index'),
        {
            q: form.q !== '' ? form.q : undefined,
            process_status: form.process_status !== '' ? form.process_status : undefined,
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
    form.process_status = '';
    form.date_from = '';
    form.date_to = '';
    form.per_page = String(props.perPageOptions[0]);
    router.get(route('admin.contactus.item.index'), {}, { preserveScroll: true, replace: true });
}

function sortBy(column: string) {
    const direction = props.sort === column && props.direction === 'asc' ? 'desc' : 'asc';
    visit({ sort: column, direction, page: undefined });
}

function sortIcon(column: string) {
    if (props.sort !== column) return ArrowUpDown;
    return props.direction === 'asc' ? ArrowUp : ArrowDown;
}

function open(row: Row) {
    router.visit(route('admin.contactus.item.edit', row.id));
}

const breadcrumbs = [{ label: 'Dashboard', href: route('admin.dashboard') }, { label: 'ติดต่อเรา' }];
</script>

<template>
    <Head title="ติดต่อเรา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="ติดต่อเรา" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <!-- ตัวกรอง -->
            <form class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs" @submit.prevent="search">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <TextInput
                            v-model="form.q"
                            type="text"
                            placeholder="ค้นหาจาก ชื่อ - นามสกุล, อีเมล, หัวข้อ, ตำแหน่ง, บริษัท, เบอร์ติดต่อ"
                            @keyup.enter="search"
                        />
                    </div>
                    <label class="flex items-center gap-2">
                        <span class="whitespace-nowrap text-sm text-gray-500">ส่งตั้งแต่</span>
                        <TextInput v-model="form.date_from" type="date" />
                    </label>
                    <label class="flex items-center gap-2">
                        <span class="whitespace-nowrap text-sm text-gray-500">ถึง</span>
                        <TextInput v-model="form.date_to" type="date" />
                    </label>
                    <SearchableSelect v-model="form.process_status" :options="statusFilterOptions" />
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <PrimaryButton type="submit"><Search class="mr-1.5 size-4" /> ค้นหา</PrimaryButton>
                    <SecondaryButton type="button" @click="resetFilters"><RotateCcw class="mr-1.5 size-4" /> เริ่มใหม่</SecondaryButton>
                </div>
            </form>

            <!-- ตาราง -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th v-for="col in columns" :key="col.key" class="px-4 py-3 font-medium">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 transition-colors hover:text-gray-700"
                                        @click="sortBy(col.key)"
                                    >
                                        {{ col.label }}
                                        <component
                                            :is="sortIcon(col.key)"
                                            class="size-3.5"
                                            :class="sort === col.key ? 'text-brand-500' : 'text-gray-400'"
                                        />
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in items.data"
                                :key="row.id"
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                                :class="row.process_status === 'unread' ? 'font-semibold' : ''"
                                tabindex="0"
                                @click="open(row)"
                                @keydown.enter="open(row)"
                            >
                                <td class="px-4 py-3 text-gray-800">{{ row.fullname }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ row.email ?? '-' }}</td>
                                <td class="max-w-[22rem] truncate px-4 py-3 text-gray-600" :title="row.subject ?? ''">{{ row.subject ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ formatDateTime(row.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="CONTACTUS_PROCESS_STATUS_CLASS[row.process_status]"
                                    >
                                        {{ contactusProcessStatusLabel(row.process_status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="items.data.length === 0">
                                <td :colspan="columns.length" class="px-4 py-10 text-center text-gray-500">ไม่พบข้อมูลตามเงื่อนไข</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="order-1 shrink-0 text-sm text-gray-500">แสดง {{ items.from ?? 0 }}–{{ items.to ?? 0 }} จาก {{ items.total }} รายการ</p>

                <Pagination
                    :current-page="items.current_page"
                    :last-page="items.last_page"
                    class="order-3 w-full sm:order-2 sm:w-auto sm:flex-1"
                    @navigate="(page) => visit({ page })"
                />

                <div class="order-2 w-20 shrink-0 sm:order-3">
                    <SearchableSelect v-model="form.per_page" :options="perPageSelectOptions" class="text-sm" @update:model-value="search" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
