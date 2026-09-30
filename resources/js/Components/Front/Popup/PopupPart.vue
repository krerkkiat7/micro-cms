<script setup lang="ts">
import FrontLink from '@/Components/Front/FrontLink.vue';
import { useFront } from '@/composables/useFront';
import type { FrontPopupPart } from '@/utils/front';

/**
 * ข้อมูล 1 รายการ (1 สไลด์) ของ popup — รูปภาพ + ข้อความ / รูปภาพ / ข้อความ
 * ขนาดรูป: เต็มความกว้าง / ใหญ่ 75% / กลาง 50% / เล็ก 33% (กึ่งกลาง); มีลิงก์ = รูปเป็นลิงก์ ส่วนข้อความอย่างเดียวมีลิงก์ "อ่านต่อ"
 * (ไม่ครอบข้อความทั้งก้อนด้วยลิงก์ เพราะ rich text อาจมีลิงก์ของตัวเองอยู่ข้างใน — ลิงก์ซ้อนกันไม่ถูกต้อง)
 */
defineProps<{ part: FrontPopupPart }>();

const emit = defineEmits<{ navigate: [] }>();

const { t } = useFront();

const SIZE_CLASS: Record<string, string> = {
    full: 'w-full',
    large: 'w-3/4',
    medium: 'w-1/2',
    small: 'w-1/3',
};
</script>

<template>
    <div class="space-y-4">
        <div v-if="part.image" class="mx-auto" :class="SIZE_CLASS[part.image_size] ?? 'w-full'">
            <FrontLink v-if="part.url" :href="part.url" :target="part.link_target" class="block" @click="emit('navigate')">
                <img :src="part.image.thumb_url ?? part.image.url" :alt="t('popup_image')" class="h-auto w-full rounded-md" />
            </FrontLink>
            <img v-else :src="part.image.thumb_url ?? part.image.url" :alt="t('popup_image')" class="h-auto w-full rounded-md" />
        </div>

        <!-- eslint-disable-next-line vue/no-v-html -- ผ่าน HtmlSanitizer ฝั่ง server แล้ว -->
        <div v-if="part.html" class="rich-text-content" v-html="part.html" />

        <p v-if="part.url && !part.image" class="text-center">
            <FrontLink :href="part.url" :target="part.link_target" class="font-medium text-brand-700" @click="emit('navigate')">
                {{ t('read_more') }}
            </FrontLink>
        </p>
    </div>
</template>
