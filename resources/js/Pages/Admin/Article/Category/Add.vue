<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ArticleCategoryFormFields from '@/Components/Admin/ArticleForm/ArticleCategoryFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { emptyCategoryDetails } from '@/utils/articleForm';
import type { ArticleCategoryFormData } from '@/utils/articleForm';
import type { FileItem, LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
}>();

const form = useForm<ArticleCategoryFormData>({
    intro_image_id: null,
    sort_order: '0',
    status: 'Y',
    detail: emptyCategoryDetails(props.languages),
});

// FilePickerField ทำงานกับ array ของไฟล์เสมอ (เลือกได้ 1 รูป) — sync กลับเป็น id เดี่ยวก่อนส่งฟอร์ม
const introImage = ref<FileItem[]>([]);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function submit() {
    form.post(route('admin.article.category.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่บทความ', href: route('admin.article.category.index') },
    { label: 'เพิ่มหมวดหมู่' },
];
</script>

<template>
    <Head title="เพิ่มหมวดหมู่บทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มหมวดหมู่บทความ" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <ArticleCategoryFormFields v-model:intro-image="introImage" :form="form" :languages="languages" />

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <BackToListButton :href="route('admin.article.category.index')" cancel />
            </div>
        </form>
    </AdminLayout>
</template>
