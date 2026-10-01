<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import BannerItemFormFields from '@/Components/Admin/BannerForm/BannerItemFormFields.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { bannerItemPayload } from '@/utils/bannerForm';
import type { BannerDetail, BannerItemFormData, BannerLinkType } from '@/utils/bannerForm';
import type { FrontMenuPickerOption } from '@/utils/readAllButton';
import type { FileItem, LanguageOption } from '@/types';

interface EditItem {
    id: number;
    banner_category_info_id: number | null;
    status: string;
    link_type: BannerLinkType;
    front_menu_info_id: number | null;
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
    frontMenus: FrontMenuPickerOption[];
    systemInfo: SystemAudit;
    clickCount: number;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm<BannerItemFormData>({
    banner_category_info_id: props.item.banner_category_info_id ? String(props.item.banner_category_info_id) : '',
    intro_image_id: props.item.intro_image?.id ?? null,
    link_type: props.item.link_type ?? 'none',
    front_menu_info_id: props.item.front_menu_info_id ? String(props.item.front_menu_info_id) : '',
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
    form.transform(bannerItemPayload).put(route('admin.banner.item.update', props.item.id));
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

const tabs = computed(() => [
    { label: 'ข้อมูลทั่วไป', href: route('admin.banner.item.edit', props.item.id), active: true },
    { label: 'รายงาน', href: route('admin.banner.item.report', props.item.id), active: false },
]);

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

        <TabNav :tabs="tabs" class="mb-6" />

        <form class="space-y-6" @submit.prevent="submit">
            <BannerItemFormFields v-model:intro-image="introImage" :form="form" :languages="languages" :category-options="categoryOptions" :front-menus="frontMenus" />

            <SystemInfoCard :audit="systemInfo" :append="[{ label: 'จำนวนคลิก', value: clickCount }]" />

            <div class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <BackToListButton :href="route('admin.banner.item.index')" />
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
