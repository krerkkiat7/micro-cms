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
import { Plus, RotateCcw, Search } from 'lucide-vue-next';
import { reactive } from 'vue';
import type { Paginated } from '@/types';

interface Row {
    id: number;
    title: string | null;
    article_count: number;
    status: string;
}

const props = defineProps<{
    categories: Paginated<Row>;
    filters: {
        q: string | null;
        status: string | null;
        per_page: number;
    };
    perPageOptions: number[];
    can: { manage: boolean };
}>();

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    per_page: String(props.filters.per_page),
});

function visit(extra: Record<string, unknown> = {}) {
    router.get(
        route('admin.article.category.index'),
        {
            q: form.q !== '' ? form.q : undefined,
            status: form.status !== '' ? form.status : undefined,
            per_page: form.per_page,
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
    router.get(route('admin.article.category.index'), {}, { preserveScroll: true, replace: true });
}

function goToEdit(id: number) {
    router.get(route('admin.article.category.edit', id));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่บทความ' },
];
</script>

<template>
    <Head title="รายการหมวดหมู่บทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="หมวดหมู่บทความ" :breadcrumbs="breadcrumbs" />
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
                            placeholder="ค้นหาชื่อ, ข้อความเกริ่นนำ"
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
                            @click="router.get(route('admin.article.category.add'))"
                        >
                            <Plus class="mr-1.5 size-4" /> เพิ่มหมวดหมู่
                        </PrimaryButton>
                    </template>
                </div>
            </form>

            <!-- ตาราง -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px] text-left text-sm">
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">ชื่อ</th>
                                <th class="px-4 py-3 font-medium">จำนวนบทความ</th>
                                <th class="px-4 py-3 font-medium">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in categories.data"
                                :key="row.id"
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                                @click="goToEdit(row.id)"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.article.category.edit', row.id)"
                                        class="font-medium text-brand-600 hover:text-brand-700"
                                        @click.stop
                                    >
                                        {{ row.title ?? '(ไม่มีชื่อ)' }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ row.article_count }}</td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="row.status" />
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td
                                    colspan="3"
                                    class="px-4 py-10 text-center text-gray-500"
                                >
                                    ไม่พบหมวดหมู่ตามเงื่อนไข
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ท้ายตาราง: จำนวนรายการอยู่ซ้าย, เลขหน้าอยู่กึ่งกลาง (ตกลงบรรทัดใหม่ถ้าที่ไม่พอ), จำนวนต่อหน้าชิดขวา -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="order-1 shrink-0 text-sm text-gray-500">
                    แสดง {{ categories.from ?? 0 }}–{{ categories.to ?? 0 }} จาก {{ categories.total }} รายการ
                </p>

                <Pagination
                    :current-page="categories.current_page"
                    :last-page="categories.last_page"
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
