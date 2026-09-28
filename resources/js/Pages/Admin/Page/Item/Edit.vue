<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import PageItemFormFields from '@/Components/Admin/PageItem/PageItemFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toPagePayload } from '@/utils/pageItem';
import type { PageDetailFields, PageItemFormData } from '@/utils/pageItem';
import type { FileItem, LanguageOption } from '@/types';

interface EditItem {
    id: number;
    intro_image: FileItem | null;
    background_color: string | null;
    background_image: FileItem | null;
    background_repeat: string | null;
    background_size: string | null;
    background_attachment: string | null;
    background_position: string | null;
    status: string;
}

const props = defineProps<{
    item: EditItem;
    details: Record<string, PageDetailFields>;
    languages: LanguageOption[];
    systemInfo: SystemAudit;
    viewCount: number;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm<PageItemFormData>({
    intro_image: props.item.intro_image ? [props.item.intro_image] : [],
    background_color: props.item.background_color ?? '',
    background_image: props.item.background_image ? [props.item.background_image] : [],
    background_repeat: props.item.background_repeat ?? '',
    background_size: props.item.background_size ?? '',
    background_attachment: props.item.background_attachment ?? '',
    background_position: props.item.background_position ?? '',
    status: props.item.status,
    detail: { ...props.details },
});

function submit() {
    form.transform((data) => toPagePayload(data as PageItemFormData)).put(route('admin.page.item.update', props.item.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.page.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `หน้าเพจ #${props.item.id}`;
});

const tabs = computed(() => [
    { label: 'ข้อมูลทั่วไป', href: route('admin.page.item.edit', props.item.id), active: true },
    { label: 'โครงสร้าง', href: route('admin.page.item.layout', props.item.id), active: false },
]);

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หน้าเพจ', href: route('admin.page.item.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไขหน้าเพจ: ${defaultTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <PageItemFormFields :form="form" :languages="languages" />

                <SystemInfoCard :audit="systemInfo" :append="[{ label: 'จำนวนผู้เข้าชม', value: viewCount }]" />

                <div v-if="can.manage || can.delete" class="flex flex-wrap items-center gap-3">
                    <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                        <Trash2 class="mr-1.5 size-4" /> ลบ
                    </DangerButton>
                    <Link :href="route('admin.page.item.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                        กลับไปหน้ารายการ
                    </Link>
                </div>
            </form>
        </div>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบหน้าเพจ"
            confirm-text="ลบหน้าเพจ"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบหน้าเพจ "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
