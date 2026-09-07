<script setup lang="ts">
import AuthLayout from '@/Layouts/Admin/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('admin.password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthLayout
        title="ยืนยันรหัสผ่าน"
        description="ส่วนนี้เป็นพื้นที่ปลอดภัย กรุณายืนยันรหัสผ่านก่อนดำเนินการต่อ"
    >
        <Head title="ยืนยันรหัสผ่าน" />

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="รหัสผ่าน" />
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError :message="form.errors.password" />
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">
                ยืนยัน
            </PrimaryButton>
        </form>
    </AuthLayout>
</template>
