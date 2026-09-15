<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import type { PageProps } from '@/types';

/**
 * โลโก้ของระบบ — ใช้แทน ApplicationLogo.vue ตรง ๆ ทุกจุดที่เคยแสดงโลโก้ (sidebar หลังบ้าน, หน้า auth,
 * header หน้าบ้าน) ตั้งค่าไว้ (sys_setting: site.logo_id) → แสดงรูปที่อัพโหลดจริงผ่าน route app.logo
 * ยังไม่ได้ตั้งค่า → ใช้ ApplicationLogo.vue (ไอคอน default เดิม) ต่อไปเหมือนเดิม
 *
 * ใช้ร่วมกันทั้งฝั่งแอดมินและหน้าบ้าน จึงวางไว้ระดับบนสุดของ Components (ไม่ผูกกับ Admin/ หรือ Front/)
 */
defineOptions({ inheritAttrs: false });

const appLogoUrl = computed(() => usePage<PageProps>().props.appLogoUrl);
</script>

<template>
    <img v-if="appLogoUrl" :src="appLogoUrl" alt="" class="object-contain" v-bind="$attrs" />
    <ApplicationLogo v-else v-bind="$attrs" />
</template>
