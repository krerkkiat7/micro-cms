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
import { SHOW_OPTIONS, cloneDeep, pickBackground, pickTextStyles } from '@/utils/pageLayout';
import type { ColumnData, ColumnSettings } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * dialog ตั้งค่าของ "คอลัมน์" — ความกว้าง หัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ (แยกภาษา + จัดรูปแบบตัวอักษร) และพื้นหลัง
 * แก้ไขบนสำเนา (draft) แล้วส่ง `save` กลับเมื่อกด "ตกลง" (ไม่แตะ widget ข้างใน)
 * ความกว้าง 1 - 12 เลือกจากปุ่มตัวเลขพร้อมแถบแสดงสัดส่วนเทียบทั้งแถว (grid 12)
 */
const props = defineProps<{
    show: boolean;
    column: ColumnData | null;
    languages: LanguageOption[];
    /** รายการชื่อฟอนต์ให้เลือก (จาก backend) */
    fonts: string[];
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
            const { detail, show_title, column_size } = props.column;
            draft.value = cloneDeep({ detail, show_title, column_size, ...pickBackground(props.column), ...pickTextStyles(props.column) });
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="แสดงหัวเรื่อง" />
                    <SearchableSelect v-model="draft.show_title" :options="SHOW_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">เปิดแล้วจะแสดงหัวเรื่อง หัวเรื่องรอง และข้อความเกริ่นนำของคอลัมน์นี้ (หัวเรื่องใช้แท็ก H3)</p>
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
