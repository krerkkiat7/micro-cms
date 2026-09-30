<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import IntropageFormFields from '@/Components/Admin/IntropageForm/IntropageFormFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { buttonsFromServer, buttonsToPayload } from '@/utils/intropageButtons';
import type { ButtonData, ButtonType, ButtonDisplayType } from '@/utils/intropageButtons';
import type { FileItem, LanguageOption } from '@/types';

interface DetailFields {
    title: string;
    detail: string;
}

interface EditItem {
    id: number;
    background_color: string | null;
    background_image: FileItem | null;
    background_repeat: string | null;
    background_size: string | null;
    background_attachment: string | null;
    background_position: string | null;
    display_type: string;
    display_size: string;
    image_file: FileItem | null;
    vdo_file: FileItem | null;
    vdo_url: string | null;
    detail_font_family: string;
    detail_font_size: number;
    detail_color: string;
    show_button: string;
    button_font_size: number;
    button_font_family: string;
    publish_date: string | null;
    publish_down: string | null;
    status: string;
}

interface ServerButton {
    button_type: ButtonType;
    button_display_type: ButtonDisplayType;
    background_color: string | null;
    text_color: string | null;
    button_image: FileItem | null;
    url: string | null;
    link_target: string | null;
    texts: Record<string, string> | null;
}

const props = defineProps<{
    item: EditItem;
    details: Record<string, DetailFields>;
    buttons: ServerButton[];
    languages: LanguageOption[];
    fonts: string[];
    fontsUrl: string;
    systemInfo: SystemAudit;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm({
    detail: { ...props.details } as Record<string, DetailFields>,
    detail_font_family: props.item.detail_font_family,
    detail_font_size: props.item.detail_font_size,
    detail_color: props.item.detail_color,
    display_type: props.item.display_type,
    display_size: props.item.display_size,
    image_file_id: props.item.image_file?.id ?? null,
    vdo_file_id: props.item.vdo_file?.id ?? null,
    vdo_url: props.item.vdo_url ?? '',
    background_color: props.item.background_color ?? '',
    background_image_id: props.item.background_image?.id ?? null,
    background_repeat: props.item.background_repeat ?? '',
    background_size: props.item.background_size ?? '',
    background_attachment: props.item.background_attachment ?? '',
    background_position: props.item.background_position ?? '',
    show_button: props.item.show_button,
    button_font_size: props.item.button_font_size,
    button_font_family: props.item.button_font_family,
    buttons: buttonsFromServer(props.buttons, props.languages) as ButtonData[],
    publish_date: props.item.publish_date,
    publish_down: props.item.publish_down,
    status: props.item.status,
});

function submit() {
    form.transform((data) => ({
        ...data,
        buttons: buttonsToPayload(data.buttons),
    })).put(route('admin.intropage.item.update', props.item.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.intropage.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const defaultTitle = computed(() => {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;
    return (defaultLang && props.details[defaultLang]?.title) || `Intropage #${props.item.id}`;
});

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'Intropage', href: route('admin.intropage.item.index') },
    { label: defaultTitle.value },
]);
</script>

<template>
    <Head :title="`แก้ไข Intropage: ${defaultTitle}`">
        <link rel="stylesheet" :href="fontsUrl" />
    </Head>

    <AdminLayout>
        <template #header>
            <PageHeader :title="defaultTitle" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <IntropageFormFields
                :form="form"
                :languages="languages"
                :fonts="fonts"
                :initial-background-image="item.background_image"
                :initial-image-file="item.image_file"
                :initial-vdo-file="item.vdo_file"
            />

            <SystemInfoCard :audit="systemInfo" />

            <div class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <BackToListButton :href="route('admin.intropage.item.index')" />
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบ Intropage"
            confirm-text="ลบ Intropage"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบ Intropage "{{ defaultTitle }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
