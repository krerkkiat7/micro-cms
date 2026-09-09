<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { formatDateTime } from '@/utils/date';

interface EditGroup {
    id: number;
    name: string;
    description: string | null;
    status: string;
    can_edit: string;
    can_delete: string;
    actions_count: number;
    users_count: number;
    created_at: string | null;
}

const props = defineProps<{
    group: EditGroup;
    can: { manage: boolean; delete: boolean; rights: boolean };
}>();

const locked = computed(() => props.group.can_edit === 'N');
const canSave = computed(() => props.can.manage && !locked.value);
const canDelete = computed(
    () =>
        props.can.delete &&
        props.group.can_delete === 'Y' &&
        props.group.users_count === 0,
);

const form = useForm({
    name: props.group.name,
    description: props.group.description ?? '',
    status: props.group.status,
});

function submit() {
    form.put(route('admin.system.usergroup.update', props.group.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.system.usergroup.destroy', props.group.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการกลุ่มผู้ใช้งาน', href: route('admin.system.usergroup.index') },
    { label: props.group.name },
]);

const tabs = computed(() => [
    {
        label: 'ข้อมูลทั่วไป',
        href: route('admin.system.usergroup.edit', props.group.id),
        active: true,
    },
    {
        label: 'กำหนดสิทธิ์',
        href: route('admin.system.usergroup.rights', props.group.id),
        active: false,
    },
]);

const groupInfo = computed(() => [
    { label: 'จำนวนสิทธิ์', value: String(props.group.actions_count) },
    { label: 'จำนวนสมาชิก', value: String(props.group.users_count) },
    { label: 'วันที่สร้าง', value: formatDateTime(props.group.created_at) },
]);
</script>

<template>
    <Head :title="`แก้ไขกลุ่มผู้ใช้งาน: ${group.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="group.name" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav v-if="can.rights" :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                >
                    <h2 class="text-base font-semibold text-gray-800">
                        ข้อมูลกลุ่มผู้ใช้งาน
                    </h2>

                    <p
                        v-if="locked"
                        class="mt-2 text-sm text-amber-600"
                    >
                        กลุ่มนี้เป็นกลุ่มระบบ ไม่สามารถแก้ไขได้
                    </p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <InputLabel for="name" value="ชื่อกลุ่ม" required />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                :disabled="locked"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="sm:col-span-6">
                            <InputLabel for="description" value="รายละเอียด" />
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                :disabled="locked"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="status" value="สถานะ" required />
                            <SelectInput
                                id="status"
                                v-model="form.status"
                                :disabled="locked"
                            >
                                <option value="Y">ใช้งาน</option>
                                <option value="N">ไม่ใช้งาน</option>
                            </SelectInput>
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                >
                    <h2 class="text-base font-semibold text-gray-800">ข้อมูลกลุ่ม</h2>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div v-for="item in groupInfo" :key="item.label">
                            <dt class="text-sm text-gray-500">{{ item.label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">
                                {{ item.value }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton
                        v-if="canSave"
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <DangerButton
                        v-if="canDelete"
                        type="button"
                        @click="confirmingDeletion = true"
                    >
                        <Trash2 class="mr-1.5 size-4" /> ลบ
                    </DangerButton>
                    <p
                        v-if="can.delete && group.can_delete === 'Y' && group.users_count > 0"
                        class="text-sm text-gray-500"
                    >
                        ลบไม่ได้: ยังมีสมาชิก {{ group.users_count }} คน
                    </p>
                    <Link
                        :href="route('admin.system.usergroup.index')"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700"
                    >
                        กลับไปหน้ารายการ
                    </Link>
                </div>
            </form>
        </div>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบกลุ่มผู้ใช้งาน"
            confirm-text="ลบกลุ่มผู้ใช้งาน"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบกลุ่มผู้ใช้งาน "{{ group.name }}" ใช่หรือไม่?
        </ConfirmDialog>
    </AdminLayout>
</template>
