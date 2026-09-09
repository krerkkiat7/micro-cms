<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import type { UserGroupOption } from '@/types';

defineProps<{
    userGroups: UserGroupOption[];
}>();

const form = useForm({
    titlename: '',
    firstname: '',
    lastname: '',
    email: '',
    mobile: '',
    phone: '',
    line: '',
    facebook: '',
    usergroup_id: '',
    password: '',
    password_confirmation: '',
    status: 'Y',
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
                        <SelectInput id="usergroup_id" v-model="form.usergroup_id">
                            <option value="" disabled>เลือกกลุ่มผู้ใช้งาน</option>
                            <option
                                v-for="g in userGroups"
                                :key="g.id"
                                :value="String(g.id)"
                            >
                                {{ g.name }}
                            </option>
                        </SelectInput>
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
                    <SelectInput id="status" v-model="form.status">
                        <option value="Y">ใช้งาน</option>
                        <option value="N">ไม่ใช้งาน</option>
                    </SelectInput>
                    <InputError :message="form.errors.status" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link
                    :href="route('admin.system.user.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
