<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PopupItemFormFields from '@/Components/Admin/PopupForm/PopupItemFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { createPopupPart, nowDateTime, popupPartsToPayload } from '@/utils/popupForm';
import type { PopupItemFormData, PopupMenuNode } from '@/utils/popupForm';
import type { LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
    menuTree: PopupMenuNode[];
}>();

// หน้าเพิ่มเริ่มต้นด้วยข้อมูล (part) 1 รายการ
const form = useForm<PopupItemFormData>({
    name: '',
    display_type: 'modal',
    show_dismiss_today: 'Y',
    show_arrows: 'Y',
    show_dots: 'Y',
    autoplay: 'Y',
    slide_interval: '5',
    slide_speed: '3000',
    menu_mode: 'all',
    menu_ids: [],
    publish_date: nowDateTime(),
    publish_down: null,
    sort_order: '0',
    status: 'Y',
    parts: [createPopupPart(props.languages)],
});

function submit() {
    form.transform((data) => ({ ...data, parts: popupPartsToPayload(data.parts) })).post(route('admin.popup.item.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'Popup', href: route('admin.popup.item.index') },
    { label: 'เพิ่ม Popup' },
];
</script>

<template>
    <Head title="เพิ่ม Popup" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่ม Popup" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <PopupItemFormFields :form="form" :languages="languages" :menu-tree="menuTree" />

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link :href="route('admin.popup.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">ยกเลิก</Link>
            </div>
        </form>
    </AdminLayout>
</template>
