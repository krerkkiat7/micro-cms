<script setup lang="ts">
import AuthLayout from '@/Layouts/Admin/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    titlename: '',
    firstname: '',
    lastname: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('admin.register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout title="สมัครสมาชิก" description="สร้างบัญชีผู้ดูแลระบบใหม่">
        <Head title="สมัครสมาชิก" />

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="titlename" value="คำนำหน้า" />
                <TextInput
                    id="titlename"
                    type="text"
                    v-model="form.titlename"
                    autocomplete="honorific-prefix"
                />
                <InputError :message="form.errors.titlename" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="firstname" value="ชื่อ" />
                    <TextInput
                        id="firstname"
                        type="text"
                        v-model="form.firstname"
                        required
                        autofocus
                        autocomplete="given-name"
                    />
                    <InputError :message="form.errors.firstname" />
                </div>

                <div>
                    <InputLabel for="lastname" value="นามสกุล" />
                    <TextInput
                        id="lastname"
                        type="text"
                        v-model="form.lastname"
                        required
                        autocomplete="family-name"
                    />
                    <InputError :message="form.errors.lastname" />
                </div>
            </div>

            <div>
                <InputLabel for="email" value="อีเมล" />
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="รหัสผ่าน" />
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="ยืนยันรหัสผ่าน" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">
                สมัครสมาชิก
            </PrimaryButton>

            <p class="text-center text-sm text-gray-500">
                มีบัญชีอยู่แล้ว?
                <Link
                    :href="route('admin.login')"
                    class="font-medium text-brand-600 hover:text-brand-700"
                >
                    เข้าสู่ระบบ
                </Link>
            </p>
        </form>
    </AuthLayout>
</template>
