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
    name: string | null;
    article_count: number;
    status: string;
}

const props = defineProps<{
    tags: Paginated<Row>;
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
        route('admin.article.tag.index'),
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
    router.get(route('admin.article.tag.index'), {}, { preserveScroll: true, replace: true });
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
    router.get(route('admin.article.tag.edit', id));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'แท็กบทความ' },
];
</script>

<template>
    <Head title="รายการแท็กบทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="แท็กบทความ" :breadcrumbs="breadcrumbs" />
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
                            placeholder="ค้นหาชื่อแท็ก"
                            @keyup.enter="search"
                        />
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
                        <span
                            class="mx-1 h-7 w-px bg-gray-200"
                            aria-hidden="true"
                        />
                        <PrimaryButton
                            type="button"
                            @click="router.get(route('admin.article.tag.add'))"
                        >
                            <Plus class="mr-1.5 size-4" /> เพิ่มแท็ก
                        </PrimaryButton>
                    </template>
                </div>
            </form>

            <!-- ตาราง -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 transition-colors hover:text-gray-700"
                                        @click="sortBy('name')"
                                    >
                                        ชื่อ
                                        <component :is="sortIcon('name')" class="size-3.5" :class="sort === 'name' ? 'text-brand-500' : 'text-gray-400'" />
                                    </button>
                                </th>
                                <th class="w-28 px-4 py-3 font-medium">จำนวนบทความ</th>
                                <th class="w-28 px-4 py-3 font-medium">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 transition-colors hover:text-gray-700"
                                        @click="sortBy('status')"
                                    >
                                        สถานะ
                                        <component :is="sortIcon('status')" class="size-3.5" :class="sort === 'status' ? 'text-brand-500' : 'text-gray-400'" />
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in tags.data"
                                :key="row.id"
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                                @click="goToEdit(row.id)"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.article.tag.edit', row.id)"
                                        class="font-medium text-brand-600 hover:text-brand-700"
                                        @click.stop
                                    >
                                        {{ row.name ?? '(ไม่มีชื่อ)' }}
                                    </Link>
                                </td>
                                <td class="w-28 px-4 py-3 text-gray-600">{{ row.article_count }}</td>
                                <td class="w-28 px-4 py-3">
                                    <StatusBadge :status="row.status" />
                                </td>
                            </tr>
                            <tr v-if="tags.data.length === 0">
                                <td
                                    colspan="3"
                                    class="px-4 py-10 text-center text-gray-500"
                                >
                                    ไม่พบแท็กตามเงื่อนไข
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ท้ายตาราง: จำนวนรายการอยู่ซ้าย, เลขหน้าอยู่กึ่งกลาง (ตกลงบรรทัดใหม่ถ้าที่ไม่พอ), จำนวนต่อหน้าชิดขวา -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="order-1 shrink-0 text-sm text-gray-500">
                    แสดง {{ tags.from ?? 0 }}–{{ tags.to ?? 0 }} จาก {{ tags.total }} รายการ
                </p>

                <Pagination
                    :current-page="tags.current_page"
                    :last-page="tags.last_page"
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
