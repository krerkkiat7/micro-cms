<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, Plus, RotateCcw, Search } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { STATUS_FILTER_OPTIONS } from '@/utils/options';
import type { Paginated } from '@/types';

interface Row {
    id: number;
    title: string | null;
    created_at: string | null;
    status: string;
    view_amount: number;
}

const props = defineProps<{
    items: Paginated<Row>;
    filters: {
        q: string | null;
        status: string | null;
        per_page: number;
    };
    sort: string;
    direction: 'asc' | 'desc';
    perPageOptions: number[];
    can: { manage: boolean };
}>();

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    per_page: String(props.filters.per_page),
});

const perPageSelectOptions = computed(() => props.perPageOptions.map((n) => ({ value: String(n), label: String(n) })));

function visit(extra: Record<string, unknown> = {}) {
    router.get(
        route('admin.page.item.index'),
        {
            q: form.q !== '' ? form.q : undefined,
            status: form.status !== '' ? form.status : undefined,
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
    form.status = '';
    form.per_page = String(props.perPageOptions[0]);
    router.get(route('admin.page.item.index'), {}, { preserveScroll: true, replace: true });
}

function sortBy(column: string) {
    const direction = props.sort === column && props.direction === 'asc' ? 'desc' : 'asc';
    visit({ sort: column, direction, page: undefined });
}

function sortIcon(column: string) {
    if (props.sort !== column) return ArrowUpDown;
    return props.direction === 'asc' ? ArrowUp : ArrowDown;
}

function goToEdit(id: number) {
    router.get(route('admin.page.item.edit', id));
}

function formatDate(value: string | null): string {
    if (!value) return '-';

    return new Date(value.replace(' ', 'T')).toLocaleString('th-TH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หน้าเพจ' },
];
</script>

<template>
    <Head title="รายการหน้าเพจ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="หน้าเพจ" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <!-- ตัวกรอง -->
            <form class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs" @submit.prevent="search">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <TextInput v-model="form.q" type="text" placeholder="ค้นหาชื่อ" @keyup.enter="search" />
                    </div>
                    <SearchableSelect v-model="form.status" :options="STATUS_FILTER_OPTIONS" />
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <PrimaryButton type="submit">
                        <Search class="mr-1.5 size-4" /> ค้นหา
                    </PrimaryButton>
                    <SecondaryButton type="button" @click="resetFilters">
                        <RotateCcw class="mr-1.5 size-4" /> เริ่มใหม่
                    </SecondaryButton>

                    <template v-if="can.manage">
                        <span class="mx-1 h-7 w-px bg-gray-200" aria-hidden="true" />
                        <PrimaryButton type="button" @click="router.get(route('admin.page.item.add'))">
                            <Plus class="mr-1.5 size-4" /> เพิ่มหน้าเพจ
                        </PrimaryButton>
                    </template>
                </div>
            </form>

            <!-- ตาราง -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">
                                    <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy('title')">
                                        ชื่อ
                                        <component :is="sortIcon('title')" class="size-3.5" :class="sort === 'title' ? 'text-brand-500' : 'text-gray-400'" />
                                    </button>
                                </th>
                                <th class="w-36 px-4 py-3 text-right font-medium">
                                    <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy('view_amount')">
                                        จำนวนเข้าชม
                                        <component :is="sortIcon('view_amount')" class="size-3.5" :class="sort === 'view_amount' ? 'text-brand-500' : 'text-gray-400'" />
                                    </button>
                                </th>
                                <th class="w-48 px-4 py-3 font-medium">
                                    <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy('created_at')">
                                        วันที่สร้าง
                                        <component :is="sortIcon('created_at')" class="size-3.5" :class="sort === 'created_at' ? 'text-brand-500' : 'text-gray-400'" />
                                    </button>
                                </th>
                                <th class="w-28 px-4 py-3 font-medium">
                                    <button type="button" class="inline-flex items-center gap-1 transition-colors hover:text-gray-700" @click="sortBy('status')">
                                        สถานะ
                                        <component :is="sortIcon('status')" class="size-3.5" :class="sort === 'status' ? 'text-brand-500' : 'text-gray-400'" />
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in items.data"
                                :key="row.id"
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                                @click="goToEdit(row.id)"
                            >
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.page.item.edit', row.id)" class="font-medium text-brand-600 hover:text-brand-700" @click.stop>
                                        {{ row.title ?? '(ไม่มีชื่อ)' }}
                                    </Link>
                                </td>
                                <td class="w-36 px-4 py-3 text-right tabular-nums text-gray-600">{{ row.view_amount.toLocaleString('th-TH') }}</td>
                                <td class="w-48 px-4 py-3 text-gray-600">{{ formatDate(row.created_at) }}</td>
                                <td class="w-28 px-4 py-3">
                                    <StatusBadge :status="row.status" />
                                </td>
                            </tr>
                            <tr v-if="items.data.length === 0">
                                <td colspan="4" class="px-4 py-10 text-center text-gray-500">ไม่พบหน้าเพจตามเงื่อนไข</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ท้ายตาราง -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="order-1 shrink-0 text-sm text-gray-500">
                    แสดง {{ items.from ?? 0 }}–{{ items.to ?? 0 }} จาก {{ items.total }} รายการ
                </p>

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
