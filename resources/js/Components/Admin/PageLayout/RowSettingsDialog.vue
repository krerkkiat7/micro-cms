<script setup lang="ts">
import { ref, watch } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import LayoutDialog from './LayoutDialog.vue';
import BackgroundFields from './BackgroundFields.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { CONTAINER_OPTIONS, SHOW_OPTIONS, cloneDeep } from '@/utils/pageLayout';
import type { RowData, RowSettings } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * dialog ตั้งค่าของ "แถว" — แก้ไขบนสำเนา (draft) ของค่าที่ dialog นี้จัดการเท่านั้น (ไม่แตะคอลัมน์ข้างใน) แล้วส่ง `save`
 * กลับเมื่อกด "ตกลง" (ยกเลิกแล้วค่าเดิมไม่เปลี่ยน) — ปุ่ม "ลบแถว" อยู่ในนี้ ไม่เพิ่มไอคอนบนแถบจัดการเกินที่กำหนด
 */
const props = defineProps<{
    show: boolean;
    row: RowData | null;
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    save: [settings: RowSettings];
    remove: [];
}>();

const draft = ref<RowSettings | null>(null);
const confirmingDelete = ref(false);

watch(
    () => props.show,
    (show) => {
        if (show && props.row) {
            const { detail, show_title, use_container, background_color, background_image, background_repeat, background_size, background_attachment, background_position } = props.row;
            draft.value = cloneDeep({ detail, show_title, use_container, background_color, background_image, background_repeat, background_size, background_attachment, background_position });
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
    <LayoutDialog :show="show && draft !== null" title="ตั้งค่าแถว" @close="emit('close')" @confirm="confirm">
        <div v-if="draft" class="space-y-5">
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="แสดงหัวเรื่องของแถว" />
                    <SearchableSelect v-model="draft.show_title" :options="SHOW_OPTIONS" />
                </div>
                <div>
                    <InputLabel value="การแสดงเนื้อหา" />
                    <SearchableSelect v-model="draft.use_container" :options="CONTAINER_OPTIONS" />
                </div>
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-5">
                <h3 class="text-sm font-medium text-gray-600">พื้นหลัง</h3>
                <BackgroundFields :fields="draft" />
            </div>
        </div>

        <template #footer-left>
            <DangerButton type="button" @click="confirmingDelete = true">
                <Trash2 class="mr-1.5 size-4" /> ลบแถว
            </DangerButton>
        </template>
    </LayoutDialog>

    <ConfirmDialog
        :show="confirmingDelete"
        title="ยืนยันการลบแถว"
        confirm-text="ลบแถว"
        @confirm="remove"
        @cancel="confirmingDelete = false"
    >
        ต้องการลบแถวนี้พร้อมคอลัมน์และ Widget ทั้งหมดภายในใช่หรือไม่? (การลบจะมีผลเมื่อกด "บันทึกโครงสร้าง")
    </ConfirmDialog>
</template>
