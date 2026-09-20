<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PageItemFormFields from '@/Components/Admin/PageItem/PageItemFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { emptyPageItemForm, toPagePayload } from '@/utils/pageItem';
import type { PageItemFormData } from '@/utils/pageItem';
import type { LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
}>();

const form = useForm<PageItemFormData>(emptyPageItemForm(props.languages));

function submit() {
    form.transform((data) => toPagePayload(data as PageItemFormData)).post(route('admin.page.item.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หน้าเพจ', href: route('admin.page.item.index') },
    { label: 'เพิ่มหน้าเพจ' },
];
</script>

<template>
    <Head title="เพิ่มหน้าเพจ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มหน้าเพจ" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <PageItemFormFields :form="form" :languages="languages" />

            <p class="text-xs text-gray-500">หลังบันทึกแล้ว จะจัดโครงสร้างหน้า (แถว / คอลัมน์ / Widget) ได้ที่แท็บ "โครงสร้าง"</p>

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link :href="route('admin.page.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
