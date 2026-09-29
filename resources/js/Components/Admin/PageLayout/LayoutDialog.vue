<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

/**
 * เปลือก dialog กลางของหน้า "โครงสร้าง" (Teleport + Transition แบบเดียวกับ ArticlePart/PartReorderDialog) —
 * dialog ตั้งค่าแถว/คอลัมน์/widget ใช้เปลือกนี้ร่วมกัน: ปุ่ม "ตกลง" ส่ง event `confirm` (ผู้ใช้ dialog เป็นคน apply ค่า
 * กลับ), "ยกเลิก"/กดพื้นหลัง/Esc ส่ง `close`; slot `footer-left` ไว้วางปุ่มลบ
 */
const props = withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        description?: string;
        /** ความกว้างสูงสุด: md = max-w-lg, lg = max-w-3xl */
        size?: 'md' | 'lg';
        confirmText?: string;
        /** true = ปุ่มยืนยันกดไม่ได้ (เช่น ยังไม่ได้เลือกตัวเลือก) */
        confirmDisabled?: boolean;
    }>(),
    { size: 'lg', confirmText: 'ตกลง', description: undefined, confirmDisabled: false },
);

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show) {
        emit('close');
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-500/75" @click="emit('close')" />

                <div
                    class="relative flex max-h-[90vh] w-full flex-col rounded-lg bg-white shadow-xl"
                    :class="size === 'md' ? 'max-w-lg' : 'max-w-3xl'"
                >
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-800">{{ title }}</h2>
                        <p v-if="description" class="mt-1 text-sm text-gray-500">{{ description }}</p>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <slot />
                    </div>

                    <div class="flex items-center gap-3 border-t border-gray-100 px-6 py-4">
                        <slot name="footer-left" />
                        <div class="ml-auto flex gap-3">
                            <PrimaryButton type="button" :disabled="confirmDisabled" @click="emit('confirm')">{{ confirmText }}</PrimaryButton>
                            <SecondaryButton type="button" @click="emit('close')">ยกเลิก</SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
