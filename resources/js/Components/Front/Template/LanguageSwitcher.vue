<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';
import LanguageFlag from '@/Components/Template/LanguageFlag.vue';
import { useFront } from '@/composables/useFront';
import type { SeoData } from '@/utils/front';

/**
 * ตัวสลับภาษาของ header — ลิงก์ไปหน้าเดียวกันในภาษาอื่น (URL จาก hreflang ของหน้า ถ้าไม่มีไปหน้าแรกของภาษานั้น)
 * โหลดหน้าเต็ม (ไม่ใช่ Inertia visit) เพื่อให้ <html lang> และ meta ฝั่ง server เป็นภาษาใหม่
 * แสดงแบบ "ทั้งหมด" (ลิงก์เรียงกัน) หรือ "เป็นตัวเลือก" (ปุ่ม disclosure + รายการ) ตามตั้งค่า header
 */
const props = defineProps<{
    display: 'code' | 'flag' | 'flag_code';
    select: 'all' | 'dropdown';
    seo?: SeoData | null;
    /** แสดงในแผงเมนูข้าง (รายการแนวตั้ง ไม่ลอย) */
    inline?: boolean;
}>();

const { front, t } = useFront();

const languages = computed(() =>
    front.value.languages.map((code) => ({
        code,
        label: (front.value.t.languages as Record<string, string> | undefined)?.[code] ?? code.toUpperCase(),
        url: props.seo?.alternates.find((a) => a.hreflang === code)?.href ?? `/${code}`,
        current: code === front.value.lang,
    })),
);

const open = ref(false);
const button = ref<HTMLButtonElement | null>(null);

function onFocusOut(event: FocusEvent): void {
    const next = event.relatedTarget as Node | null;
    if (!next || !(event.currentTarget as HTMLElement).contains(next)) open.value = false;
}

function closeAndFocus(): void {
    open.value = false;
    void nextTick(() => button.value?.focus());
}
</script>

<template>
    <nav :aria-label="t('change_language')">
        <ul v-if="select === 'all' || inline" class="flex flex-wrap items-center gap-2">
            <li v-for="lang in languages" :key="lang.code">
                <a
                    :href="lang.url"
                    :hreflang="lang.code"
                    :lang="lang.code"
                    class="inline-flex items-center gap-1 rounded px-1.5 py-1 text-sm uppercase hover:underline"
                    :class="lang.current ? 'font-bold underline underline-offset-4' : 'opacity-80'"
                    :aria-current="lang.current ? 'true' : undefined"
                    :aria-label="lang.label"
                >
                    <LanguageFlag v-if="display !== 'code'" :code="lang.code" />
                    <span v-if="display !== 'flag'" aria-hidden="true">{{ lang.code }}</span>
                </a>
            </li>
        </ul>

        <div v-else class="relative" @focusout="onFocusOut" @keydown.esc.stop="closeAndFocus">
            <button
                ref="button"
                type="button"
                class="inline-flex items-center gap-1 rounded border border-current/40 px-2 py-1 text-sm uppercase"
                :aria-expanded="open"
                aria-controls="front-language-list"
                @click="open = !open"
            >
                <span class="sr-only">{{ t('language') }}: </span>
                <LanguageFlag v-if="display !== 'code'" :code="front.lang" />
                <span v-if="display !== 'flag'">{{ front.lang }}</span>
                <span v-else class="sr-only">{{ languages.find((l) => l.current)?.label }}</span>
                <ChevronDown class="size-3.5" aria-hidden="true" />
            </button>
            <ul v-show="open" id="front-language-list" class="absolute right-0 top-full z-50 mt-1 min-w-40 rounded-lg border border-gray-200 bg-white py-1 text-gray-800 shadow-lg">
                <li v-for="lang in languages" :key="lang.code">
                    <a
                        :href="lang.url"
                        :hreflang="lang.code"
                        :lang="lang.code"
                        class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-100"
                        :aria-current="lang.current ? 'true' : undefined"
                    >
                        <LanguageFlag :code="lang.code" />
                        <span class="flex-1">{{ lang.label }}</span>
                        <Check v-if="lang.current" class="size-4 text-brand-600" aria-hidden="true" />
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</template>
