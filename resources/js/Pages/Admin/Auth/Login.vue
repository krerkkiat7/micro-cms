<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import AuthLayout from '@/Layouts/Admin/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

declare global {
    interface Window {
        grecaptcha?: {
            ready: (callback: () => void) => void;
            execute: (siteKey: string, options: { action: string }) => Promise<string>;
        };
    }
}

const props = defineProps<{
    canResetPassword?: boolean;
    status?: string;
    recaptcha?: { enabled: boolean; siteKey: string | null };
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
    'g-recaptcha-response': '',
});

// reCAPTCHA v3 (invisible) — โหลดสคริปต์เฉพาะตอนเปิดใช้งาน + มี site key ครบ
onMounted(() => {
    if (props.recaptcha?.enabled && props.recaptcha.siteKey && ! window.grecaptcha) {
        const script = document.createElement('script');
        script.src = `https://www.google.com/recaptcha/api.js?render=${props.recaptcha.siteKey}`;
        script.async = true;
        document.head.appendChild(script);
    }
});

function submitLogin() {
    form.post(route('admin.login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
}

const submit = () => {
    if (props.recaptcha?.enabled && props.recaptcha.siteKey) {
        const siteKey = props.recaptcha.siteKey;

        window.grecaptcha?.ready(() => {
            window.grecaptcha!.execute(siteKey, { action: 'login' }).then((token) => {
                form['g-recaptcha-response'] = token;
                submitLogin();
            });
        });

        return;
    }

    submitLogin();
};
</script>

<template>
    <AuthLayout title="เข้าสู่ระบบ" description="กรอกอีเมลและรหัสผ่านเพื่อเข้าสู่ระบบจัดการ">
        <Head title="เข้าสู่ระบบ" />

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

            <div>
                <InputLabel for="password" value="รหัสผ่าน" />
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-sm text-gray-600">จดจำฉันไว้</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('admin.password.request')"
                    class="text-sm font-medium text-brand-600 hover:text-brand-700"
                >
                    ลืมรหัสผ่าน?
                </Link>
            </div>

            <InputError :message="form.errors['g-recaptcha-response']" />

            <PrimaryButton class="w-full" :disabled="form.processing">
                เข้าสู่ระบบ
            </PrimaryButton>
        </form>
    </AuthLayout>
</template>
