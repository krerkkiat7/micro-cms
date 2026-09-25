<script setup lang="ts">
import { ChevronDown, Contrast, Minus, Plus, Search } from 'lucide-vue-next';
import LanguageFlag from '@/Components/Template/LanguageFlag.vue';
import SocialIcon from '@/Components/Template/SocialIcon.vue';
import { SOCIAL_LABELS } from '@/utils/template';
import type { HeaderZone, TemplatePreviewData } from '@/utils/template';

/**
 * ตัวอย่างเครื่องมือใน header — social / ค้นหา / ปรับขนาดตัวอักษร / การแสดงสี / ภาษา (ตามที่เปิดแสดงไว้)
 * `only` = กลุ่มที่ให้แสดงในตำแหน่งนี้ (แถบบนแยก social ไว้ซ้าย ส่วนเครื่องมืออื่นไว้ขวา; `aside` = ท้ายเมนูข้าง — ทุกอย่างยกเว้นค้นหา)
 */
defineProps<{
    zone: HeaderZone;
    preview: TemplatePreviewData;
    only: 'social' | 'tools' | 'all' | 'aside';
}>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-3 text-xs">
        <div v-if="only !== 'tools' && zone.social_status === 'Y'" class="flex items-center gap-2.5">
            <span v-for="key in preview.social.length ? preview.social : ['facebook', 'youtube', 'line']" :key="key" :title="SOCIAL_LABELS[key]">
                <SocialIcon :name="key" />
            </span>
        </div>

        <template v-if="only !== 'social'">
            <Search v-if="only !== 'aside' && zone.search_status === 'Y'" class="size-4" />

            <div v-if="zone.fontsize_status === 'Y'" class="flex items-center gap-1">
                <template v-if="zone.fontsize_display === 'icon'">
                    <span class="flex size-5 items-center justify-center rounded border border-current/40"><Minus class="size-3" /></span>
                    <span class="font-semibold">A</span>
                    <span class="flex size-5 items-center justify-center rounded border border-current/40"><Plus class="size-3" /></span>
                </template>
                <template v-else>
                    <span class="text-[11px]">ก</span>
                    <span class="text-[13px]">ก</span>
                    <span class="text-[15px]">ก</span>
                </template>
            </div>

            <div v-if="zone.contrast_status === 'Y'" class="flex items-center gap-1">
                <Contrast v-if="zone.contrast_display === 'icon'" class="size-4" />
                <span v-else>การแสดงสี</span>
            </div>

            <div v-if="zone.lang_status === 'Y'" class="flex items-center gap-1.5">
                <template v-if="zone.lang_select === 'all'">
                    <span
                        v-for="lang in preview.languages"
                        :key="lang"
                        class="flex items-center gap-1 uppercase"
                        :class="lang === preview.defaultLanguage ? 'font-semibold' : 'opacity-60'"
                    >
                        <LanguageFlag v-if="zone.lang_display !== 'code'" :code="lang" />
                        <template v-if="zone.lang_display !== 'flag'">{{ lang }}</template>
                    </span>
                </template>
                <span v-else class="flex items-center gap-1 rounded border border-current/40 px-1.5 py-0.5 uppercase">
                    <LanguageFlag v-if="zone.lang_display !== 'code'" :code="preview.defaultLanguage" />
                    <template v-if="zone.lang_display !== 'flag'">{{ preview.defaultLanguage }}</template>
                    <ChevronDown class="size-3" />
                </span>
            </div>
        </template>
    </div>
</template>
