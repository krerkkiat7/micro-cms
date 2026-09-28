<script setup lang="ts">
import { Check, Link2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import SocialIcon from '@/Components/Template/SocialIcon.vue';
import { useFront } from '@/composables/useFront';

/**
 * ปุ่มแชร์บทความ: Facebook / X / LINE (เปิดหน้าแชร์ของแต่ละช่องทางในหน้าต่างใหม่) + คัดลอกลิงก์ (แจ้งผลผ่าน aria-live)
 * `url` = canonical ของบทความ (ไม่ใช่ URL ที่เปิดอยู่ ซึ่งอาจมี query ติดมา) — ซ่อนตอนพิมพ์
 */
const props = defineProps<{
    url: string;
    title: string;
}>();

const { t } = useFront();

const links = computed(() => {
    const url = encodeURIComponent(props.url);
    const text = encodeURIComponent(props.title);

    return [
        { key: 'facebook', label: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${url}`, class: 'bg-[#1877F2] hover:bg-[#0f63d1]' },
        { key: 'x', label: 'X', href: `https://twitter.com/intent/tweet?url=${url}&text=${text}`, class: 'bg-black hover:bg-gray-800' },
        { key: 'line', label: 'LINE', href: `https://social-plugins.line.me/lineit/share?url=${url}`, class: 'bg-[#06C755] hover:bg-[#05a647]' },
    ];
});

const copied = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

async function copyLink(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.url);
    } catch {
        // เบราว์เซอร์ที่ไม่อนุญาต clipboard API (เช่น ไม่ใช่ https) — ใช้ textarea ชั่วคราวแทน
        const input = document.createElement('textarea');
        input.value = props.url;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
    }

    copied.value = true;
    clearTimeout(timer);
    timer = setTimeout(() => (copied.value = false), 2500);
}

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="flex flex-wrap items-center gap-2 print:hidden">
        <span class="mr-1 text-sm font-medium text-gray-700">{{ t('share') }}:</span>
        <a
            v-for="link in links"
            :key="link.key"
            :href="link.href"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex size-9 items-center justify-center rounded-full text-white transition-colors"
            :class="link.class"
            :aria-label="`${t('share_on', { name: link.label })} ${t('opens_new_window')}`"
            :title="t('share_on', { name: link.label })"
        >
            <SocialIcon :name="link.key" />
        </a>
        <button
            type="button"
            class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 text-sm text-gray-700 transition-colors hover:bg-gray-50"
            @click="copyLink"
        >
            <component :is="copied ? Check : Link2" class="size-4" :class="copied ? 'text-green-600' : ''" aria-hidden="true" />
            {{ copied ? t('link_copied') : t('copy_link') }}
        </button>
        <span class="sr-only" aria-live="polite">{{ copied ? t('link_copied') : '' }}</span>
    </div>
</template>
