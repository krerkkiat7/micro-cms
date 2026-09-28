<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import ArticleItemFormFields from '@/Components/Admin/ArticleForm/ArticleItemFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { partsFromServer, partsToPayload } from '@/utils/articleParts';
import type { PartData } from '@/utils/articleParts';
import type { ArticleItemDetail, ArticleItemFormData, ArticleTagChip } from '@/utils/articleForm';
import type { FileItem, LanguageOption } from '@/types';

interface EditItem {
    id: number;
    article_category_info_id: number | null;
    status: string;
    publish_date: string | null;
    publish_down: string | null;
    intro_image: FileItem | null;
}

interface CategoryOption {
    id: number;
    title: string | null;
}

interface ServerPart {
    part_type: PartData['part_type'];
    images_display_type: string | null;
    show_title: 'Y' | 'N';
    status: 'Y' | 'N';
    setting: Record<string, unknown> | null;
    detail: Record<string, { title: string; detail: string }>;
    files: Array<{
        file: FileItem | null;
        cover_image: FileItem | null;
        video_type: string | null;
        youtube_url: string | null;
        description: Record<string, unknown> | null;
    }>;
}

const props = defineProps<{
    item: EditItem;
    details: Record<string, ArticleItemDetail>;
    parts: ServerPart[];
    tags: ArticleTagChip[];
    languages: LanguageOption[];
    categories: CategoryOption[];
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm<ArticleItemFormData>({
    article_category_info_id: props.item.article_category_info_id ? String(props.item.article_category_info_id) : '',
    intro_image_id: props.item.intro_image?.id ?? null,
    publish_date: props.item.publish_date,
    publish_down: props.item.publish_down,
    status: props.item.status,
    tags: props.tags.map((t) => t.id),
    detail: { ...props.details },
    parts: partsFromServer(props.parts, props.languages) as PartData[],
});

const categoryOptions = computed(() => props.categories.map((cat) => ({ value: String(cat.id), label: cat.title ?? '(ไม่มีชื่อ)' })));

const introImage = ref<FileItem[]>(props.item.intro_image ? [props.item.intro_image] : []);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function submit() {
    form.transform((data) => ({
        ...data,
        article_category_info_id: data.article_category_info_id !== '' ? Number(data.article_category_info_id) : null,
        parts: partsToPayload(data.parts),
    })).put(route('admin.article.item.update', props.item.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.article.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `บทความ #${props.item.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'บทความ', href: route('admin.article.item.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขบทความ: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <ArticleItemFormFields
                v-model:intro-image="introImage"
                :form="form"
                :languages="languages"
                :category-options="categoryOptions"
                :initial-tags="tags"
            />

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link :href="route('admin.article.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบบทความ"
            confirm-text="ลบบทความ"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบบทความ "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
