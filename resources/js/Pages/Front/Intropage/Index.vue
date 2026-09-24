<script setup lang="ts">
import { computed } from 'vue';
import FrontLink from '@/Components/Front/FrontLink.vue';
import IntroLayout from '@/Layouts/Front/IntroLayout.vue';
import { useFront } from '@/composables/useFront';
import { backgroundCss } from '@/utils/front';
import type { FrontBackground, FrontFileData, SeoData } from '@/utils/front';

/**
 * Intropage (หน้าคั่นก่อนเข้าเว็บ) — สื่อหลัก (รูป / วิดีโอไฟล์ / วิดีโอจาก URL / YouTube) ตามขนาดที่ตั้งไว้ + ข้อความต้อนรับ + ปุ่ม
 * ปุ่ม "เข้าหน้าแรก" ไปหน้าแรกตามเมนู (is_home) เสมอ, ปุ่มอื่นไป URL ที่ตั้งไว้ — ดู docs/PRD-intropage.md
 */
interface IntroButton {
    id: number;
    type: 'home' | 'other';
    display: 'text' | 'image';
    text: string;
    image_url: string | null;
    url: string | null;
    target: '_self' | '_blank';
    background_color: string | null;
    text_color: string | null;
}

const props = defineProps<{
    intro: {
        id: number;
        title: string;
        detail: string;
        display_type: 'image' | 'vdo' | 'vdourl' | 'youtubeurl';
        display_size: string;
        image: FrontFileData | null;
        video_url: string | null;
        youtube_id: string | null;
        background: FrontBackground;
        show_button: boolean;
        buttons: IntroButton[];
    };
    homeUrl: string;
    seo: SeoData;
}>();

const { front, t } = useFront();

/** ขนาดสื่อหลัก: screen_* = เทียบความกว้างหน้าจอ, container_* = เทียบ container เนื้อหา */
const mediaWrapperClass = computed(() => (props.intro.display_size.startsWith('container') ? 'mx-auto w-full max-w-6xl px-4' : 'w-full'));
const mediaWidth = computed(() => {
    const percent = Number(props.intro.display_size.split('_')[1] ?? 100);

    return { width: `${[25, 50, 75, 100].includes(percent) ? percent : 100}%` };
});

const buttons = computed(() =>
    props.intro.buttons
        .map((button) => ({ ...button, href: button.type === 'home' ? props.homeUrl : button.url }))
        .filter((button) => button.href),
);

function buttonLabel(button: IntroButton): string {
    return button.text || (button.type === 'home' ? t('enter_site') : '');
}
</script>

<template>
    <IntroLayout :seo="seo">
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 py-10" :style="backgroundCss(intro.background)">
            <h1 :class="intro.title ? 'px-4 text-center text-2xl font-bold sm:text-3xl' : 'sr-only'">{{ intro.title || front.site.name }}</h1>

            <div :class="mediaWrapperClass">
                <div class="mx-auto" :style="mediaWidth">
                    <img
                        v-if="intro.display_type === 'image' && intro.image"
                        :src="intro.image.url"
                        :alt="intro.title"
                        class="h-auto w-full"
                        fetchpriority="high"
                    />

                    <video
                        v-else-if="(intro.display_type === 'vdo' || intro.display_type === 'vdourl') && intro.video_url"
                        :src="intro.video_url"
                        class="h-auto w-full"
                        controls
                        autoplay
                        muted
                        playsinline
                        :aria-label="intro.title || t('video')"
                    />

                    <div v-else-if="intro.display_type === 'youtubeurl' && intro.youtube_id" class="relative aspect-video w-full overflow-hidden">
                        <iframe
                            :src="`https://www.youtube-nocookie.com/embed/${intro.youtube_id}?rel=0`"
                            :title="intro.title || t('youtube_title')"
                            class="absolute inset-0 size-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                        />
                    </div>
                </div>
            </div>

            <p v-if="intro.detail" class="max-w-3xl whitespace-pre-line px-4 text-center text-lg leading-relaxed">{{ intro.detail }}</p>

            <div v-if="intro.show_button && buttons.length" class="flex flex-wrap items-center justify-center gap-3 px-4">
                <FrontLink
                    v-for="button in buttons"
                    :key="button.id"
                    :href="button.href!"
                    :target="button.target"
                    class="inline-flex min-h-11 items-center justify-center rounded-lg font-medium transition-opacity hover:opacity-90 focus-visible:outline-3 focus-visible:outline-offset-2"
                    :class="button.display === 'image' ? '' : 'px-6 py-2.5 shadow'"
                    :style="button.display === 'text' ? { backgroundColor: button.background_color ?? '#465fff', color: button.text_color ?? '#ffffff' } : undefined"
                >
                    <img v-if="button.display === 'image' && button.image_url" :src="button.image_url" :alt="buttonLabel(button)" class="max-h-20 w-auto" />
                    <template v-else>{{ buttonLabel(button) }}</template>
                </FrontLink>
            </div>
        </div>
    </IntroLayout>
</template>
