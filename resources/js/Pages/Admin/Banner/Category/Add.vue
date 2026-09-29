<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import BannerCategoryFormFields from '@/Components/Admin/BannerForm/BannerCategoryFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { emptyBannerDetails } from '@/utils/bannerForm';
import type { BannerCategoryFormData } from '@/utils/bannerForm';
import type { LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
}>();

const form = useForm<BannerCategoryFormData>({
    status: 'Y',
    detail: emptyBannerDetails(props.languages),
});

function submit() {
    form.post(route('admin.banner.category.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่ป้ายโฆษณา', href: route('admin.banner.category.index') },
    { label: 'เพิ่มหมวดหมู่' },
];
</script>

<template>
    <Head title="เพิ่มหมวดหมู่ป้ายโฆษณา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มหมวดหมู่ป้ายโฆษณา" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <BannerCategoryFormFields :form="form" :languages="languages" />

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <BackToListButton :href="route('admin.banner.category.index')" cancel />
            </div>
        </form>
    </AdminLayout>
</template>
