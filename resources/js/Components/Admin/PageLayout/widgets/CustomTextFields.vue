<script setup lang="ts">
import { ref } from 'vue';
import { Plus } from 'lucide-vue-next';
import PartCard from './CustomTextPart/PartCard.vue';
import PartReorderDialog from './CustomTextPart/PartReorderDialog.vue';
import { CUSTOMTEXT_PART_TYPE_ICONS, createCustomTextPart } from '@/utils/pageWidgetCustomText';
import type { CustomTextPartData, CustomTextPartType, CustomTextSetting } from '@/utils/pageWidgetCustomText';
import type { LanguageOption } from '@/types';

/**
 * ฟอร์มตั้งค่าเฉพาะของ widget Custom Text (อยู่ในกล่องบนสุดของ dialog ตั้งค่า widget) — เนื้อหาเป็นรายการ "part" เรียงลำดับได้
 * เหมือนระบบ part ของบทความ (Components/Admin/ArticlePart) ทุกประเภทมีหัวเรื่องของตัวเองที่จัดรูปแบบได้ (ขนาด/ฟอนต์/ตำแหน่ง/สี — PartCard.vue)
 * ตามด้วยเนื้อหาเฉพาะประเภท (ข้อความ/รูปภาพเดี่ยว/กลุ่มรูปภาพ/วิดีโอ) — เนื้อหาบางส่วนสูงมาก ลากสลับลำดับตรง ๆ จึงยาก ใช้ dialog เรียงลำดับแทน
 */
const props = defineProps<{
    setting: CustomTextSetting;
    languages: LanguageOption[];
    fonts: string[];
    errors: Record<string, string>;
}>();

const addOptions: { type: CustomTextPartType; label: string }[] = [
    { type: 'text', label: 'ข้อความ' },
    { type: 'image', label: 'รูปภาพเดี่ยว' },
    { type: 'images', label: 'กลุ่มรูปภาพ' },
    { type: 'video', label: 'วิดีโอ' },
];

function addPart(type: CustomTextPartType) {
    props.setting.parts.push(createCustomTextPart(type, props.languages));
}

function removePart(index: number) {
    props.setting.parts.splice(index, 1);
}

const showReorder = ref(false);

function applyOrder(order: CustomTextPartData[]) {
    props.setting.parts = order;
    showReorder.value = false;
}
</script>

<template>
    <div class="space-y-4">
        <div v-for="(part, index) in setting.parts" :key="part._key">
            <PartCard
                :part="part"
                :languages="languages"
                :fonts="fonts"
                :error-prefix="`setting.parts.${index}`"
                :form-errors="errors"
                @remove="removePart(index)"
                @reorder="showReorder = true"
            />
        </div>

        <p v-if="setting.parts.length === 0" class="rounded-xl border border-dashed border-gray-300 py-8 text-center text-sm text-gray-400">
            ยังไม่มีเนื้อหา — กดปุ่มด้านล่างเพื่อเพิ่มเนื้อหา
        </p>

        <div class="flex flex-wrap gap-2">
            <button
                v-for="opt in addOptions"
                :key="opt.type"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                @click="addPart(opt.type)"
            >
                <Plus class="size-3.5" />
                <component :is="CUSTOMTEXT_PART_TYPE_ICONS[opt.type]" class="size-3.5" />
                {{ opt.label }}
            </button>
        </div>

        <PartReorderDialog :show="showReorder" :parts="setting.parts" :languages="languages" @close="showReorder = false" @confirm="applyOrder" />
    </div>
</template>
