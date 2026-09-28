<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import BannerCategoryFormFields from '@/Components/Admin/BannerForm/BannerCategoryFormFields.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { BannerCategoryFormData, BannerDetail } from '@/utils/bannerForm';
import type { LanguageOption } from '@/types';

interface EditCategory {
    id: number;
    status: string;
}

const props = defineProps<{
    category: EditCategory;
    details: Record<string, BannerDetail>;
    languages: LanguageOption[];
    systemInfo: SystemAudit;
    bannerCount: number;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm<BannerCategoryFormData>({
    status: props.category.status,
    detail: { ...props.details },
});

function submit() {
    form.put(route('admin.banner.category.update', props.category.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.banner.category.destroy', props.category.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `หมวดหมู่ #${props.category.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หมวดหมู่ป้ายโฆษณา', href: route('admin.banner.category.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขหมวดหมู่ป้ายโฆษณา: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <BannerCategoryFormFields :form="form" :languages="languages" />

            <SystemInfoCard :audit="systemInfo" :append="[{ label: 'จำนวนป้ายโฆษณา', value: bannerCount }]" />

            <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <Link
                    :href="route('admin.banner.category.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    กลับไปหน้ารายการ
                </Link>
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
