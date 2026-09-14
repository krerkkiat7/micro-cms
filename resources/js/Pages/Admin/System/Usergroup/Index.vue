<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, Plus, RotateCcw, Search } from 'lucide-vue-next';
import { reactive } from 'vue';
import type { Paginated } from '@/types';

interface Row {
    id: number;
    name: string;
    description: string | null;
    status: string;
    actions_count: number;
    users_count: number;
    can_edit: string;
    can_delete: string;
}

const props = defineProps<{
    groups: Paginated<Row>;
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

const columns: { key: string; label: string; sortable: boolean }[] = [
    { key: 'name', label: 'ชื่อกลุ่ม', sortable: true },
    { key: 'description', label: 'รายละเอียด', sortable: false },
    { key: 'actions_count', label: 'จำนวนสิทธิ์', sortable: true },
    { key: 'users_count', label: 'จำนวนสมาชิก', sortable: true },
    { key: 'status', label: 'สถานะ', sortable: true },
];

function visit(extra: Record<string, unknown> = {}) {
    router.get(
        route('admin.system.usergroup.index'),
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
    router.get(
        route('admin.system.usergroup.index'),
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

function goToEdit(id: number) {
    router.get(route('admin.system.usergroup.edit', id));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการกลุ่มผู้ใช้งาน' },
];
</script>

<template>
    <Head title="รายการกลุ่มผู้ใช้งาน" />

    <AdminLayout>
        <template #header>
            <PageHeader title="รายการกลุ่มผู้ใช้งาน" :breadcrumbs="breadcrumbs" />
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
                            placeholder="ค้นหาชื่อกลุ่ม รายละเอียด"
                            @keyup.enter="search"
                        />
                    </div>
                    <SelectInput v-model="form.status">
                        <option value="">ทุกสถานะ</option>
                        <option value="Y">ใช้งาน</option>
                        <option value="N">ไม่ใช้งาน</option>
                    </SelectInput>
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
                            @click="router.get(route('admin.system.usergroup.add'))"
                        >
                            <Plus class="mr-1.5 size-4" /> เพิ่มกลุ่มผู้ใช้งาน
                        </PrimaryButton>
                    </template>
                </div>
            </form>

            <!-- ตาราง -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
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
                                v-for="row in groups.data"
                                :key="row.id"
                                class="cursor-pointer align-top transition-colors hover:bg-gray-50"
                                @click="goToEdit(row.id)"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.system.usergroup.edit', row.id)"
                                        class="font-medium text-brand-600 hover:text-brand-700"
                                        @click.stop
                                    >
                                        {{ row.name }}
                                    </Link>
                                </td>
                                <td
                                    class="max-w-sm whitespace-pre-line px-4 py-3 text-gray-600"
                                >
                                    {{ row.description ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ row.actions_count }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ row.users_count }}
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="row.status" />
                                </td>
                            </tr>
                            <tr v-if="groups.data.length === 0">
                                <td
                                    :colspan="columns.length"
                                    class="px-4 py-10 text-center text-gray-500"
                                >
                                    ไม่พบกลุ่มผู้ใช้งานตามเงื่อนไข
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ท้ายตาราง: จำนวนรายการอยู่ซ้าย, เลขหน้าอยู่กึ่งกลาง (ตกลงบรรทัดใหม่ถ้าที่ไม่พอ), จำนวนต่อหน้าชิดขวา -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="order-1 shrink-0 text-sm text-gray-500">
                    แสดง {{ groups.from ?? 0 }}–{{ groups.to ?? 0 }} จาก {{ groups.total }} รายการ
                </p>

                <Pagination
                    :current-page="groups.current_page"
                    :last-page="groups.last_page"
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
    </AdminLayout>
</template>
