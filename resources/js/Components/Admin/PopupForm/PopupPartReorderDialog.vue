<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { EyeOff, GripVertical } from 'lucide-vue-next';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { POPUP_PART_TYPE_LABELS, popupPartSummary } from '@/utils/popupForm';
import type { LanguageOption } from '@/types';
import type { PopupPart } from '@/utils/popupForm';

/**
 * dialog เรียงลำดับข้อมูล (part) ของ popup — เทียบเคียง ArticlePart/PartReorderDialog.vue
 * ลากสลับบนสำเนา ลำดับจริงเปลี่ยนเมื่อกด "ยืนยันลำดับ" เท่านั้น
 */
const props = defineProps<{
    show: boolean;
    parts: PopupPart[];
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    confirm: [order: PopupPart[]];
}>();

const workingOrder = ref<PopupPart[]>([]);

watch(
    () => props.show,
    (show) => {
        if (show) {
            workingOrder.value = [...props.parts];
        }
    },
);

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
                        <h2 class="text-base font-semibold text-gray-800">จัดลำดับข้อมูล</h2>
                        <p class="mt-1 text-sm text-gray-500">ลากเพื่อสลับลำดับ แล้วกด "ยืนยันลำดับ" เพื่อใช้ลำดับใหม่</p>
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
                            <template #item="{ element, index }">
                                <div
                                    class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm"
                                    :class="element.status === 'N' ? 'opacity-60' : ''"
                                >
                                    <button type="button" class="reorder-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                                        <GripVertical class="size-4" />
                                    </button>
                                    <span class="shrink-0 text-gray-400">{{ index + 1 }}.</span>
                                    <span class="shrink-0 font-medium text-gray-600">{{ POPUP_PART_TYPE_LABELS[(element as PopupPart).part_type] }}</span>
                                    <span class="shrink-0 text-gray-400">:</span>
                                    <span class="truncate text-gray-700">{{ popupPartSummary(element, languages) }}</span>
                                    <EyeOff v-if="element.status === 'N'" class="ml-auto size-4 shrink-0 text-gray-400" title="ซ่อนอยู่" />
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <SecondaryButton type="button" @click="emit('close')">ยกเลิก</SecondaryButton>
                        <PrimaryButton type="button" @click="emit('confirm', workingOrder)">ยืนยันลำดับ</PrimaryButton>
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
