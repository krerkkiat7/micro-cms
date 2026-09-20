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
import { SHOW_OPTIONS, cloneDeep } from '@/utils/pageLayout';
import type { ColumnData, ColumnSettings } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * dialog ตั้งค่าของ "คอลัมน์" — แก้ไขบนสำเนา (draft) แล้วส่ง `save` กลับเมื่อกด "ตกลง" (ไม่แตะ widget ข้างใน)
 * ความกว้าง 1 - 12 เลือกจากปุ่มตัวเลขพร้อมแถบแสดงสัดส่วนเทียบทั้งแถว (grid 12)
 */
const props = defineProps<{
    show: boolean;
    column: ColumnData | null;
    languages: LanguageOption[];
}>();

const emit = defineEmits<{
    close: [];
    save: [settings: ColumnSettings];
    remove: [];
}>();

const SIZES = Array.from({ length: 12 }, (_, i) => i + 1);

const draft = ref<ColumnSettings | null>(null);
const confirmingDelete = ref(false);

watch(
    () => props.show,
    (show) => {
        if (show && props.column) {
            const { detail, show_title, column_size, background_color, background_image, background_repeat, background_size, background_attachment, background_position } = props.column;
            draft.value = cloneDeep({ detail, show_title, column_size, background_color, background_image, background_repeat, background_size, background_attachment, background_position });
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
    <LayoutDialog :show="show && draft !== null" title="ตั้งค่าคอลัมน์" @close="emit('close')" @confirm="confirm">
        <div v-if="draft" class="space-y-5">
            <div>
                <InputLabel value="ความกว้างคอลัมน์ (จาก 12 ส่วน)" required />
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="size in SIZES"
                        :key="size"
                        type="button"
                        class="flex size-9 items-center justify-center rounded-lg border text-sm font-medium transition-colors"
                        :class="
                            draft.column_size === size
                                ? 'border-brand-500 bg-brand-50 text-brand-700 ring-1 ring-brand-500'
                                : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'
                        "
                        @click="draft.column_size = size"
                    >
                        {{ size }}
                    </button>
                </div>
                <div class="mt-2 grid h-3 grid-cols-12 gap-0.5 overflow-hidden rounded" aria-hidden="true">
                    <span
                        v-for="n in SIZES"
                        :key="n"
                        class="rounded-sm"
                        :class="n <= draft.column_size ? 'bg-brand-500' : 'bg-gray-200'"
                    />
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="แสดงหัวเรื่องของคอลัมน์" />
                    <SearchableSelect v-model="draft.show_title" :options="SHOW_OPTIONS" />
                </div>
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-5">
                <h3 class="text-sm font-medium text-gray-600">พื้นหลัง</h3>
                <BackgroundFields :fields="draft" />
            </div>
        </div>

        <template #footer-left>
            <DangerButton type="button" @click="confirmingDelete = true">
                <Trash2 class="mr-1.5 size-4" /> ลบคอลัมน์
            </DangerButton>
        </template>
    </LayoutDialog>

    <ConfirmDialog
        :show="confirmingDelete"
        title="ยืนยันการลบคอลัมน์"
        confirm-text="ลบคอลัมน์"
        @confirm="remove"
        @cancel="confirmingDelete = false"
    >
        ต้องการลบคอลัมน์นี้พร้อม Widget ทั้งหมดภายในใช่หรือไม่? (การลบจะมีผลเมื่อกด "บันทึกโครงสร้าง")
    </ConfirmDialog>
</template>
