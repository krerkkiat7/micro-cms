<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import type { RequestPayload } from '@inertiajs/core';
import { ArrowUpDown, Plus } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import MenuTreeNode from '@/Components/Admin/FrontMenu/MenuTreeNode.vue';
import MenuFormDialog from '@/Components/Admin/FrontMenu/MenuFormDialog.vue';
import MenuReorderDialog from '@/Components/Admin/FrontMenu/MenuReorderDialog.vue';
import type { ReorderItem } from '@/utils/frontMenu';
import type { FrontMenuNode, LanguageOption } from '@/types';

const props = defineProps<{
    tree: FrontMenuNode[];
    languages: LanguageOption[];
    menuTypes: Record<string, string>;
    articleCategories: { value: string; label: string }[];
    fonts: string[];
    fontsUrl: string;
    can: { manage: boolean; delete: boolean };
}>();

// โหลดสไตล์ชีตฟอนต์ไทยทั้งหมดครั้งเดียว ให้เห็นตัวอย่างฟอนต์จริงตอนเลือกในฟอร์ม (เทียบเคียง Pages/Admin/Page/Item/Layout.vue)
function loadFonts() {
    const id = 'front-menu-fonts';
    if (document.getElementById(id)) return;

    const link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href = props.fontsUrl;
    document.head.appendChild(link);
}

onMounted(loadFonts);

const editingMenu = ref<FrontMenuNode | null>(null);
const showForm = ref(false);
const showReorder = ref(false);

function openAdd() {
    editingMenu.value = null;
    showForm.value = true;
}

function openEdit(node: FrontMenuNode) {
    editingMenu.value = node;
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
}

function toggleStatus(node: FrontMenuNode) {
    router.put(route('admin.system.menu.status', node.id), {}, { preserveScroll: true });
}

function confirmReorder(order: ReorderItem[]) {
    router.put(route('admin.system.menu.reorder'), { order } as unknown as RequestPayload, {
        preserveScroll: true,
        // ผิดพลาด (เช่น วางเมนูผิดระดับ) ก็ปิด dialog ให้เห็นข้อความ error เหนือรายการ
        onFinish: () => (showReorder.value = false),
    });
}

const pendingDelete = ref<FrontMenuNode | null>(null);
const deleteForm = useForm<{ menu?: string }>({});

// ข้อความ error ของการลบ / สลับสถานะ (menu) / เรียงลำดับ (order) — แสดงเหนือรายการ
const page = usePage();
const listError = computed(() => deleteForm.errors.menu || page.props.errors.menu || page.props.errors.order || null);

function destroy() {
    if (!pendingDelete.value) return;

    deleteForm.delete(route('admin.system.menu.destroy', pendingDelete.value.id), {
        preserveScroll: true,
        onFinish: () => (pendingDelete.value = null),
    });
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการเมนูหน้าบ้าน' },
];
</script>

<template>
    <Head title="จัดการเมนูหน้าบ้าน" />

    <AdminLayout>
        <template #header>
            <PageHeader title="จัดการเมนูหน้าบ้าน" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <SecondaryButton v-if="can.manage" type="button" @click="showReorder = true">
                    <ArrowUpDown class="mr-1.5 size-4" /> เรียงลำดับ
                </SecondaryButton>
                <PrimaryButton v-if="can.manage" type="button" @click="openAdd">
                    <Plus class="mr-1.5 size-4" /> เพิ่ม
                </PrimaryButton>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
                <p v-if="listError" class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ listError }}
                </p>

                <MenuTreeNode
                    v-for="node in tree"
                    :key="node.id"
                    :node="node"
                    :depth="0"
                    :menu-types="menuTypes"
                    :can-manage="can.manage"
                    :can-delete="can.delete"
                    @edit="openEdit"
                    @toggle-status="toggleStatus"
                    @remove="(node) => (pendingDelete = node)"
                />

                <p v-if="tree.length === 0" class="py-10 text-center text-sm text-gray-400">ยังไม่มีเมนู — กด "เพิ่ม" เพื่อเริ่มสร้างเมนู</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <SecondaryButton v-if="can.manage" type="button" @click="showReorder = true">
                    <ArrowUpDown class="mr-1.5 size-4" /> เรียงลำดับ
                </SecondaryButton>
                <PrimaryButton v-if="can.manage" type="button" @click="openAdd">
                    <Plus class="mr-1.5 size-4" /> เพิ่ม
                </PrimaryButton>
            </div>
        </div>

        <MenuFormDialog
            :show="showForm"
            :menu="editingMenu"
            :tree="tree"
            :languages="languages"
            :menu-types="menuTypes"
            :article-categories="articleCategories"
            :fonts="fonts"
            @close="closeForm"
        />

        <MenuReorderDialog :show="showReorder" :tree="tree" @close="showReorder = false" @confirm="confirmReorder" />

        <ConfirmDialog
            :show="pendingDelete !== null"
            title="ยืนยันการลบเมนู"
            confirm-text="ลบเมนู"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="pendingDelete = null"
        >
            ต้องการลบเมนูนี้ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
