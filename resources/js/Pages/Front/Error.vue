<script setup lang="ts">
import { computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import IntroLayout from '@/Layouts/Front/IntroLayout.vue';
import { useFront } from '@/composables/useFront';
import type { SeoData } from '@/utils/front';

/**
 * หน้า error ของหน้าบ้าน (ทุก 4xx/5xx) — App\Support\Front\FrontErrorPage
 * layout เปล่า: โลโก้ + ชื่อเว็บ, รหัสสถานะ, หัวข้อ/คำอธิบายตามภาษาของ URL, ปุ่มกลับหน้าแรก (+ ปุ่มรองตามสถานะ), รหัสอ้างอิง (5xx), ลิงก์ภาษาอื่น
 * 5xx บอกแค่ว่าเกิดข้อผิดพลาด ไม่แสดงสาเหตุจริง — ผู้ดูแลค้นรายละเอียดใน storage/logs/error-*.log ด้วยรหัสอ้างอิง
 * ลิงก์ทั้งหมดเป็น <a> ธรรมดา (โหลดหน้าเต็ม) — หลังเกิด error ให้เริ่มหน้าใหม่ทั้งหมด ไม่ต่อ state เดิมของ Inertia
 */
const props = defineProps<{
    status: number;
    reference: string | null;
    homeUrl: string;
    seo: SeoData;
}>();

const { front, t } = useFront();

const KNOWN = [400, 403, 404, 405, 410, 413, 419, 429, 500, 503];
const key = computed(() => (KNOWN.includes(props.status) ? String(props.status) : props.status >= 500 ? '5xx' : '4xx'));

const title = computed(() => t(`error_title.${key.value}`));
const description = computed(() => t(`error_description.${key.value}`));

// ปุ่มรอง: หน้าที่ไม่มี/ไม่มีสิทธิ์ = ย้อนกลับ, ปัญหาชั่วคราว = ลองอีกครั้ง (โหลดหน้าเดิมใหม่)
const secondary = computed<'back' | 'reload' | null>(() => {
    if ([419, 429, 503].includes(props.status) || props.status >= 500) return 'reload';
    if ([403, 404, 410].includes(props.status)) return 'back';
    return null;
});

const otherLanguages = computed(() => front.value.languages.filter((code) => code !== front.value.lang));

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = props.homeUrl;
    }
}

function reload(): void {
    window.location.reload();
}
</script>

<template>
    <IntroLayout :seo="seo">
        <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12">
            <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white px-6 py-10 text-center shadow-sm sm:px-10">
                <a :href="homeUrl" class="inline-flex flex-col items-center gap-3 text-gray-900">
                    <img v-if="front.site.logoUrl" :src="front.site.logoUrl" alt="" class="h-14 w-auto max-w-[12rem] object-contain" />
                    <ApplicationLogo v-else class="size-12 stroke-current text-brand-700" aria-hidden="true" />
                    <span class="text-lg font-semibold">{{ front.site.name }}</span>
                </a>

                <p class="mt-8 text-6xl font-bold tracking-tight text-brand-700 tabular-nums" aria-hidden="true">{{ status }}</p>
                <h1 class="mt-3 text-2xl font-bold text-gray-900">{{ title }}</h1>
                <p class="mt-3 text-gray-600">{{ description }}</p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a :href="homeUrl" class="inline-flex items-center rounded-md bg-brand-700 px-5 py-2.5 font-medium text-white hover:bg-brand-800">
                        {{ t('back_to_home') }}
                    </a>
                    <button
                        v-if="secondary"
                        type="button"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-5 py-2.5 font-medium text-gray-700 hover:bg-gray-50"
                        @click="secondary === 'back' ? goBack() : reload()"
                    >
                        {{ secondary === 'back' ? t('go_back') : t('try_again') }}
                    </button>
                </div>

                <div v-if="reference" class="mt-8 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    <p>
                        {{ t('error_reference') }}:
                        <span class="font-mono font-semibold text-gray-900 select-all">{{ reference }}</span>
                    </p>
                    <p class="mt-1 text-xs text-gray-500">{{ t('error_reference_hint') }}</p>
                </div>
            </div>

            <nav v-if="otherLanguages.length" class="mt-6 flex items-center gap-2 text-sm text-gray-500" :aria-label="t('error_other_languages')">
                <span>{{ t('error_other_languages') }}:</span>
                <a v-for="code in otherLanguages" :key="code" :href="`/${code}`" :hreflang="code" :lang="code" class="text-brand-700 hover:text-brand-800">
                    {{ t(`languages.${code}`) }}
                </a>
            </nav>
        </div>
    </IntroLayout>
</template>
