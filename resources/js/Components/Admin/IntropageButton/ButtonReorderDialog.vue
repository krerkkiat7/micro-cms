<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { GripVertical, Home, Link as LinkIcon } from 'lucide-vue-next';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { buttonDisplayTitle } from '@/utils/intropageButtons';
import type { LanguageOption } from '@/types';
import type { ButtonData } from '@/utils/intropageButtons';

/**
 * เหมือน PartReorderDialog.vue ของบทความ — เปิด dialog แสดงปุ่มทั้งหมดแบบย่อให้ลากสลับลำดับง่ายกว่าลาก
 * ตรง ๆ ในฟอร์ม ปุ่ม home ลากสลับตำแหน่งได้ปกติ (ลบไม่ได้เท่านั้น ดู ButtonCard.vue)
 */
const props = defineProps<{
    show: boolean;
    buttons: ButtonData[];
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    confirm: [order: ButtonData[]];
}>();

const workingOrder = ref<ButtonData[]>([]);

watch(
    () => props.show,
    (show) => {
        if (show) {
            workingOrder.value = [...props.buttons];
        }
    },
);

function confirm() {
    emit('confirm', workingOrder.value);
}

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
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-500/75" @click="emit('close')" />

                <div class="relative flex max-h-[80vh] w-full max-w-lg flex-col rounded-lg bg-white shadow-xl">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-800">จัดลำดับปุ่ม</h2>
                        <p class="mt-1 text-sm text-gray-500">ลากเพื่อสลับลำดับ แล้วกด "ยืนยันลำดับ" เพื่อบันทึกลำดับใหม่</p>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <draggable
                            :list="workingOrder"
                            item-key="_key"
                            handle=".reorder-drag-handle"
                            ghost-class="drag-ghost"
                            :animation="150"
                            class="space-y-1.5"
                        >
                            <template #item="{ element }">
                                <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm">
                                    <button type="button" class="reorder-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                                        <GripVertical class="size-4" />
                                    </button>
                                    <Home v-if="element.button_type === 'home'" class="size-4 shrink-0 text-gray-500" />
                                    <LinkIcon v-else class="size-4 shrink-0 text-gray-500" />
                                    <span class="truncate text-gray-700">{{ buttonDisplayTitle(element, languages) }}</span>
                                </div>
                            </template>
                        </draggable>

                        <p v-if="workingOrder.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีปุ่ม</p>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <PrimaryButton type="button" @click="confirm">ยืนยันลำดับ</PrimaryButton>
                        <SecondaryButton type="button" @click="emit('close')">ยกเลิก</SecondaryButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.drag-ghost {
    opacity: 0.4;
    background-color: #eff6ff;
    border: 2px dashed #93c5fd;
}
</style>
