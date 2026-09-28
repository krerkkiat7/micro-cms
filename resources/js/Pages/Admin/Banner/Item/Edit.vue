<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import BannerItemFormFields from '@/Components/Admin/BannerForm/BannerItemFormFields.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { BannerDetail, BannerItemFormData } from '@/utils/bannerForm';
import type { FileItem, LanguageOption } from '@/types';

interface EditItem {
    id: number;
    banner_category_info_id: number | null;
    status: string;
    url: string | null;
    link_target: string | null;
    publish_date: string | null;
    publish_down: string | null;
    sort_order: number;
    intro_image: FileItem | null;
}

interface CategoryOption {
    id: number;
    title: string | null;
}

const props = defineProps<{
    item: EditItem;
    details: Record<string, BannerDetail>;
    languages: LanguageOption[];
    categories: CategoryOption[];
    systemInfo: SystemAudit;
    clickCount: number;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm<BannerItemFormData>({
    banner_category_info_id: props.item.banner_category_info_id ? String(props.item.banner_category_info_id) : '',
    intro_image_id: props.item.intro_image?.id ?? null,
    url: props.item.url ?? '',
    link_target: props.item.link_target ?? '_self',
    publish_date: props.item.publish_date,
    publish_down: props.item.publish_down,
    sort_order: String(props.item.sort_order ?? 0),
    status: props.item.status,
    detail: { ...props.details },
});

const categoryOptions = computed(() => props.categories.map((cat) => ({ value: String(cat.id), label: cat.title ?? '(ไม่มีชื่อ)' })));

const introImage = ref<FileItem[]>(props.item.intro_image ? [props.item.intro_image] : []);
watch(introImage, (files) => {
    form.intro_image_id = files[0]?.id ?? null;
});

function submit() {
    form.transform((data) => ({
        ...data,
        banner_category_info_id: data.banner_category_info_id !== '' ? Number(data.banner_category_info_id) : null,
    })).put(route('admin.banner.item.update', props.item.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.banner.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `ป้ายโฆษณา #${props.item.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'ป้ายโฆษณา', href: route('admin.banner.item.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขป้ายโฆษณา: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <BannerItemFormFields v-model:intro-image="introImage" :form="form" :languages="languages" :category-options="categoryOptions" />

            <SystemInfoCard :audit="systemInfo" :append="[{ label: 'จำนวนคลิก', value: clickCount }]" />

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link :href="route('admin.banner.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบป้ายโฆษณา"
            confirm-text="ลบป้ายโฆษณา"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบป้ายโฆษณา "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
