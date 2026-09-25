<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import Breadcrumbs from '@/Components/Front/Breadcrumbs.vue';
import PageHero from '@/Components/Front/PageHero.vue';
import SeoHead from '@/Components/Front/SeoHead.vue';
import FrontAside from '@/Components/Front/Template/FrontAside.vue';
import FrontFooter from '@/Components/Front/Template/FrontFooter.vue';
import FrontHeader from '@/Components/Front/Template/FrontHeader.vue';
import { useA11yPreferences } from '@/composables/useA11yPreferences';
import { useAccessHeartbeat } from '@/composables/useAccessHeartbeat';
import { useFront } from '@/composables/useFront';
import { zoneBackgroundCss } from '@/utils/front';
import type { PageHeaderData, SeoData } from '@/utils/front';

/**
 * layout หน้าภายในของหน้าบ้าน — สร้างจาก template ที่เปิดใช้งาน (sys_template: header / aside / body / footer)
 * โซน body: สี/รูปพื้นหลังเต็มความกว้างเสมอ, ส่วนหัว (รูป + หัวเรื่องตามเมนู) + breadcrumb, แล้วเนื้อหา
 * — เนื้อหาอยู่ใน container ยกเว้น `fullWidth` (หน้าเพจ — กำหนดความกว้างต่อแถวเองอยู่แล้ว)
 *
 * WCAG: ลิงก์ "ข้ามไปยังเนื้อหาหลัก" เป็นอย่างแรกของหน้า, landmark header/nav/main/footer, <html lang> ตามภาษาของหน้า,
 * เครื่องมือขนาดตัวอักษร/การแสดงสี (useA11yPreferences) — บันทึกการเข้าชม keep-alive (useAccessHeartbeat)
 */
const props = defineProps<{
    seo: SeoData;
    header?: PageHeaderData | null;
    fullWidth?: boolean;
    /** stylesheet ฟอนต์เพิ่มเติมของหน้านี้ (เช่น ฟอนต์ที่หน้าเพจใช้) */
    fontsUrl?: string | null;
}>();

const { front, t } = useFront();

useA11yPreferences();
useAccessHeartbeat('front.access.ping');

const asideOpen = ref(false);
const template = computed(() => front.value.template);
const activeIds = computed(() => props.header?.activeMenuIds ?? []);

onMounted(() => {
    // เปลี่ยนภาษาแบบ Inertia visit ไม่ได้ render <html> ใหม่ — ตั้ง lang ให้ตรงภาษาของหน้าเสมอ
    document.documentElement.lang = front.value.lang;
});
</script>

<template>
    <div class="front-root flex min-h-screen flex-col bg-white text-gray-900">
        <SeoHead :seo="seo" :fonts="[front.fontsUrl, fontsUrl]" />

        <a
            href="#main-content"
            class="sr-only z-[60] rounded-md bg-brand-700 px-4 py-2 font-medium text-white focus:not-sr-only focus:fixed focus:left-3 focus:top-3"
        >
            {{ t('skip_to_content') }}
        </a>

        <FrontHeader
            v-if="template.header.status === 'Y'"
            :zone="template.header"
            :aside="template.aside"
            :active-ids="activeIds"
            :seo="seo"
            :aside-open="asideOpen"
            @open-aside="asideOpen = true"
        />

        <FrontAside v-model:open="asideOpen" :zone="template.aside" :header="template.header" :active-ids="activeIds" :seo="seo" />

        <main id="main-content" tabindex="-1" class="flex-1 outline-none" :style="zoneBackgroundCss(template.body)">
            <PageHero v-if="header?.hero" :hero="header.hero" />

            <div v-if="header?.showBreadcrumb" class="mx-auto max-w-7xl px-4 pt-5">
                <Breadcrumbs :items="header.breadcrumb" />
            </div>

            <div v-if="fullWidth" class="pb-10 pt-4">
                <slot />
            </div>
            <div v-else class="mx-auto max-w-7xl px-4 pb-12 pt-6">
                <slot />
            </div>
        </main>

        <FrontFooter v-if="template.footer.status === 'Y'" :zone="template.footer" />
    </div>
</template>
