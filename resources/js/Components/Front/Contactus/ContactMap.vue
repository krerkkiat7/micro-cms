<script setup lang="ts">
import { computed } from 'vue';
import { Download, Navigation } from 'lucide-vue-next';
import { useFront } from '@/composables/useFront';
import type { FrontContactusData } from '@/utils/frontContactus';

/**
 * รูปแผนที่ + Google Map (iframe จากพิกัด) — แสดงเฉพาะส่วนที่ตั้งค่าแสดงไว้
 * - รูปแผนที่: คลิกแล้วดาวน์โหลดไฟล์ มีแถบข้อความ "คลิกเพื่อดาวน์โหลด" คาดด้านล่างบนรูป (พื้นหลังทึบให้อ่านชัด)
 * - Google Map: กล่องรายละเอียดสถานที่มุมซ้ายบน (ชื่อเจ้าของ + ที่อยู่ จากตั้งค่าระบบ) + ปุ่มเส้นทาง (เปิดหน้าใหม่ใน Google Maps)
 */
defineProps<{
    contactus: FrontContactusData;
}>();

const { front, t } = useFront();

const placeName = computed(() => front.value.contact.owner || front.value.site.name);
</script>

<template>
    <div class="space-y-4">
        <figure v-if="contactus.mapImage">
            <a
                :href="contactus.mapImage.downloadUrl"
                download
                class="group relative block overflow-hidden rounded-lg border border-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
            >
                <img :src="contactus.mapImage.url" :alt="t('contactus_form.map')" class="w-full" loading="lazy" />
                <span
                    class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-gray-900/70 px-4 py-2.5 text-sm font-medium text-white transition-colors group-hover:bg-gray-900/85"
                >
                    <Download class="size-4" aria-hidden="true" />
                    {{ t('contactus_form.download_map') }}
                </span>
            </a>
        </figure>

        <div v-if="contactus.googleMap" class="relative aspect-[4/3] w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-100 sm:aspect-video">
            <iframe
                :src="contactus.googleMap.embedUrl"
                :title="t('contactus_form.google_map')"
                class="size-full"
                style="border: 0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            />

            <!-- กล่องรายละเอียดสถานที่ (มุมซ้ายบน แบบเดียวกับการ์ดของ Google Maps) -->
            <div class="absolute left-2.5 top-2.5 flex max-w-[calc(100%-1.25rem)] items-start gap-3 rounded-md bg-white p-3 shadow-md sm:max-w-xs">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-900" :title="placeName">{{ placeName }}</p>
                    <p v-if="front.contact.address" class="mt-0.5 line-clamp-3 whitespace-pre-line text-xs text-gray-600">{{ front.contact.address }}</p>
                </div>
                <a
                    :href="contactus.googleMap.directionsUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex shrink-0 flex-col items-center gap-0.5 text-xs font-medium text-sky-700 hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                    :aria-label="`${t('contactus_form.directions_to', { name: placeName })} ${t('opens_new_window')}`"
                    :title="t('contactus_form.directions_to', { name: placeName })"
                >
                    <span class="flex size-9 items-center justify-center rounded-full bg-sky-600 text-white">
                        <Navigation class="size-4 rotate-45" aria-hidden="true" />
                    </span>
                    {{ t('contactus_form.directions') }}
                </a>
            </div>
        </div>
    </div>
</template>
