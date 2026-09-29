<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    user: { id: number; name: string; email: string };
}>();

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('admin.system.user.password.update', props.user.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการผู้ใช้งาน', href: route('admin.system.user.index') },
    { label: props.user.name, href: route('admin.system.user.edit', props.user.id) },
    { label: 'เปลี่ยนรหัสผ่าน' },
]);

const tabs = computed(() => [
    {
        label: 'ข้อมูลทั่วไป',
        href: route('admin.system.user.edit', props.user.id),
        active: false,
    },
    {
        label: 'เปลี่ยนรหัสผ่าน',
        href: route('admin.system.user.password', props.user.id),
        active: true,
    },
]);

const passwordHint =
    'อย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์ใหญ่ พิมพ์เล็ก ตัวเลข และอักขระพิเศษ อย่างน้อยอย่างละ 1 ตัว';
</script>

<template>
    <Head :title="`เปลี่ยนรหัสผ่าน: ${user.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader title="เปลี่ยนรหัสผ่าน" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <form class="space-y-6" @submit.prevent="submit">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8"
                >
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500">ชื่อ</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">
                                {{ user.name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">อีเมล</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">
                                {{ user.email }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="password" value="รหัสผ่านใหม่" required />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                            />
                            <p class="mt-1.5 text-xs text-gray-500">
                                {{ passwordHint }}
                            </p>
                            <InputError :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel
                                for="password_confirmation"
                                value="ยืนยันรหัสผ่านใหม่"
                                required
                            />
                            <TextInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                            />
                            <InputError
                                :message="form.errors.password_confirmation"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton type="submit" :disabled="form.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <BackToListButton :href="route('admin.system.user.edit', user.id)" cancel />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
