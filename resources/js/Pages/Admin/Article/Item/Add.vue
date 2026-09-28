<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ArticleItemFormFields from '@/Components/Admin/ArticleForm/ArticleItemFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { createPart, partsToPayload } from '@/utils/articleParts';
import { emptyItemDetails } from '@/utils/articleForm';
import type { ArticleItemFormData } from '@/utils/articleForm';
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

const form = useForm<ArticleItemFormData>({
    article_category_info_id: '',
    intro_image_id: null,
    publish_date: nowDateTime(),
    publish_down: null,
    status: 'Y',
    tags: [],
    detail: emptyItemDetails(props.languages),
    parts: [createPart('text', props.languages)],
});

const introImage = ref<FileItem[]>([]);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function submit() {
    form.transform((data) => ({
        ...data,
        article_category_info_id: data.article_category_info_id !== '' ? Number(data.article_category_info_id) : null,
        parts: partsToPayload(data.parts),
    })).post(route('admin.article.item.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'บทความ', href: route('admin.article.item.index') },
    { label: 'เพิ่มบทความ' },
];
</script>

<template>
    <Head title="เพิ่มบทความ" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มบทความ" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <ArticleItemFormFields
                v-model:intro-image="introImage"
                :form="form"
                :languages="languages"
                :category-options="categoryOptions"
            />

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link :href="route('admin.article.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
