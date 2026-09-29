<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import BannerItemFormFields from '@/Components/Admin/BannerForm/BannerItemFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { emptyBannerDetails } from '@/utils/bannerForm';
import type { BannerItemFormData } from '@/utils/bannerForm';
import type { FileItem, LanguageOption } from '@/types';

interface CategoryOption {
    id: number;
    title: string | null;
}

const props = defineProps<{
    languages: LanguageOption[];
    categories: CategoryOption[];
}>();

const categoryOptions = computed(() => props.categories.map((cat) => ({ value: String(cat.id), label: cat.title ?? '(ไม่มีชื่อ)' })));

function nowDateTime(): string {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}

const form = useForm<BannerItemFormData>({
    banner_category_info_id: '',
    intro_image_id: null,
    url: '',
    link_target: '_self',
    publish_date: nowDateTime(),
    publish_down: null,
    sort_order: '0',
    status: 'Y',
    detail: emptyBannerDetails(props.languages),
});

const introImage = ref<FileItem[]>([]);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function submit() {
    form.transform((data) => ({
        ...data,
        banner_category_info_id: data.banner_category_info_id !== '' ? Number(data.banner_category_info_id) : null,
    })).post(route('admin.banner.item.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ป้ายโฆษณา', href: route('admin.banner.item.index') },
    { label: 'เพิ่มป้ายโฆษณา' },
];
</script>

<template>
    <Head title="เพิ่มป้ายโฆษณา" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มป้ายโฆษณา" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <BannerItemFormFields v-model:intro-image="introImage" :form="form" :languages="languages" :category-options="categoryOptions" />

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <BackToListButton :href="route('admin.banner.item.index')" cancel />
            </div>
        </form>
    </AdminLayout>
</template>
