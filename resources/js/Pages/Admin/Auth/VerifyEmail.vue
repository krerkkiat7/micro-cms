<script setup lang="ts">
import { computed } from 'vue';
import AuthLayout from '@/Layouts/Admin/AuthLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('admin.verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <AuthLayout
        title="ยืนยันอีเมล"
        description="กรุณายืนยันอีเมลโดยกดลิงก์ที่เราส่งไปให้ หากไม่ได้รับ กดปุ่มด้านล่างเพื่อส่งใหม่"
    >
        <Head title="ยืนยันอีเมล" />

        <div
            v-if="verificationLinkSent"
            class="mb-4 text-sm font-medium text-emerald-600"
        >
            ส่งลิงก์ยืนยันใหม่ไปที่อีเมลที่คุณใช้สมัครแล้ว
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <PrimaryButton class="w-full" :disabled="form.processing">
                ส่งอีเมลยืนยันอีกครั้ง
            </PrimaryButton>

            <Link
                :href="route('admin.logout')"
                method="post"
                as="button"
                class="block w-full text-center text-sm font-medium text-brand-600 hover:text-brand-700"
            >
                ออกจากระบบ
            </Link>
        </form>
    </AuthLayout>
</template>
