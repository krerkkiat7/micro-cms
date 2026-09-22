<script setup lang="ts">
import { computed } from 'vue';
import { Eye, EyeOff, ListOrdered, Trash2 } from 'lucide-vue-next';
import PartText from './PartText.vue';
import PartImage from './PartImage.vue';
import PartImages from './PartImages.vue';
import PartVideo from './PartVideo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import TextInput from '@/Components/TextInput.vue';
import TextStyleFields from '@/Components/Admin/PageLayout/TextStyleFields.vue';
import { settingTextStyle } from '@/utils/pageWidget';
import { CUSTOMTEXT_PART_TYPE_ICONS, CUSTOMTEXT_PART_TYPE_LABELS } from '@/utils/pageWidgetCustomText';
import type { LanguageOption } from '@/types';
import type { CustomTextPartData } from '@/utils/pageWidgetCustomText';

/**
 * การ์ดของ part 1 รายการ — ส่วนหัว (ประเภท/เรียงลำดับ/แสดงหัวเรื่อง/ซ่อน/ลบ) ตามด้วย "หัวเรื่อง" ที่ทุกประเภทมีเหมือนกัน
 * (ข้อความแยกภาษา + การจัดรูปแบบตัวอักษร — ต่างจาก part ของบทความที่ไม่มีการจัดรูปแบบ จึงยกขึ้นมาไว้ที่นี่ที่เดียวแทนที่จะ
 * ซ้ำในแต่ละ PartText/PartImage/PartImages/PartVideo) แล้วค่อยตามด้วยเนื้อหาเฉพาะประเภท
 */
const props = defineProps<{
    part: CustomTextPartData;
    languages: LanguageOption[];
    fonts: string[];
    errorPrefix: string;
    formErrors: Record<string, string>;
}>();

defineEmits<{ remove: []; reorder: [] }>();

const typeMeta: Record<string, { component: unknown }> = {
    text: { component: PartText },
    image: { component: PartImage },
    images: { component: PartImages },
    video: { component: PartVideo },
};

const meta = computed(() => typeMeta[props.part.part_type]);
const icon = computed(() => CUSTOMTEXT_PART_TYPE_ICONS[props.part.part_type]);
const titleStyle = computed(() => settingTextStyle(props.part, 'title'));

function toggleStatus() {
    props.part.status = props.part.status === 'Y' ? 'N' : 'Y';
}

function titleError(lang: string): string | undefined {
    return props.formErrors[`${props.errorPrefix}.detail.${lang}.title`];
}
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4" :class="part.status === 'N' ? 'opacity-60' : ''">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <button type="button" class="text-gray-400 hover:text-gray-600" title="จัดลำดับเนื้อหา" @click="$emit('reorder')">
                <ListOrdered class="size-5" />
            </button>
            <component :is="icon" class="size-4 text-gray-500" />
            <span class="text-sm font-medium text-gray-700">{{ CUSTOMTEXT_PART_TYPE_LABELS[part.part_type] }}</span>

            <label class="ml-2 flex items-center gap-1.5 text-xs text-gray-500">
                <Checkbox :checked="part.show_title === 'Y'" @update:checked="(v) => (part.show_title = v ? 'Y' : 'N')" />
                แสดงหัวเรื่อง
            </label>

            <div class="ml-auto flex items-center gap-1">
                <button
                    type="button"
                    class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    :title="part.status === 'Y' ? 'ซ่อน part นี้' : 'แสดง part นี้'"
                    @click="toggleStatus"
                >
                    <Eye v-if="part.status === 'Y'" class="size-4" />
                    <EyeOff v-else class="size-4" />
                </button>
                <button type="button" class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600" title="ลบ part นี้" @click="$emit('remove')">
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <LangFieldGroup label="หัวเรื่อง" :languages="languages">
                <template #default="{ lang }">
                    <TextInput v-model="part.detail[lang.code].title" type="text" maxlength="250" />
                    <InputError :message="titleError(lang.code)" />
                </template>
            </LangFieldGroup>

            <div class="rounded-xl bg-white p-4">
                <p class="mb-3 text-xs font-medium text-gray-500">การจัดรูปแบบตัวอักษร — หัวเรื่อง</p>
                <TextStyleFields :text-style="titleStyle" :fonts="fonts" />
            </div>

            <component :is="meta.component" :part="part" :languages="languages" :error-prefix="errorPrefix" :form-errors="formErrors" />
        </div>
    </div>
</template>
