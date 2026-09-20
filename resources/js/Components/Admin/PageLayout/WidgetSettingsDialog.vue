<script setup lang="ts">
import { ref, watch } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import LayoutDialog from './LayoutDialog.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { SHOW_OPTIONS, WIDGET_TYPES, cloneDeep } from '@/utils/pageLayout';
import type { WidgetData, WidgetSettings } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * dialog ตั้งค่าของ "Widget" — แก้ไขบนสำเนา (draft) แล้วส่ง `save` กลับเมื่อกด "ตกลง"
 * ประเภท widget และการตั้งค่าเฉพาะประเภท (`setting`) ยังรอกำหนดรายละเอียด ตอนนี้แก้ได้แค่ชนิด/ชื่อ/ข้อความเกริ่นนำ/การแสดงชื่อ
 */
const props = defineProps<{
    show: boolean;
    widget: WidgetData | null;
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    save: [settings: WidgetSettings];
    remove: [];
}>();

const draft = ref<WidgetSettings | null>(null);
const confirmingDelete = ref(false);

watch(
    () => props.show,
    (show) => {
        if (show && props.widget) {
            const { detail, show_title, widget_type } = props.widget;
            draft.value = cloneDeep({ detail, show_title, widget_type });
        }
    },
    { immediate: true },
);

function confirm() {
    if (draft.value) {
        emit('save', draft.value);
    }
}

function remove() {
    confirmingDelete.value = false;
    emit('remove');
}
</script>

<template>
    <LayoutDialog :show="show && draft !== null" title="ตั้งค่า Widget" @close="emit('close')" @confirm="confirm">
        <div v-if="draft" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="ประเภท Widget" required />
                    <SearchableSelect v-model="draft.widget_type" :options="WIDGET_TYPES" />
                </div>
                <div>
                    <InputLabel value="แสดงหัวเรื่องของ Widget" />
                    <SearchableSelect v-model="draft.show_title" :options="SHOW_OPTIONS" />
                </div>
            </div>

            <LangFieldGroup label="ชื่อหัวเรื่อง" :languages="languages">
                <template #default="{ lang }">
                    <TextInput v-model="draft.detail[lang.code].title" type="text" maxlength="250" />
                </template>
            </LangFieldGroup>

            <LangFieldGroup label="ข้อความเกริ่นนำ" :languages="languages">
                <template #default="{ lang }">
                    <Textarea v-model="draft.detail[lang.code].intro_text" rows="2" maxlength="2000" />
                </template>
            </LangFieldGroup>
        </div>

        <template #footer-left>
            <DangerButton type="button" @click="confirmingDelete = true">
                <Trash2 class="mr-1.5 size-4" /> ลบ Widget
            </DangerButton>
        </template>
    </LayoutDialog>

    <ConfirmDialog
        :show="confirmingDelete"
        title="ยืนยันการลบ Widget"
        confirm-text="ลบ Widget"
        @confirm="remove"
        @cancel="confirmingDelete = false"
    >
        ต้องการลบ Widget นี้ใช่หรือไม่? (การลบจะมีผลเมื่อกด "บันทึกโครงสร้าง")
    </ConfirmDialog>
</template>
