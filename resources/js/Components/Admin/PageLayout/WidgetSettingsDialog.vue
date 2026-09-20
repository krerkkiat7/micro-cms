<script setup lang="ts">
import { ref, watch } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import LayoutDialog from './LayoutDialog.vue';
import BackgroundFields from './BackgroundFields.vue';
import TextFieldsSection from './TextFieldsSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { SHOW_OPTIONS, WIDGET_TYPES, cloneDeep, pickBackground, pickTextStyles } from '@/utils/pageLayout';
import type { WidgetData, WidgetSettings } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * dialog ตั้งค่าของ "Widget" — หัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ (แยกภาษา + จัดรูปแบบตัวอักษร) และพื้นหลัง
 * แก้ไขบนสำเนา (draft) แล้วส่ง `save` กลับเมื่อกด "ตกลง"
 * ประเภท widget และการตั้งค่าเฉพาะประเภท (`setting`) ยังรอกำหนดรายละเอียด ตอนนี้แก้ได้แค่ชนิด/ข้อความ/การแสดงหัวเรื่อง/พื้นหลัง
 */
const props = defineProps<{
    show: boolean;
    widget: WidgetData | null;
    languages: LanguageOption[];
    /** รายการชื่อฟอนต์ให้เลือก (จาก backend) */
    fonts: string[];
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
            draft.value = cloneDeep({ detail, show_title, widget_type, ...pickBackground(props.widget), ...pickTextStyles(props.widget) });
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
                    <InputLabel value="แสดงหัวเรื่อง" />
                    <SearchableSelect v-model="draft.show_title" :options="SHOW_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">เปิดแล้วจะแสดงหัวเรื่อง หัวเรื่องรอง และข้อความเกริ่นนำของ Widget นี้ (หัวเรื่องใช้แท็ก H4)</p>
                </div>
            </div>

            <TextFieldsSection label="หัวเรื่อง" part="title" :languages="languages" :detail="draft.detail" :text-style="draft.title_style" :fonts="fonts" />
            <TextFieldsSection label="หัวเรื่องรอง" part="subtitle" :languages="languages" :detail="draft.detail" :text-style="draft.subtitle_style" :fonts="fonts" />
            <TextFieldsSection
                label="ข้อความเกริ่นนำ"
                part="intro_text"
                multiline
                :languages="languages"
                :detail="draft.detail"
                :text-style="draft.intro_text_style"
                :fonts="fonts"
            />

            <div class="space-y-4 border-t border-gray-100 pt-5">
                <h3 class="text-sm font-medium text-gray-600">พื้นหลัง</h3>
                <BackgroundFields :fields="draft" />
            </div>
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
