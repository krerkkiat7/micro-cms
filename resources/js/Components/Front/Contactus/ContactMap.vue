<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { useFront } from '@/composables/useFront';
import type { FrontContactusData } from '@/utils/frontContactus';

/** รูปแผนที่ + Google Map (iframe จากพิกัด) — แสดงเฉพาะส่วนที่ตั้งค่าแสดงไว้ */
defineProps<{
    contactus: FrontContactusData;
}>();

const { t } = useFront();
</script>

<template>
    <div class="space-y-4">
        <figure v-if="contactus.mapImage">
            <a :href="contactus.mapImage.url" target="_blank" rel="noopener" :aria-label="`${t('contactus_form.map')} ${t('opens_new_window')}`">
                <img :src="contactus.mapImage.url" :alt="t('contactus_form.map')" class="w-full rounded-lg border border-gray-200" loading="lazy" />
            </a>
        </figure>

        <div v-if="contactus.googleMap">
            <div class="aspect-[4/3] w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-100 sm:aspect-video">
                <iframe
                    :src="contactus.googleMap.embedUrl"
                    :title="t('contactus_form.google_map')"
                    class="size-full"
                    style="border: 0"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                />
            </div>
            <a
                :href="contactus.googleMap.linkUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 inline-flex items-center gap-1 text-sm text-brand-700 hover:opacity-80"
            >
                {{ t('contactus_form.open_google_map') }}
                <ExternalLink class="size-3.5" aria-hidden="true" />
                <span class="sr-only">{{ t('opens_new_window') }}</span>
            </a>
        </div>
    </div>
</template>
