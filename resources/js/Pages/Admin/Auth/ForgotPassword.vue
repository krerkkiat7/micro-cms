<script setup lang="ts">
import AuthLayout from '@/Layouts/Admin/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('admin.password.email'));
};
</script>

<template>
    <AuthLayout
        title="ลืมรหัสผ่าน"
        description="กรอกอีเมลของคุณ ระบบจะส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ให้"
    >
        <Head title="ลืมรหัสผ่าน" />

        <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600">
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="อีเมล" />
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" />
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">
                ส่งลิงก์ตั้งรหัสผ่านใหม่
            </PrimaryButton>

            <p class="text-center text-sm text-gray-500">
                <Link
                    :href="route('admin.login')"
                    class="font-medium text-brand-600 hover:text-brand-700"
                >
                    กลับไปหน้าเข้าสู่ระบบ
                </Link>
            </p>
        </form>
    </AuthLayout>
</template>
