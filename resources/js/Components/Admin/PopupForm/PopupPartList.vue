<script setup lang="ts">
import { computed, ref } from 'vue';
import { Plus } from 'lucide-vue-next';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import PopupPartCard from './PopupPartCard.vue';
import PopupPartReorderDialog from './PopupPartReorderDialog.vue';
import { createPopupPart } from '@/utils/popupForm';
import type { LanguageOption } from '@/types';
import type { PopupPart } from '@/utils/popupForm';

/**
 * รายการข้อมูล (part) ของ popup — แต่ละรายการแสดงเป็น 1 สไลด์ที่หน้าบ้าน
 * ลบต้องยืนยันก่อน, เรียงลำดับผ่าน dialog (เหมือน part ของบทความ)
 */
const props = defineProps<{
    languages: LanguageOption[];
    formErrors: Record<string, string | undefined>;
}>();

const parts = defineModel<PopupPart[]>({ required: true });

function addPart() {
    parts.value.push(createPopupPart(props.languages));
}

const pendingRemove = ref<number | null>(null);

function confirmRemove() {
    if (pendingRemove.value !== null) {
        parts.value.splice(pendingRemove.value, 1);
    }
    pendingRemove.value = null;
}

const showReorder = ref(false);

function applyOrder(order: PopupPart[]) {
    parts.value = order;
    showReorder.value = false;
}

const partsError = computed(() => props.formErrors.parts);
</script>

<template>
    <div class="space-y-4">
        <PopupPartCard
            v-for="(part, index) in parts"
            :key="part._key"
            :part="part"
            :index="index"
            :languages="languages"
            :form-errors="formErrors"
            @remove="pendingRemove = index"
            @reorder="showReorder = true"
        />

        <p v-if="parts.length === 0" class="rounded-xl border border-dashed border-gray-300 py-8 text-center text-sm text-gray-400">
            ยังไม่มีข้อมูล — กดปุ่ม "เพิ่มข้อมูล" เพื่อเพิ่ม
        </p>

        <p v-if="partsError" class="text-sm text-red-600">{{ partsError }}</p>

        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
            @click="addPart"
        >
            <Plus class="size-3.5" /> เพิ่มข้อมูล
        </button>

        <PopupPartReorderDialog :show="showReorder" :parts="parts" :languages="languages" @close="showReorder = false" @confirm="applyOrder" />

        <ConfirmDialog
            :show="pendingRemove !== null"
            title="ยืนยันการลบข้อมูล"
            confirm-text="ลบข้อมูล"
            @confirm="confirmRemove"
            @cancel="pendingRemove = null"
        >
            ต้องการลบข้อมูลที่ {{ (pendingRemove ?? 0) + 1 }} ใช่หรือไม่? (มีผลเมื่อกดบันทึก)
        </ConfirmDialog>
    </div>
</template>
