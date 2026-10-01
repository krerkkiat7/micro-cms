<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ArticleCategoryFormFields from '@/Components/Admin/ArticleForm/ArticleCategoryFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { ArticleCategoryDetail, ArticleCategoryFormData } from '@/utils/articleForm';
import type { FileItem, LanguageOption } from '@/types';

interface EditCategory {
    id: number;
    sort_order: number;
    status: string;
    intro_image: FileItem | null;
}

const props = defineProps<{
    category: EditCategory;
    details: Record<string, ArticleCategoryDetail>;
    languages: LanguageOption[];
    systemInfo: SystemAudit;
    articleCount: number;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm<ArticleCategoryFormData>({
    intro_image_id: props.category.intro_image?.id ?? null,
    sort_order: String(props.category.sort_order ?? 0),
    status: props.category.status,
    detail: { ...props.details },
});

// FilePickerField ทำงานกับ array ของไฟล์เสมอ (เลือกได้ 1 รูป) — เลือกใหม่ = แทนที่ของเดิมทั้งหมด
const introImage = ref<FileItem[]>(props.category.intro_image ? [props.category.intro_image] : []);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function submit() {
    form.put(route('admin.article.category.update', props.category.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm<{ category?: string }>({});

function destroy() {
    deleteForm.delete(route('admin.article.category.destroy', props.category.id), {
        onFinish: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `หมวดหมู่ #${props.category.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่บทความ', href: route('admin.article.category.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขหมวดหมู่บทความ: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <ArticleCategoryFormFields v-model:intro-image="introImage" :form="form" :languages="languages" />

            <SystemInfoCard :audit="systemInfo" :append="[{ label: 'จำนวนบทความ', value: articleCount }]" />

            <InputError :message="deleteForm.errors.category" />

            <div class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <BackToListButton :href="route('admin.article.category.index')" />
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบหมวดหมู่"
            confirm-text="ลบหมวดหมู่"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบหมวดหมู่ "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
