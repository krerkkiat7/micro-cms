<script setup lang="ts">
import { computed } from 'vue';
import type { CSSProperties } from 'vue';
import { aspectRatio, fontCss, positionClasses } from '@/utils/front';
import type { HeroData } from '@/utils/front';

/**
 * ส่วนหัวของหน้าภายใน ตามเมนูที่ชี้มาหน้านี้ (front_menu_info — รูปภาพส่วนหัว + หัวเรื่อง/หัวเรื่องรองพร้อมสไตล์ + ตำแหน่ง 9 ทิศ)
 * ข้อความเป็น <p> (ไม่ใช่หัวเรื่อง) — หัวเรื่องหลัก (h1) ของหน้าอยู่ในเนื้อหาแต่ละหน้า ใช้ h1 ได้ครั้งเดียวต่อหน้า
 * มีรูป: ข้อความซ้อนบนรูป (มีเงาข้อความให้อ่านออก), ไม่มีรูป: แถบหัวเรื่องธรรมดา
 */
const props = defineProps<{ hero: HeroData }>();

const hasText = computed(() => props.hero.title !== '' || props.hero.subtitle !== '');
const ratio = computed(() => aspectRatio(props.hero.image_aspect_ratio));
const frameStyle = computed<CSSProperties>(() => (ratio.value ? { aspectRatio: ratio.value, backgroundColor: props.hero.image_background } : {}));
</script>

<template>
    <div v-if="hero.image_url || hasText" class="relative w-full overflow-hidden">
        <div v-if="hero.image_url" class="relative w-full" :style="frameStyle">
            <img
                :src="hero.image_url"
                alt=""
                class="w-full"
                :class="ratio ? 'absolute inset-0 h-full' : 'h-auto'"
                :style="ratio ? { objectFit: hero.image_fit } : undefined"
                fetchpriority="high"
            />
        </div>

        <div v-if="hasText" :class="hero.image_url ? 'absolute inset-0' : 'py-8'">
            <div class="flex h-full px-4 py-6" :class="[positionClasses(hero.content_align), hero.use_container ? 'mx-auto max-w-7xl' : '']">
                <div class="max-w-3xl space-y-1" :class="hero.image_url ? '[text-shadow:0_1px_3px_rgba(0,0,0,0.45)]' : ''">
                    <p v-if="hero.title" class="leading-tight" :style="fontCss(hero.title_style)">{{ hero.title }}</p>
                    <p v-if="hero.subtitle" class="leading-snug" :style="fontCss(hero.subtitle_style)">{{ hero.subtitle }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
