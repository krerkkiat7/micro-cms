<script setup lang="ts">
import { onMounted } from 'vue';
import SeoHead from '@/Components/Front/SeoHead.vue';
import { useAccessHeartbeat } from '@/composables/useAccessHeartbeat';
import { useFront } from '@/composables/useFront';
import type { SeoData } from '@/utils/front';

/**
 * layout เปล่าของหน้า Intropage — ไม่ใช้ template (sys_template) ใช้เฉพาะข้อมูลระบบร่วม (ชื่อไซต์/ภาษา/ข้อความส่วนติดต่อผู้ใช้)
 * มีลิงก์ข้ามไปเนื้อหาหลัก + landmark main + บันทึกการเข้าชม keep-alive เหมือน layout หน้าภายใน
 */
defineProps<{ seo: SeoData }>();

const { front, t } = useFront();

useAccessHeartbeat('front.access.ping');

onMounted(() => {
    document.documentElement.lang = front.value.lang;
});
</script>

<template>
    <div class="min-h-screen">
        <SeoHead :seo="seo" />

        <a
            href="#main-content"
            class="sr-only z-[60] rounded-md bg-brand-700 px-4 py-2 font-medium text-white focus:not-sr-only focus:fixed focus:left-3 focus:top-3"
        >
            {{ t('skip_to_content') }}
        </a>

        <main id="main-content" tabindex="-1" class="outline-none">
            <slot />
        </main>
    </div>
</template>
