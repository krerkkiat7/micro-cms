<script setup lang="ts">
import ErrorLayout from '@/Layouts/Admin/ErrorLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * หน้า error ของหลังบ้าน (ทุก 4xx/5xx) — App\Support\Admin\AdminErrorPage (ข้อความจาก lang/th/error.php)
 * 5xx บอกแค่ว่าเกิดข้อผิดพลาด + รหัสอ้างอิง ไม่แสดงสาเหตุจริง (รายละเอียดอยู่ใน storage/logs/error-*.log)
 * ลิงก์เป็น <a> ธรรมดา (โหลดหน้าเต็ม) — หลังเกิด error ให้เริ่มหน้าใหม่ทั้งหมด
 */
const props = defineProps<{
    status: number;
    title: string;
    description: string;
    reference: string | null;
    homeUrl: string;
    loginUrl: string;
    siteName: string;
}>();

// ข้อความปุ่ม (หลังบ้านเป็นภาษาไทยอย่างเดียว เหมือนหน้าอื่นของหลังบ้าน)
const secondary = computed<'back' | 'reload' | 'login' | null>(() => {
    if (props.status === 419) return 'login';
    if ([429, 503].includes(props.status) || props.status >= 500) return 'reload';
    if ([403, 404, 410].includes(props.status)) return 'back';
    return null;
});

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = props.homeUrl;
    }
}

function reload(): void {
    window.location.reload();
}
</script>

<template>
    <Head :title="title" />

    <ErrorLayout :site-name="siteName" :home-url="homeUrl" system-label="ระบบจัดการเนื้อหา">
        <p class="text-5xl font-bold tracking-tight text-brand-400 tabular-nums" aria-hidden="true">{{ status }}</p>
        <h1 class="mt-3 text-2xl font-semibold text-white">{{ title }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-gray-400">{{ description }}</p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a
                :href="homeUrl"
                class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-brand-600"
            >
                กลับหน้าแรกของระบบ
            </a>
            <a
                v-if="secondary === 'login'"
                :href="loginUrl"
                class="inline-flex items-center rounded-lg border border-admin-700 bg-transparent px-4 py-2.5 text-sm font-medium text-gray-200 hover:bg-admin-700"
            >
                เข้าสู่ระบบอีกครั้ง
            </a>
            <button
                v-else-if="secondary"
                type="button"
                class="inline-flex items-center rounded-lg border border-admin-700 bg-transparent px-4 py-2.5 text-sm font-medium text-gray-200 hover:bg-admin-700"
                @click="secondary === 'back' ? goBack() : reload()"
            >
                {{ secondary === 'back' ? 'ย้อนกลับ' : 'ลองอีกครั้ง' }}
            </button>
        </div>

        <div v-if="reference" class="mt-8 rounded-lg border border-admin-border bg-admin-900/60 px-4 py-3 text-sm text-gray-300">
            <p>
                รหัสอ้างอิง:
                <span class="font-mono font-semibold text-white select-all">{{ reference }}</span>
            </p>
            <p class="mt-1 text-xs text-gray-400">หากปัญหายังคงอยู่ กรุณาแจ้งรหัสนี้แก่ผู้ดูแลระบบ</p>
        </div>
    </ErrorLayout>
</template>
