<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

const form = useForm({
    name: '',
    description: '',
    status: 'Y',
});

function submit() {
    form.post(route('admin.system.usergroup.store'));
}

const breadcrumbs = [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการกลุ่มผู้ใช้งาน', href: route('admin.system.usergroup.index') },
    { label: 'เพิ่มกลุ่มผู้ใช้งาน' },
];
</script>

<template>
    <Head title="เพิ่มกลุ่มผู้ใช้งาน" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เพิ่มกลุ่มผู้ใช้งาน" :breadcrumbs="breadcrumbs" />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
            >
                <h2 class="text-base font-semibold text-gray-800">ข้อมูลกลุ่มผู้ใช้งาน</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <InputLabel for="name" value="ชื่อกลุ่ม" required />
                        <TextInput id="name" v-model="form.name" type="text" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="sm:col-span-6">
                        <InputLabel for="description" value="รายละเอียด" />
                        <Textarea id="description" v-model="form.description" rows="4" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="status" value="สถานะ" required />
                        <SelectInput id="status" v-model="form.status">
                            <option value="Y">ใช้งาน</option>
                            <option value="N">ไม่ใช้งาน</option>
                        </SelectInput>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">
                    <Save class="mr-1.5 size-4" /> บันทึก
                </PrimaryButton>
                <Link
                    :href="route('admin.system.usergroup.index')"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    ยกเลิก
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
