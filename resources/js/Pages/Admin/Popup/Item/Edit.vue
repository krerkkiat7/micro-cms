<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import PopupItemFormFields from '@/Components/Admin/PopupForm/PopupItemFormFields.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { popupPartsFromServer, popupPartsToPayload } from '@/utils/popupForm';
import type { PopupItemFormData, PopupMenuNode, PopupPartFromServer } from '@/utils/popupForm';
import type { LanguageOption } from '@/types';

interface EditItem {
    id: number;
    name: string;
    display_type: string;
    show_dismiss_today: string;
    show_arrows: string;
    show_dots: string;
    autoplay: string;
    slide_interval: number;
    slide_speed: number;
    menu_mode: string;
    menu_ids: number[];
    publish_date: string | null;
    publish_down: string | null;
    sort_order: number;
    status: string;
    parts: PopupPartFromServer[];
}

const props = defineProps<{
    item: EditItem;
    languages: LanguageOption[];
    menuTree: PopupMenuNode[];
    systemInfo: SystemAudit;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm<PopupItemFormData>({
    name: props.item.name,
    display_type: props.item.display_type,
    show_dismiss_today: props.item.show_dismiss_today,
    show_arrows: props.item.show_arrows,
    show_dots: props.item.show_dots,
    autoplay: props.item.autoplay,
    slide_interval: String(props.item.slide_interval),
    slide_speed: String(props.item.slide_speed),
    menu_mode: props.item.menu_mode,
    menu_ids: [...props.item.menu_ids],
    publish_date: props.item.publish_date,
    publish_down: props.item.publish_down,
    sort_order: String(props.item.sort_order ?? 0),
    status: props.item.status,
    parts: popupPartsFromServer(props.item.parts, props.languages),
});

function submit() {
    form.transform((data) => ({ ...data, parts: popupPartsToPayload(data.parts) })).put(route('admin.popup.item.update', props.item.id), {
        preserveScroll: true,
    });
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.popup.item.destroy', props.item.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'Popup', href: route('admin.popup.item.index') },
    { label: props.item.name },
]);
</script>

<template>
    <Head :title="`แก้ไข Popup: ${item.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="item.name" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <PopupItemFormFields :form="form" :languages="languages" :menu-tree="menuTree" />

            <SystemInfoCard :audit="systemInfo" />

            <div class="flex flex-wrap items-center gap-3">
                <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <DangerButton v-if="can.delete" type="button" @click="confirmingDeletion = true">
                    <Trash2 class="mr-1.5 size-4" /> ลบ
                </DangerButton>
                <BackToListButton :href="route('admin.popup.item.index')" />
            </div>
        </form>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบ Popup"
            confirm-text="ลบ Popup"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบ Popup "{{ item.name }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
