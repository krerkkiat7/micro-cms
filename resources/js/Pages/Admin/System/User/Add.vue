<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { STATUS_OPTIONS } from '@/utils/options';
import { userGroupSelectOptions } from '@/utils/userGroup';
import type { FileItem, UserGroupOption } from '@/types';

const props = defineProps<{
    userGroups: UserGroupOption[];
    /** ข้อความแนะนำเรื่องกลุ่มระบบ (null = ผู้ใช้ปัจจุบันอยู่ในกลุ่มระบบ ไม่ต้องแสดง) */
    systemGroupMessage: string | null;
}>();

const userGroupOptions = computed(() => userGroupSelectOptions(props.userGroups));
const hasDisabledGroup = computed(() => props.userGroups.some((g) => g.disabled));

const form = useForm({
    titlename: '',
    firstname: '',
    lastname: '',
    email: '',
    mobile: '',
    phone: '',
    line: '',
    facebook: '',
    profile_image_id: null as number | null,
    usergroup_id: '',
    password: '',
    password_confirmation: '',
    status: 'Y',
});

// FilePickerField ทำงานกับ array ของไฟล์เสมอ (เลือกได้ 1 รูป) — sync กลับเป็น id เดี่ยวก่อนส่งฟอร์ม
const profileImage = ref<FileItem[]>([]);
watch(profileImage, (files) => {
    form.profile_image_id = files[0]?.id ?? null;
});

function submit() {
    form.post(route('admin.system.user.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการผู้ใช้งาน', href: route('admin.system.user.index') },
    { label: 'เพิ่มผู้ใช้งาน' },
];

const passwordHint =
    'อย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์ใหญ่ พิมพ์เล็ก ตัวเลข และอักขระพิเศษ อย่างน้อยอย่างละ 1 ตัว';
</script>

<template>
    <Head title="เพิ่มผู้ใช้งาน" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มผู้ใช้งาน" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
            >
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลผู้ใช้งาน</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <InputLabel for="titlename" value="คำนำหน้า" required />
                        <TextInput id="titlename" v-model="form.titlename" type="text" />
                        <InputError :message="form.errors.titlename" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="firstname" value="ชื่อ" required />
                        <TextInput id="firstname" v-model="form.firstname" type="text" />
                        <InputError :message="form.errors.firstname" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="lastname" value="นามสกุล" required />
                        <TextInput id="lastname" v-model="form.lastname" type="text" />
                        <InputError :message="form.errors.lastname" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel for="email" value="อีเมล" required />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel for="usergroup_id" value="กลุ่มผู้ใช้งาน" required />
                        <SearchableSelect id="usergroup_id" v-model="form.usergroup_id" :options="userGroupOptions" placeholder="เลือกกลุ่มผู้ใช้งาน" />
                        <p v-if="hasDisabledGroup && systemGroupMessage" class="mt-1.5 text-xs text-gray-500">{{ systemGroupMessage }}</p>
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
                        <TextInput id="facebook" v-model="form.facebook" type="text" />
                        <InputError :message="form.errors.facebook" />
                    </div>
                    <div class="sm:col-span-6">
                        <InputLabel value="รูปโปรไฟล์" />
                        <FilePickerField v-model="profileImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                        <InputError :message="form.errors.profile_image_id" />
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
            >
                <h2 class="text-base font-semibold text-gray-800">รหัสผ่าน</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="password" value="รหัสผ่าน" required />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                        />
                        <p class="mt-1.5 text-xs text-gray-500">{{ passwordHint }}</p>
                        <InputError :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="ยืนยันรหัสผ่าน"
                            required
                        />
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
            >
                <h2 class="text-base font-semibold text-gray-800">สถานะ</h2>

                <div class="mt-5 sm:w-64">
                    <InputLabel for="status" value="สถานะ" required />
                    <SearchableSelect id="status" v-model="form.status" :options="STATUS_OPTIONS" />
                    <InputError :message="form.errors.status" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <BackToListButton :href="route('admin.system.user.index')" cancel />
            </div>
        </form>
    </AdminLayout>
</template>
