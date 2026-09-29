<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { GripVertical } from 'lucide-vue-next';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PART_TYPE_ICONS, PART_TYPE_LABELS, partDisplayTitle } from '@/utils/articleParts';
import type { LanguageOption } from '@/types';
import type { PartData, PartType } from '@/utils/articleParts';

/**
 * แทนที่การลากสลับ part การ์ดใหญ่ ๆ ตรง ๆ ในหน้าฟอร์ม (ยากเวลา part ยาว ต้องเลื่อนไกล) ด้วย dialog
 * แสดงรายการ part ทั้งหมดแบบย่อ (แค่ [ประเภท] : [หัวเรื่อง]) ให้ลากสลับในนี้ง่ายกว่า — ลำดับจริงจะ
 * เปลี่ยนก็ต่อเมื่อกด "ยืนยันลำดับ" เท่านั้น (ยกเลิกได้โดยไม่กระทบลำดับเดิม)
 */
const props = defineProps<{
    show: boolean;
    parts: PartData[];
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    confirm: [order: PartData[]];
}>();

const workingOrder = ref<PartData[]>([]);

watch(
    () => props.show,
    (show) => {
        if (show) {
            workingOrder.value = [...props.parts];
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
                        <h2 class="text-base font-semibold text-gray-800">จัดลำดับเนื้อหา</h2>
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
                                    <component :is="PART_TYPE_ICONS[element.part_type as PartType]" class="size-4 shrink-0 text-gray-500" />
                                    <span class="shrink-0 font-medium text-gray-600">{{ PART_TYPE_LABELS[element.part_type as PartType] }}</span>
                                    <span class="shrink-0 text-gray-400">:</span>
                                    <span class="truncate text-gray-700">{{ partDisplayTitle(element, languages) }}</span>
                                </div>
                            </template>
                        </draggable>

                        <p v-if="workingOrder.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีเนื้อหา</p>
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
/* placeholder ที่ตำแหน่งที่จะวาง (เหมือนตัวอย่าง Simple List ของ SortableJS) ให้เห็นขอบเขตชัดเจน
ระหว่างลาก แยกจากรายการที่กำลังถูกลากอยู่ */
.drag-ghost {
    opacity: 0.4;
    background-color: #eff6ff;
    border: 2px dashed #93c5fd;
}
</style>

