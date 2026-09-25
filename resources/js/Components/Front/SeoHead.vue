<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
import type { SeoData } from '@/utils/front';

/**
 * <head> ของหน้าบ้าน — title / meta description / robots / canonical / hreflang / Open Graph / Twitter card (ผ่าน Inertia <Head>
 * ที่แทนแท็ก inertia="..." ที่ app.blade.php render ไว้ฝั่ง server) + JSON-LD (อัปเดต <script id="front-jsonld"> เองตอนเปลี่ยนหน้า
 * เพราะ Head ไม่จัดการเนื้อหาของ script) + stylesheet ฟอนต์ที่หน้านี้ใช้
 */
const props = defineProps<{
    seo: SeoData;
    /** URL stylesheet ฟอนต์ (template / หน้าเพจ) — ค่า null ถูกข้าม */
    fonts?: (string | null | undefined)[];
}>();

// title ว่าง = ใช้ชื่อไซต์อย่างเดียว (callback title ใน app.ts ต่อท้ายชื่อไซต์ให้เองเมื่อมี title)
const title = computed(() => (props.seo.title === props.seo.og.site_name ? '' : props.seo.title));
const fontUrls = computed(() => [...new Set((props.fonts ?? []).filter((url): url is string => !!url))]);

function syncJsonLd(): void {
    let script = document.getElementById('front-jsonld');

    if (!script) {
        script = document.createElement('script');
        script.id = 'front-jsonld';
        script.setAttribute('type', 'application/ld+json');
        document.head.appendChild(script);
    }

    script.textContent = JSON.stringify(props.seo.jsonLd);
}

onMounted(syncJsonLd);
watch(() => props.seo.jsonLd, syncJsonLd);
</script>

<template>
    <Head :title="title">
        <meta v-if="seo.description" head-key="description" name="description" :content="seo.description" />
        <meta v-if="seo.keywords" head-key="keywords" name="keywords" :content="seo.keywords" />
        <meta head-key="robots" name="robots" :content="seo.robots" />
        <link head-key="canonical" rel="canonical" :href="seo.canonical" />
        <link
            v-for="alternate in seo.alternates"
            :key="alternate.hreflang"
            :head-key="`alternate-${alternate.hreflang}`"
            rel="alternate"
            :hreflang="alternate.hreflang"
            :href="alternate.href"
        />
        <meta head-key="og:type" property="og:type" :content="seo.og.type" />
        <meta head-key="og:title" property="og:title" :content="seo.og.title" />
        <meta v-if="seo.og.description" head-key="og:description" property="og:description" :content="seo.og.description" />
        <meta head-key="og:url" property="og:url" :content="seo.og.url" />
        <meta head-key="og:site_name" property="og:site_name" :content="seo.og.site_name" />
        <meta head-key="og:locale" property="og:locale" :content="seo.og.locale" />
        <meta v-if="seo.og.image" head-key="og:image" property="og:image" :content="seo.og.image" />
        <meta head-key="twitter:card" name="twitter:card" :content="seo.twitter" />
        <link v-for="(url, index) in fontUrls" :key="url" :head-key="`front-font-${index}`" rel="stylesheet" :href="url" />
    </Head>
</template>
