<script setup lang="ts">
import SocialIcon from '@/Components/Template/SocialIcon.vue';
import { useFront } from '@/composables/useFront';

/** ลิงก์ Social Media จากตั้งค่าระบบ (เฉพาะช่องทางที่ตั้งเป็น URL ไว้) — ไอคอนอย่างเดียว ชื่อช่องทางอยู่ใน aria-label */
const { front, t } = useFront();
</script>

<template>
    <ul v-if="front.social.length" class="flex flex-wrap items-center gap-3" :aria-label="t('social')">
        <li v-for="item in front.social" :key="item.key">
            <a
                :href="item.url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex size-8 items-center justify-center rounded-full transition-opacity hover:opacity-75"
                :aria-label="`${item.label} ${t('opens_new_window')}`"
                :title="item.label"
            >
                <SocialIcon :name="item.key" />
            </a>
        </li>
    </ul>
</template>
