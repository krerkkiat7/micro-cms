<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import AuthLayout from '@/Layouts/Admin/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

declare global {
    interface Window {
        turnstile?: {
            render: (
                container: string | HTMLElement,
                options: {
                    sitekey: string;
                    execution?: 'render' | 'execute';
                    callback?: (token: string) => void;
                    'error-callback'?: () => void;
                },
            ) => string;
            execute: (widgetIdOrContainer: string | HTMLElement) => void;
            reset: (widgetIdOrContainer?: string | HTMLElement) => void;
        };
    }
}

const props = defineProps<{
    canResetPassword?: boolean;
    status?: string;
    captcha?: { enabled: boolean; siteKey: string | null };
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
    'cf-turnstile-response': '',
});

const turnstileContainer = ref<HTMLElement | null>(null);
let widgetId: string | null = null;

// Cloudflare Turnstile — โหลดสคริปต์ + render widget เฉพาะตอนเปิดใช้งาน + มี site key ครบ
// โหมดแสดงผล (Invisible/Non-Interactive) กำหนดตอนสร้าง site key ที่ Cloudflare dashboard ไม่ใช่ค่าจากโค้ดนี้
onMounted(() => {
    if (!props.captcha?.enabled || !props.captcha.siteKey || !turnstileContainer.value) {
        return;
    }

    const siteKey = props.captcha.siteKey;

    function renderWidget() {
        widgetId = window.turnstile!.render(turnstileContainer.value!, {
            sitekey: siteKey,
            execution: 'execute',
            callback: (token) => {
                form['cf-turnstile-response'] = token;
                submitLogin();
            },
        });
    }

    if (window.turnstile) {
        renderWidget();
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    script.async = true;
    script.defer = true;
    script.onload = renderWidget;
    document.head.appendChild(script);
});

function submitLogin() {
    form.post(route('admin.login'), {
        onFinish: () => {
            form.reset('password');
            // token ของ Turnstile ใช้ได้ครั้งเดียว — reset widget ให้พร้อมสำหรับความพยายามครั้งถัดไป
            if (widgetId) {
                window.turnstile?.reset(widgetId);
            }
        },
    });
}

const submit = () => {
    if (props.captcha?.enabled && widgetId) {
        window.turnstile?.execute(widgetId);
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

            <PrimaryButton class="w-full" :disabled="form.processing">
                เข้าสู่ระบบ
            </PrimaryButton>

            <div ref="turnstileContainer" class="flex justify-center"></div>
            <InputError :message="form.errors['cf-turnstile-response']" />
        </form>
    </AuthLayout>
</template>
