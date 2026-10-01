<script setup lang="ts">
import type { CSSProperties } from 'vue';
import { Download, FileText } from 'lucide-vue-next';
import PartImages from '@/Components/Front/ContentPart/PartImages.vue';
import { useFront } from '@/composables/useFront';
import { formatBytes } from '@/utils/front';
import type { FrontPart } from '@/utils/front';

/**
 * เนื้อหาแบบแบ่ง part — ใช้ร่วมกันระหว่างรายละเอียดบทความ (article_item_part — หัวข้อ part = h2) และ widget Custom Text ของหน้าเพจ
 * (หัวข้อใต้หัวเรื่องของ widget) ประเภท: ข้อความ (rich text ที่ผ่าน HtmlSanitizer ฝั่ง server แล้ว), รูปภาพเดี่ยว, กลุ่มรูปภาพ (7 รูปแบบ),
 * วิดีโอ (ไฟล์ / YouTube แบบ youtube-nocookie), เอกสารเดี่ยว/กลุ่มเอกสาร (ดาวน์โหลด + ตัวอย่าง PDF)
 */
defineProps<{
    parts: FrontPart[];
    /** แท็กหัวข้อของแต่ละ part (บทความ = h2) */
    headingTag: string;
}>();

const { t } = useFront();

const ALIGN: Record<string, string> = { left: 'mr-auto', center: 'mx-auto', right: 'ml-auto' };
const SIZE: Record<string, string> = { small: 'max-w-xs', medium: 'max-w-md', large: 'max-w-2xl', full: 'w-full max-w-none' };

function titleCss(part: FrontPart): CSSProperties | undefined {
    const s = part.title_style;

    if (!s) return undefined;

    return { fontSize: `${s.font_size}px`, fontWeight: s.bold ? 700 : 400, fontFamily: `'${s.font_family}', sans-serif`, textAlign: s.align, color: s.color };
}
</script>

<template>
    <div class="space-y-8">
        <section v-for="part in parts" :key="part.id" :aria-labelledby="part.title ? `part-${part.id}` : undefined">
            <component :is="headingTag" v-if="part.title" :id="`part-${part.id}`" class="mb-3 text-xl font-semibold leading-snug text-gray-900" :style="titleCss(part)">
                {{ part.title }}
            </component>

            <!-- ข้อความ -->
            <div v-if="part.type === 'text'" class="front-prose" v-html="part.html" />

            <!-- รูปภาพเดี่ยว -->
            <figure v-else-if="part.type === 'image' && part.files[0]?.file" :class="[ALIGN[part.setting.alignment], SIZE[part.setting.size]]">
                <img :src="part.files[0].file.thumb_url ?? part.files[0].file.url" :alt="part.files[0].alt" class="h-auto w-full rounded-md" loading="lazy" />
                <figcaption v-if="part.setting.show_caption && part.files[0].alt" class="mt-2 text-center text-sm text-gray-600">{{ part.files[0].alt }}</figcaption>
            </figure>

            <!-- กลุ่มรูปภาพ -->
            <PartImages v-else-if="part.type === 'images'" :part="part" />

            <!-- วิดีโอ -->
            <template v-else-if="part.type === 'video'">
                <div v-for="(row, index) in part.files" :key="index" :class="[ALIGN[part.setting.alignment], SIZE[part.setting.player_size]]">
                    <div v-if="row.video_type === 'youtube' && row.youtube_id" class="relative aspect-video overflow-hidden rounded-md bg-black">
                        <iframe
                            :src="`https://www.youtube-nocookie.com/embed/${row.youtube_id}?rel=0${row.autoplay ? '&autoplay=1&mute=1' : ''}${row.controls ? '' : '&controls=0'}`"
                            :title="part.title || t('youtube_title')"
                            class="absolute inset-0 size-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                        />
                    </div>
                    <video
                        v-else-if="row.file"
                        :src="row.file.url"
                        :poster="row.cover?.url"
                        class="h-auto w-full rounded-md bg-black"
                        preload="metadata"
                        playsinline
                        :controls="row.controls || !row.autoplay"
                        :autoplay="row.autoplay"
                        :muted="row.autoplay"
                        :aria-label="part.title || t('video')"
                    />
                </div>
            </template>

            <!-- เอกสาร -->
            <ul v-else-if="part.type === 'document' || part.type === 'documents'" class="space-y-3">
                <li v-for="(row, index) in part.files.filter((f) => f.file)" :key="index" class="rounded-lg border border-gray-200 bg-white">
                    <div class="flex flex-wrap items-center gap-3 p-3">
                        <FileText class="size-8 shrink-0 text-brand-600" aria-hidden="true" />
                        <div class="min-w-0 flex-1">
                            <p class="break-words font-medium text-gray-900">{{ row.file!.name }}</p>
                            <p v-if="row.show_file_size" class="text-sm text-gray-600">
                                <span class="uppercase">{{ row.file!.extension }}</span> · {{ t('file_size') }} {{ formatBytes(row.file!.file_size) }}
                            </p>
                        </div>
                        <a :href="row.file!.download_url" class="inline-flex items-center gap-1.5 rounded-md bg-brand-700 px-3 py-2 text-sm font-medium text-white hover:bg-brand-800" download>
                            <Download class="size-4" aria-hidden="true" />
                            {{ t('download') }}<span class="sr-only"> {{ row.file!.name }}</span>
                        </a>
                    </div>
                    <iframe
                        v-if="row.pdf_preview && row.file!.extension?.toLowerCase() === 'pdf'"
                        :src="row.file!.url"
                        :title="t('pdf_preview', { name: row.file!.name })"
                        class="h-[70vh] w-full border-t border-gray-200"
                        loading="lazy"
                    />
                </li>
            </ul>
        </section>
    </div>
</template>
