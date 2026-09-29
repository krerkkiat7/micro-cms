<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { templateTabs } from '@/utils/template';

/**
 * แท็บข้อมูลทั่วไปของ template — ชื่อ + เปิดใช้งาน (รายการที่ใช้งานอยู่ปิดเองไม่ได้ และลบไม่ได้ — ต้องเปิดรายการอื่นแทน)
 */
const props = defineProps<{
    template: { id: number; name: string; preset: string | null; preset_label: string | null; status: string };
    systemInfo: SystemAudit;
    can: { manage: boolean; delete: boolean };
}>();

const form = useForm({
    name: props.template.name,
    status: props.template.status,
});

const isActive = computed(() => props.template.status === 'Y');

function submit() {
    form.put(route('admin.system.template.update', props.template.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.system.template.destroy', props.template.id), {
        onFinish: () => (confirmingDeletion.value = false),
    });
}

const tabs = computed(() => templateTabs(props.template.id, 'edit'));

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการ Template', href: route('admin.system.template.index') },
    { label: props.template.name },
]);
</script>

<template>
    <Head :title="`แก้ไข Template: ${template.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="template.name" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8">
                    <h2 class="text-base font-semibold text-gray-800">ข้อมูลทั่วไป</h2>

                    <div class="mt-5 space-y-5">
                        <div class="sm:w-2/3">
                            <InputLabel value="ชื่อ Template" required />
                            <TextInput v-model="form.name" type="text" :disabled="!can.manage" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div v-if="template.preset_label">
                            <InputLabel value="แม่แบบตั้งต้น" />
                            <p class="text-sm text-gray-700">{{ template.preset_label }}</p>
                        </div>

                        <div>
                            <InputLabel value="สถานะ" />
                            <p v-if="isActive" class="text-sm text-emerald-700">
                                กำลังใช้งานอยู่ — ต้องมี Template ที่ใช้งาน 1 รายการเสมอ หากต้องการเปลี่ยน ให้เปิดใช้งานรายการอื่นแทน
                            </p>
                            <YesNoCheckbox
                                v-else
                                v-model="form.status"
                                label="เปิดใช้งาน"
                                description="Template ที่ใช้งานอยู่ในปัจจุบันจะถูกปิดใช้งานอัตโนมัติ"
                            />
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>
                </div>

                <InputError :message="(deleteForm.errors as Record<string, string>).delete" />

                <SystemInfoCard :audit="systemInfo" />

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton v-if="can.manage" type="submit" :disabled="form.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <DangerButton
                        v-if="can.delete"
                        type="button"
                        :disabled="isActive"
                        :title="isActive ? 'ลบ Template ที่กำลังใช้งานอยู่ไม่ได้' : undefined"
                        @click="confirmingDeletion = true"
                    >
                        <Trash2 class="mr-1.5 size-4" /> ลบ
                    </DangerButton>
                    <BackToListButton :href="route('admin.system.template.index')" />
                </div>
            </form>
        </div>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบ Template"
            confirm-text="ลบ Template"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบ Template "{{ template.name }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
