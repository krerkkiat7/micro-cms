<script setup lang="ts">
import type { CSSProperties } from 'vue';
import { FileText, Play } from 'lucide-vue-next';
import { CUSTOMTEXT_PART_TYPE_LABELS } from '@/utils/pageWidgetCustomText';
import type { CustomTextPartData, CustomTextSetting } from '@/utils/pageWidgetCustomText';
import type { LanguageOption } from '@/types';

/**
 * ตัวอย่างการแสดงผลของ widget Custom Text ในหน้าโครงสร้าง — ต่างจาก Grid/Slideshow/Slideset ที่ดึงตัวอย่างจากหมวดหมู่ผ่าน
 * endpoint ตัวอย่าง (ajax) เนื้อหาของ Custom Text คือค่าที่กำลังแก้ไขอยู่แล้ว จึงแสดงจาก `setting.parts` ตรง ๆ ไม่ต้องเรียก endpoint
 * หัวเรื่องของแต่ละ part ใช้ `<div>` ที่นี่ (เหมือนหัวเรื่องอื่น ๆ ในหน้าโครงสร้าง — ที่หน้าบ้านของจริงจะใช้ `<h3>` แทน)
 */
const props = defineProps<{
    widgetType: string;
    setting: CustomTextSetting;
    languages: LanguageOption[];
}>();

function defaultLangTitle(part: CustomTextPartData): string {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;

    return (defaultLang ? part.detail[defaultLang]?.title : '')?.trim() ?? '';
}

function defaultLangDetail(part: CustomTextPartData): string {
    const defaultLang = props.languages.find((l) => l.is_default)?.code;

    return (defaultLang ? part.detail[defaultLang]?.detail : '')?.trim() ?? '';
}

function titleStyle(part: CustomTextPartData): CSSProperties {
    return {
        fontSize: `${part.title_font_size}px`,
        fontWeight: part.title_bold === 'Y' ? 700 : 400,
        fontFamily: `'${part.title_font_family}', sans-serif`,
        textAlign: part.title_align,
        color: part.title_color,
    };
}

function fileUrl(hashName: string): string {
    return route('admin.system.file.get', hashName);
}

const ALIGN_CLASS: Record<string, string> = { left: 'mr-auto', center: 'mx-auto', right: 'ml-auto' };
const SIZE_CLASS: Record<string, string> = { small: 'max-w-40', medium: 'max-w-72', large: 'max-w-md', full: 'w-full max-w-none' };
</script>

<template>
    <div class="space-y-4">
        <p v-if="setting.parts.length === 0" class="rounded-md border border-dashed border-gray-200 py-4 text-center text-xs text-gray-400">
            ยังไม่มีเนื้อหา ({{ widgetType }})
        </p>

        <div v-for="part in setting.parts" :key="part._key" :class="part.status === 'N' ? 'opacity-50' : ''">
            <div v-if="part.show_title === 'Y' && defaultLangTitle(part)" :style="titleStyle(part)" class="mb-2">
                {{ defaultLangTitle(part) }}
            </div>

            <!-- ข้อความ (rich text) -->
            <div
                v-if="part.part_type === 'text'"
                class="rich-text-content text-sm text-gray-700"
                v-html="defaultLangDetail(part) || '<p class=\'text-gray-400\'>(ยังไม่มีเนื้อหา)</p>'"
            />

            <!-- รูปภาพเดี่ยว -->
            <figure v-else-if="part.part_type === 'image'" :class="[ALIGN_CLASS[part.setting.alignment as string] ?? '', SIZE_CLASS[part.setting.size as string] ?? '']">
                <img
                    v-if="part.files[0]?.file[0]"
                    :src="fileUrl(part.files[0].file[0].hash_name)"
                    class="w-full rounded-md object-cover"
                    :alt="part.files[0].description[languages.find((l) => l.is_default)?.code ?? '']"
                />
                <div v-else class="flex aspect-video items-center justify-center rounded-md bg-gray-100 text-gray-300"><FileText class="size-8" /></div>
                <figcaption v-if="part.setting.show_caption && part.files[0]?.description" class="mt-1 text-center text-xs text-gray-500">
                    {{ part.files[0].description[languages.find((l) => l.is_default)?.code ?? ''] }}
                </figcaption>
            </figure>

            <!-- กลุ่มรูปภาพ (ภาพรวมแบบตาราง — ไม่จำลองรูปแบบสไลด์/กริดจริงเหมือนหน้าบ้าน) -->
            <div v-else-if="part.part_type === 'images'" class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                <div v-for="file in part.files" :key="file._key" class="aspect-square overflow-hidden rounded-md bg-gray-100">
                    <img v-if="file.file[0]" :src="fileUrl(file.file[0].hash_name)" class="size-full object-cover" />
                </div>
                <p v-if="part.files.length === 0" class="col-span-full py-3 text-center text-xs text-gray-400">ยังไม่มีรูปภาพ</p>
            </div>

            <!-- วิดีโอ -->
            <div
                v-else-if="part.part_type === 'video'"
                class="relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-gray-900"
                :class="[ALIGN_CLASS[part.setting.alignment as string] ?? '', SIZE_CLASS[part.setting.player_size as string] ?? '']"
            >
                <img v-if="part.files[0]?.cover_image[0]" :src="fileUrl(part.files[0].cover_image[0].hash_name)" class="absolute inset-0 size-full object-cover opacity-70" />
                <Play class="relative z-10 size-10 text-white" />
            </div>

            <p v-if="part.status === 'N'" class="mt-1 text-[11px] text-gray-400">({{ CUSTOMTEXT_PART_TYPE_LABELS[part.part_type] }} — ซ่อนอยู่)</p>
        </div>
    </div>
</template>
