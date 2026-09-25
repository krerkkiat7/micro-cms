<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useFront } from '@/composables/useFront';

/**
 * ลิงก์ของหน้าบ้าน — URL ภายในเว็บ (โดเมนเดียวกัน ไม่เปิดหน้าต่างใหม่) ใช้ Inertia <Link> (เปลี่ยนหน้าไม่โหลดใหม่ทั้งหน้า),
 * นอกนั้นใช้ <a> ธรรมดา; เปิดหน้าต่างใหม่ใส่ rel="noopener noreferrer" + ข้อความแจ้งสำหรับ screen reader (WCAG 3.2.5)
 * `native` = บังคับโหลดหน้าเต็ม (เช่น สลับภาษา — ให้ <html lang> และ meta ฝั่ง server ถูกต้อง)
 */
const props = defineProps<{
    href: string;
    target?: string | null;
    native?: boolean;
}>();

const { t } = useFront();

const newWindow = computed(() => props.target === '_blank');

const internal = computed(() => {
    if (props.native || newWindow.value) return false;
    if (props.href.startsWith('/') && !props.href.startsWith('//')) return true;

    try {
        return typeof window !== 'undefined' && new URL(props.href).origin === window.location.origin;
    } catch {
        return false;
    }
});
</script>

<template>
    <Link v-if="internal" :href="href"><slot /></Link>
    <a v-else :href="href" :target="newWindow ? '_blank' : undefined" :rel="newWindow ? 'noopener noreferrer' : undefined">
        <slot />
        <span v-if="newWindow" class="sr-only"> {{ t('opens_new_window') }}</span>
    </a>
</template>
