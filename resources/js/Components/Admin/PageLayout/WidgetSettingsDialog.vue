<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import LayoutDialog from './LayoutDialog.vue';
import BackgroundFields from './BackgroundFields.vue';
import TextFieldsSection from './TextFieldsSection.vue';
import SlideshowFields from './widgets/SlideshowFields.vue';
import SlidesetFields from './widgets/SlidesetFields.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { SHOW_OPTIONS, cloneDeep, pickBackground, pickTextStyles } from '@/utils/pageLayout';
import { slideshowConfig, slidesetConfig, validateSetting, widgetTypeLabel } from '@/utils/pageWidget';
import type { SlideshowCommonSetting, SlidesetSetting } from '@/utils/pageWidget';
import type { WidgetData, WidgetOptions, WidgetSettings } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * dialog ตั้งค่าของ "Widget" — ด้านบนสุดเป็นกล่องตั้งค่าเฉพาะประเภท (บอกชื่อประเภทที่เลือกไว้) ตามด้วยส่วนการแสดงผลทั่วไป:
 * หัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ (แยกภาษา + จัดรูปแบบตัวอักษร) และพื้นหลัง — แก้บนสำเนา (draft) แล้วส่ง `save` กลับเมื่อกด "ตกลง"
 * `mode = add`: widget ที่ส่งเข้ามายังไม่ถูกเพิ่มลงหน้า (เพิ่มเมื่อ `save` เท่านั้น) มีปุ่ม "ย้อนกลับ" ไปเลือกประเภทใหม่แทนปุ่มลบ
 * `mode = edit`: แก้ประเภทไม่ได้ (แสดงชื่อประเภทอย่างเดียว) มีปุ่มลบ
 */
const props = defineProps<{
    show: boolean;
    mode: 'add' | 'edit';
    widget: WidgetData | null;
    languages: LanguageOption[];
    /** รายการชื่อฟอนต์ให้เลือก (จาก backend) */
    fonts: string[];
    /** ข้อมูลประกอบฟอร์มตั้งค่าเฉพาะประเภท เช่น รายการหมวดหมู่ banner/article ที่เลือกได้ (จาก PageWidgetRegistry::options()) */
    widgetOptions: WidgetOptions;
}>();

const emit = defineEmits<{
    close: [];
    save: [settings: WidgetSettings];
    remove: [];
    back: [];
}>();

const draft = ref<WidgetSettings | null>(null);
const errors = ref<Record<string, string>>({});
const confirmingDelete = ref(false);

watch(
    () => props.show,
    (show) => {
        if (show && props.widget) {
            const { detail, show_title, widget_type, setting } = props.widget;
            draft.value = cloneDeep({ detail, show_title, widget_type, setting, ...pickBackground(props.widget), ...pickTextStyles(props.widget) });
            errors.value = {};
        }
    },
    { immediate: true },
);

// หลังแจ้ง error แล้ว ตรวจซ้ำตามที่ผู้ใช้แก้ เพื่อให้ข้อความหายทันทีที่ค่าถูกต้อง
watch(
    () => draft.value?.setting,
    () => {
        if (draft.value && Object.keys(errors.value).length > 0) {
            errors.value = validateSetting(draft.value.widget_type, draft.value.setting);
        }
    },
    { deep: true },
);

const typeLabel = computed(() => (draft.value ? widgetTypeLabel(draft.value.widget_type) : ''));

// ประเภท Slideshow (จาก banner / จาก article) ใช้ฟอร์มเดียวกัน — รายการหมวดหมู่ที่ให้เลือกขึ้นกับประเภท
const slideshow = computed(() => (draft.value ? slideshowConfig(draft.value.widget_type) : undefined));
// Slideset (การ์ดเลื่อนได้) มีฟอร์มของตัวเองเพราะมีฟิลด์ตั้งค่ามาก
const slideset = computed(() => (draft.value ? slidesetConfig(draft.value.widget_type) : undefined));

function confirm() {
    if (!draft.value) {
        return;
    }

    errors.value = validateSetting(draft.value.widget_type, draft.value.setting);

    if (Object.keys(errors.value).length === 0) {
        emit('save', draft.value);
    }
}

function remove() {
    confirmingDelete.value = false;
    emit('remove');
}
</script>

<template>
    <LayoutDialog
        :show="show && draft !== null"
        :title="mode === 'add' ? 'เพิ่ม Widget — ตั้งค่า' : 'ตั้งค่า Widget'"
        :confirm-text="mode === 'add' ? 'เพิ่ม Widget' : 'ตกลง'"
        @close="emit('close')"
        @confirm="confirm"
    >
        <div v-if="draft" class="space-y-5">
            <!-- ตั้งค่าเฉพาะประเภท -->
            <section class="rounded-xl border border-brand-200 bg-brand-50/40 p-4">
                <header class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="rounded bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700">ประเภท Widget</span>
                    <h3 class="text-sm font-semibold text-gray-800">{{ typeLabel }}</h3>
                    <span v-if="mode === 'edit'" class="text-xs text-gray-500">(เปลี่ยนประเภทไม่ได้)</span>
                </header>

                <SlideshowFields
                    v-if="slideshow"
                    :widget-type="draft.widget_type"
                    :setting="draft.setting as unknown as SlideshowCommonSetting"
                    :categories="widgetOptions[slideshow.optionsKey]"
                    :fonts="fonts"
                    :errors="errors"
                />
                <SlidesetFields
                    v-else-if="slideset"
                    :widget-type="draft.widget_type"
                    :setting="draft.setting as unknown as SlidesetSetting"
                    :categories="widgetOptions[slideset.optionsKey]"
                    :fonts="fonts"
                    :errors="errors"
                />
                <p v-else class="text-sm text-gray-500">Widget ประเภทนี้ไม่มีการตั้งค่าเฉพาะ</p>
            </section>

            <hr class="border-gray-200" />

            <!-- การแสดงผลทั่วไปของ widget (หัวเรื่องเหนือ widget + พื้นหลัง) -->
            <div class="space-y-5">
                <div>
                    <InputLabel value="แสดงหัวเรื่อง" />
                    <SearchableSelect v-model="draft.show_title" :options="SHOW_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">เปิดแล้วจะแสดงหัวเรื่อง หัวเรื่องรอง และข้อความเกริ่นนำของ Widget นี้เหนือตัว Widget (หัวเรื่องใช้แท็ก H4)</p>
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
        </div>

        <template #footer-left>
            <SecondaryButton v-if="mode === 'add'" type="button" @click="emit('back')">
                <ArrowLeft class="mr-1.5 size-4" /> ย้อนกลับ
            </SecondaryButton>
            <DangerButton v-else type="button" @click="confirmingDelete = true">
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
