<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { Contrast, Minus, Plus, RotateCcw } from 'lucide-vue-next';
import { useA11yPreferences } from '@/composables/useA11yPreferences';
import type { ContrastMode } from '@/composables/useA11yPreferences';
import { useFront } from '@/composables/useFront';

/**
 * เครื่องมือช่วยการเข้าถึง — ปรับขนาดตัวอักษร / การแสดงสี ตามตั้งค่า header (แสดง/ซ่อน + แบบไอคอนหรือข้อความ)
 * ค่าที่เลือกจำไว้ในเครื่องผู้ใช้ (composables/useA11yPreferences.ts)
 */
defineProps<{
    showFontSize: boolean;
    fontSizeDisplay: 'icon' | 'text';
    showContrast: boolean;
    contrastDisplay: 'icon' | 'text';
}>();

const { t } = useFront();
const prefs = useA11yPreferences();

const contrastModes: { value: ContrastMode; key: string }[] = [
    { value: 'normal', key: 'contrast_normal' },
    { value: 'high', key: 'contrast_high' },
    { value: 'grayscale', key: 'contrast_grayscale' },
];

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

const buttonClass = 'inline-flex min-h-8 min-w-8 items-center justify-center rounded border border-current/40 px-1.5 text-sm hover:bg-current/10 disabled:opacity-40';
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <div v-if="showFontSize" role="group" :aria-label="t('font_size')" class="flex items-center gap-1">
            <template v-if="fontSizeDisplay === 'icon'">
                <button type="button" :class="buttonClass" :disabled="!prefs.canDecrease()" :aria-label="t('font_smaller')" :title="t('font_smaller')" @click="prefs.decrease()">
                    <Minus class="size-4" aria-hidden="true" />
                </button>
                <button type="button" :class="buttonClass" :aria-label="t('font_normal')" :title="t('font_normal')" @click="prefs.reset()">
                    <span aria-hidden="true" class="font-semibold">A</span>
                </button>
                <button type="button" :class="buttonClass" :disabled="!prefs.canIncrease()" :aria-label="t('font_larger')" :title="t('font_larger')" @click="prefs.increase()">
                    <Plus class="size-4" aria-hidden="true" />
                </button>
            </template>
            <template v-else>
                <button
                    v-for="(size, index) in [13, 15, 18]"
                    :key="size"
                    type="button"
                    :class="buttonClass"
                    :style="{ fontSize: `${size}px` }"
                    :aria-pressed="prefs.fontLevel.value === index"
                    :aria-label="[t('font_normal'), t('font_larger'), `${t('font_larger')} +`][index]"
                    @click="prefs.setFontLevel(index)"
                >
                    <span aria-hidden="true">ก</span>
                </button>
            </template>
        </div>

        <div v-if="showContrast" class="relative" @focusout="onFocusOut" @keydown.esc.stop="closeAndFocus">
            <button ref="button" type="button" :class="buttonClass" class="gap-1" :aria-expanded="open" aria-controls="front-contrast-menu" :aria-label="contrastDisplay === 'icon' ? t('contrast') : undefined" @click="open = !open">
                <Contrast v-if="contrastDisplay === 'icon'" class="size-4" aria-hidden="true" />
                <span v-else>{{ t('contrast') }}</span>
            </button>
            <ul v-show="open" id="front-contrast-menu" class="absolute right-0 top-full z-50 mt-1 min-w-44 rounded-lg border border-gray-200 bg-white py-1 text-gray-800 shadow-lg">
                <li v-for="mode in contrastModes" :key="mode.value">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-gray-100"
                        :aria-pressed="prefs.contrast.value === mode.value"
                        @click="prefs.setContrast(mode.value)"
                    >
                        <span class="size-2 rounded-full" :class="prefs.contrast.value === mode.value ? 'bg-brand-600' : 'bg-gray-300'" aria-hidden="true" />
                        {{ t(mode.key) }}
                    </button>
                </li>
                <li v-if="prefs.fontLevel.value !== 0" class="border-t border-gray-100">
                    <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-gray-100" @click="prefs.reset()">
                        <RotateCcw class="size-3.5" aria-hidden="true" /> {{ t('font_normal') }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
