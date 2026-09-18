<script setup lang="ts">
import { ref } from 'vue';
import { Plus } from 'lucide-vue-next';
import ButtonCard from './ButtonCard.vue';
import ButtonReorderDialog from './ButtonReorderDialog.vue';
import { createOtherButton } from '@/utils/intropageButtons';
import type { ButtonData } from '@/utils/intropageButtons';
import type { LanguageOption } from '@/types';

const props = defineProps<{
    languages: LanguageOption[];
    formErrors: Record<string, string>;
}>();

const buttons = defineModel<ButtonData[]>({ required: true });

function addButton() {
    buttons.value.push(createOtherButton(props.languages));
}

function removeButton(index: number) {
    // ปุ่ม home ไม่มีให้กดลบอยู่แล้ว (ดู ButtonCard.vue) กันไว้อีกชั้นไม่ให้ลบโดยไม่ตั้งใจ
    if (buttons.value[index]?.button_type === 'home') {
        return;
    }

    buttons.value.splice(index, 1);
}

// เรียงลำดับผ่าน dialog เหมือนเนื้อหาแบบ part ของบทความ — ปุ่ม home ลากสลับตำแหน่งได้ปกติ แค่ลบไม่ได้
const showReorder = ref(false);

function applyOrder(order: ButtonData[]) {
    buttons.value = order;
    showReorder.value = false;
}
</script>

<template>
    <div class="space-y-4">
        <div v-for="(button, index) in buttons" :key="button._key">
            <ButtonCard
                :button="button"
                :languages="languages"
                :error-prefix="`buttons.${index}`"
                :form-errors="formErrors"
                @remove="removeButton(index)"
                @reorder="showReorder = true"
            />
        </div>

        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
            @click="addButton"
        >
            <Plus class="size-3.5" /> เพิ่มปุ่ม
        </button>

        <ButtonReorderDialog :show="showReorder" :buttons="buttons" :languages="languages" @close="showReorder = false" @confirm="applyOrder" />
    </div>
</template>
