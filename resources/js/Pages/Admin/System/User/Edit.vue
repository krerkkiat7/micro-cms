<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import SystemInfoCard from '@/Components/Admin/SystemInfoCard.vue';
import type { SystemAudit } from '@/Components/Admin/SystemInfoCard.vue';
import { userGroupSelectOptions } from '@/utils/userGroup';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { FileItem, UserGroupOption } from '@/types';
import { formatDateTime } from '@/utils/date';

interface EditUser {
    id: number;
    titlename: string | null;
    firstname: string;
    lastname: string;
    name: string;
    email: string;
    mobile: string | null;
    phone: string | null;
    line: string | null;
    facebook: string | null;
    usergroup_id: number | null;
    status: string;
    created_at: string | null;
    last_login_at: string | null;
    failed_login_count: number;
    last_failed_login_at: string | null;
    profile_image: FileItem | null;
}

const props = defineProps<{
    user: EditUser;
    userGroups: UserGroupOption[];
    isSelf: boolean;
    /** ผู้ใช้ในกลุ่มระบบที่ผู้ใช้ปัจจุบันจัดการไม่ได้ — ดูได้อย่างเดียว */
    protected: boolean;
    /** ข้อความแนะนำเรื่องกลุ่มระบบ (null = ผู้ใช้ปัจจุบันอยู่ในกลุ่มระบบ ไม่ต้องแสดง) */
    systemGroupMessage: string | null;
    systemInfo: SystemAudit;
    can: { manage: boolean; delete: boolean; password: boolean };
}>();

const form = useForm({
    titlename: props.user.titlename ?? '',
    firstname: props.user.firstname,
    lastname: props.user.lastname,
    email: props.user.email,
    mobile: props.user.mobile ?? '',
    phone: props.user.phone ?? '',
    line: props.user.line ?? '',
    facebook: props.user.facebook ?? '',
    profile_image_id: props.user.profile_image?.id ?? null,
    usergroup_id: props.user.usergroup_id ? String(props.user.usergroup_id) : '',
    status: props.user.status,
});

// FilePickerField ทำงานกับ array ของไฟล์เสมอ (เลือกได้ 1 รูป) — เลือกใหม่ = แทนที่ของเดิมทั้งหมด
const profileImage = ref<FileItem[]>(props.user.profile_image ? [props.user.profile_image] : []);
watch(profileImage, (files) => {
    form.profile_image_id = files[0]?.id ?? null;
});

function submit() {
    form.put(route('admin.system.user.update', props.user.id));
}

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

function destroy() {
    deleteForm.delete(route('admin.system.user.destroy', props.user.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
}

const userGroupOptions = computed(() => userGroupSelectOptions(props.userGroups));
const hasDisabledGroup = computed(() => props.userGroups.some((g) => g.disabled));

const statusOptions = computed(() => [
    { value: 'Y', label: 'ใช้งาน' },
    { value: 'N', label: 'ไม่ใช้งาน', disabled: props.isSelf },
]);

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการผู้ใช้งาน', href: route('admin.system.user.index') },
    { label: props.user.name },
]);

const tabs = computed(() => [
    {
        label: 'ข้อมูลทั่วไป',
        href: route('admin.system.user.edit', props.user.id),
        active: true,
    },
    {
        label: 'เปลี่ยนรหัสผ่าน',
        href: route('admin.system.user.password', props.user.id),
        active: false,
    },
]);

const loginInfo = computed(() => [
    { label: 'เข้าสู่ระบบล่าสุด', value: formatDateTime(props.user.last_login_at) },
    {
        label: 'จำนวนครั้งที่เข้าสู่ระบบผิดพลาด',
        value: String(props.user.failed_login_count),
    },
    {
        label: 'เข้าสู่ระบบผิดพลาดล่าสุด',
        value: formatDateTime(props.user.last_failed_login_at),
    },
]);
</script>

<template>
    <Head :title="`แก้ไขผู้ใช้งาน: ${user.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="user.name" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav v-if="can.password" :tabs="tabs" />

            <div v-if="protected && systemGroupMessage" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                ผู้ใช้งานนี้อยู่ในกลุ่มระบบ — ดูข้อมูลได้อย่างเดียว. {{ systemGroupMessage }}
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                >
                    <h2 class="text-base font-semibold text-gray-800">
                        ข้อมูลผู้ใช้งาน
                    </h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-2">
                            <InputLabel for="titlename" value="คำนำหน้า" required />
                            <TextInput
                                id="titlename"
                                v-model="form.titlename"
                                type="text"
                            />
                            <InputError :message="form.errors.titlename" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="firstname" value="ชื่อ" required />
                            <TextInput
                                id="firstname"
                                v-model="form.firstname"
                                type="text"
                            />
                            <InputError :message="form.errors.firstname" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="lastname" value="นามสกุล" required />
                            <TextInput
                                id="lastname"
                                v-model="form.lastname"
                                type="text"
                            />
                            <InputError :message="form.errors.lastname" />
                        </div>

                        <div class="sm:col-span-3">
                            <InputLabel for="email" value="อีเมล" required />
                            <TextInput id="email" v-model="form.email" type="email" />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel
                                for="usergroup_id"
                                value="กลุ่มผู้ใช้งาน"
                                required
                            />
                            <SearchableSelect id="usergroup_id" v-model="form.usergroup_id" :options="userGroupOptions" placeholder="เลือกกลุ่มผู้ใช้งาน" />
                            <p v-if="hasDisabledGroup && systemGroupMessage && !protected" class="mt-1.5 text-xs text-gray-500">{{ systemGroupMessage }}</p>
                            <InputError :message="form.errors.usergroup_id" />
                        </div>

                        <div class="sm:col-span-3">
                            <InputLabel for="mobile" value="เบอร์มือถือ" />
                            <TextInput id="mobile" v-model="form.mobile" type="text" />
                            <InputError :message="form.errors.mobile" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel for="phone" value="เบอร์ติดต่อ" />
                            <TextInput id="phone" v-model="form.phone" type="text" />
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel for="line" value="LINE" />
                            <TextInput id="line" v-model="form.line" type="text" />
                            <InputError :message="form.errors.line" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel for="facebook" value="Facebook" />
                            <TextInput
                                id="facebook"
                                v-model="form.facebook"
                                type="text"
                            />
                            <InputError :message="form.errors.facebook" />
                        </div>
                        <div class="sm:col-span-6">
                            <InputLabel value="รูปโปรไฟล์" />
                            <FilePickerField
                                v-model="profileImage"
                                :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']"
                            />
                            <InputError :message="form.errors.profile_image_id" />
                        </div>

                        <div class="sm:col-span-3">
                            <InputLabel for="status" value="สถานะ" required />
                            <SearchableSelect id="status" v-model="form.status" :options="statusOptions" />
                            <p
                                v-if="isSelf"
                                class="mt-1.5 text-xs text-gray-500"
                            >
                                ไม่สามารถระงับบัญชีของตัวเองได้
                            </p>
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>
                </div>

                <SystemInfoCard :audit="systemInfo" :append="loginInfo" />

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton
                        v-if="can.manage"
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <DangerButton
                        v-if="can.delete && !isSelf"
                        type="button"
                        @click="confirmingDeletion = true"
                    >
                        <Trash2 class="mr-1.5 size-4" /> ลบ
                    </DangerButton>
                    <BackToListButton :href="route('admin.system.user.index')" />
                </div>
            </form>
        </div>

        <ConfirmDialog
            :show="confirmingDeletion"
            title="ยืนยันการลบผู้ใช้งาน"
            confirm-text="ลบผู้ใช้งาน"
            :processing="deleteForm.processing"
            @confirm="destroy"
            @cancel="confirmingDeletion = false"
        >
            ต้องการลบผู้ใช้งาน "{{ user.name }}" ใช่หรือไม่?
            ผู้ใช้งานนี้จะไม่สามารถเข้าสู่ระบบได้อีก
        </ConfirmDialog>
    </AdminLayout>
</template>
