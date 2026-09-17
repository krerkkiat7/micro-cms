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
import type { Paginated, UserGroupOption } from '@/types';
import { formatDateTime } from '@/utils/date';

interface Row {
    id: number;
    name: string;
    email: string;
    group: string | null;
    status: string;
    created_at: string | null;
    last_login_at: string | null;
    profile_image_hash_name: string | null;
}

const props = defineProps<{
    users: Paginated<Row>;
    filters: {
        q: string | null;
        usergroup_id: string | null;
        status: string | null;
        per_page: number;
    };
    sort: string;
    direction: 'asc' | 'desc';
    userGroups: UserGroupOption[];
    perPageOptions: number[];
    can: { manage: boolean };
}>();

const form = reactive({
    q: props.filters.q ?? '',
    usergroup_id: props.filters.usergroup_id ?? '',
    status: props.filters.status ?? '',
    per_page: String(props.filters.per_page),
});

const userGroupFilterOptions = computed(() => [
    { value: '', label: 'ทุกกลุ่มผู้ใช้งาน' },
    ...props.userGroups.map((g) => ({ value: String(g.id), label: g.name })),
]);

const perPageSelectOptions = computed(() => props.perPageOptions.map((n) => ({ value: String(n), label: String(n) })));

const columns: { key: string; label: string }[] = [
    { key: 'name', label: 'ชื่อ - นามสกุล' },
    { key: 'group', label: 'กลุ่มผู้ใช้งาน' },
    { key: 'email', label: 'อีเมล' },
    { key: 'created_at', label: 'วันที่สร้าง' },
    { key: 'last_login_at', label: 'เข้าสู่ระบบล่าสุด' },
    { key: 'status', label: 'สถานะ' },
];

function visit(extra: Record<string, unknown> = {}) {
    router.get(
        route('admin.system.user.index'),
        {
            q: form.q !== '' ? form.q : undefined,
            usergroup_id: form.usergroup_id !== '' ? form.usergroup_id : undefined,
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
    form.usergroup_id = '';
    form.status = '';
    form.per_page = String(props.perPageOptions[0]);
    router.get(
        route('admin.system.user.index'),
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
    router.get(route('admin.system.user.edit', id));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการผู้ใช้งาน' },
];
</script>

<template>
    <Head title="รายการผู้ใช้งาน" />

    <AdminLayout>
        <template #header>
            <PageHeader title="รายการผู้ใช้งาน" :breadcrumbs="breadcrumbs" />
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
                            placeholder="ค้นหาชื่อ นามสกุล อีเมล เบอร์มือถือ เบอร์ติดต่อ"
                            @keyup.enter="search"
                        />
                    </div>
                    <SearchableSelect v-model="form.usergroup_id" :options="userGroupFilterOptions" />
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
                            @click="router.get(route('admin.system.user.add'))"
                        >
                            <Plus class="mr-1.5 size-4" /> เพิ่มผู้ใช้งาน
                        </PrimaryButton>
                    </template>
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
                                <th class="w-14 px-4 py-3 font-medium">รูป</th>
                                <th
                                    v-for="col in columns"
                                    :key="col.key"
                                    class="px-4 py-3 font-medium"
                                >
                                    <button
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
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in users.data"
                                :key="row.id"
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                                @click="goToEdit(row.id)"
                            >
                                <td class="px-4 py-3">
                                    <div
                                        v-if="row.profile_image_hash_name"
                                        class="size-10 shrink-0 overflow-hidden rounded-full bg-gray-100"
                                    >
                                        <img
                                            :src="
                                                route('admin.system.file.get.thumbnail.size', {
                                                    size: 80,
                                                    hashname: row.profile_image_hash_name,
                                                })
                                            "
                                            :alt="row.name"
                                            class="size-full object-cover"
                                        />
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.system.user.edit', row.id)"
                                        class="font-medium text-brand-600 hover:text-brand-700"
                                        @click.stop
                                    >
                                        {{ row.name }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ row.group ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ row.email }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ formatDateTime(row.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ formatDateTime(row.last_login_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="row.status" />
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td
                                    :colspan="columns.length + 1"
                                    class="px-4 py-10 text-center text-gray-500"
                                >
                                    ไม่พบผู้ใช้งานตามเงื่อนไข
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ท้ายตาราง: จำนวนรายการอยู่ซ้าย, เลขหน้าอยู่กึ่งกลาง (ตกลงบรรทัดใหม่ถ้าที่ไม่พอ), จำนวนต่อหน้าชิดขวา -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="order-1 shrink-0 text-sm text-gray-500">
                    แสดง {{ users.from ?? 0 }}–{{ users.to ?? 0 }} จาก {{ users.total }} รายการ
                </p>

                <Pagination
                    :current-page="users.current_page"
                    :last-page="users.last_page"
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
