<script setup lang="ts">
import { ref, watch } from 'vue';
import LayoutDialog from '@/Components/Admin/PageLayout/LayoutDialog.vue';
import BackgroundFields from '@/Components/Admin/PageLayout/BackgroundFields.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SettingsSection from '@/Components/Admin/Template/SettingsSection.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import HeaderLayoutPicker from '@/Components/Admin/Template/HeaderLayoutPicker.vue';
import HeaderMenuStylePicker from '@/Components/Admin/Template/HeaderMenuStylePicker.vue';
import { cloneDeep } from '@/utils/pageLayout';
import {
    ALIGN_OPTIONS,
    CONTRAST_DISPLAY_OPTIONS,
    FONTSIZE_DISPLAY_OPTIONS,
    LANG_DISPLAY_OPTIONS,
    LANG_SELECT_OPTIONS,
    LOGO_ACTION_OPTIONS,
    LOGO_DISPLAY_OPTIONS,
    WIDTH_OPTIONS,
} from '@/utils/template';
import type { HeaderZone } from '@/utils/template';

/**
 * ตั้งค่าโซน header — แก้บนสำเนา (draft) แล้วส่งกลับตอนกด "ตกลง" (หน้าโครงสร้างนำไป Object.assign ทับ แล้ว preview เปลี่ยนตาม)
 * ส่วน "แถบบน" / "แถวเมนู" แสดงเฉพาะเมื่อรูปแบบการแสดงมีแถบนั้น
 */
const props = defineProps<{
    show: boolean;
    zone: HeaderZone | null;
}>();

const emit = defineEmits<{
    close: [];
    save: [zone: HeaderZone];
}>();

const draft = ref<HeaderZone | null>(null);

watch(
    () => props.show,
    (show) => {
        if (show && props.zone) draft.value = cloneDeep(props.zone);
    },
    { immediate: true },
);

function confirm() {
    if (draft.value) emit('save', draft.value);
}
</script>

<template>
    <LayoutDialog :show="show" title="ตั้งค่า Header" @close="emit('close')" @confirm="confirm">
        <div v-if="draft" class="space-y-6">
            <SettingsSection title="การแสดง header">
                <YesNoCheckbox v-model="draft.status" label="แสดง header" />
            </SettingsSection>

            <SettingsSection title="รูปแบบ">
                <div>
                    <InputLabel value="รูปแบบการแสดง" />
                    <HeaderLayoutPicker v-model="draft.layout_type" />
                </div>
                <YesNoCheckbox v-model="draft.sticky" label="แสดง header เสมอ" description="เลื่อนหน้าลงแล้ว header ยังติดอยู่ด้านบน" />
            </SettingsSection>

            <SettingsSection title="โลโก้" description="รูปโลโก้และชื่อเว็บมาจากหน้าตั้งค่าระบบ">
                <YesNoCheckbox v-model="draft.logo_status" label="แสดงโลโก้" />
                <div v-if="draft.logo_status === 'Y'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="จัดตำแหน่ง" />
                        <SegmentedChoice v-model="draft.logo_align" :options="ALIGN_OPTIONS" />
                        <p v-if="draft.logo_align === 'center' && draft.layout_type !== 'main_menubar'" class="mt-1 text-xs text-amber-600">
                            โลโก้กึ่งกลางเหมาะกับรูปแบบ "มีแถบหลัก และแถวเมนู" (เมนูจะย้ายลงบรรทัดถัดไป)
                        </p>
                    </div>
                    <div>
                        <InputLabel value="รูปแบบที่แสดง" />
                        <SegmentedChoice v-model="draft.logo_display" :options="LOGO_DISPLAY_OPTIONS" />
                    </div>
                    <div>
                        <InputLabel value="การกระทำ" />
                        <SegmentedChoice v-model="draft.logo_action" :options="LOGO_ACTION_OPTIONS" />
                    </div>
                </div>
            </SettingsSection>

            <SettingsSection title="เมนู" description="รายการเมนูมาจากโมดูลจัดการเมนูหน้าบ้าน">
                <div>
                    <InputLabel value="จัดตำแหน่ง" />
                    <SegmentedChoice v-model="draft.menu_align" :options="ALIGN_OPTIONS" />
                </div>
                <div>
                    <InputLabel value="รูปแบบการแสดง" />
                    <HeaderMenuStylePicker v-model="draft.menu_style" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="สีตัวอักษรเมนู" />
                        <ColorPickerInput v-model="draft.menu_text_color" />
                    </div>
                    <div>
                        <InputLabel value="สีเมนูที่เลือกอยู่" />
                        <ColorPickerInput v-model="draft.menu_active_color" />
                    </div>
                </div>
            </SettingsSection>

            <SettingsSection title="ภาษา">
                <YesNoCheckbox v-model="draft.lang_status" label="แสดงตัวเลือกภาษา" />
                <div v-if="draft.lang_status === 'Y'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="รูปแบบการแสดง" />
                        <SegmentedChoice v-model="draft.lang_display" :options="LANG_DISPLAY_OPTIONS" />
                    </div>
                    <div>
                        <InputLabel value="รูปแบบการเลือก" />
                        <SegmentedChoice v-model="draft.lang_select" :options="LANG_SELECT_OPTIONS" />
                    </div>
                </div>
            </SettingsSection>

            <SettingsSection title="Social Media / การค้นหา">
                <YesNoCheckbox v-model="draft.social_status" label="แสดง Social Media" description="แสดงเฉพาะช่องทางที่ตั้งค่าไว้ในหน้าตั้งค่าระบบ" />
                <YesNoCheckbox v-model="draft.search_status" label="แสดงการค้นหา" />
            </SettingsSection>

            <SettingsSection title="การปรับตัวอักษร">
                <YesNoCheckbox v-model="draft.fontsize_status" label="แสดงการปรับขนาดตัวอักษร" />
                <div v-if="draft.fontsize_status === 'Y'">
                    <InputLabel value="รูปแบบที่แสดง" />
                    <SegmentedChoice v-model="draft.fontsize_display" :options="FONTSIZE_DISPLAY_OPTIONS" />
                </div>
            </SettingsSection>

            <SettingsSection title="การแสดงสี" description="ปุ่มสลับโหมดสีสำหรับผู้พิการทางสายตา (ปกติ / ขาวดำ / ตัดกันสูง)">
                <YesNoCheckbox v-model="draft.contrast_status" label="แสดงการปรับการแสดงสี" />
                <div v-if="draft.contrast_status === 'Y'">
                    <InputLabel value="รูปแบบที่แสดง" />
                    <SegmentedChoice v-model="draft.contrast_display" :options="CONTRAST_DISPLAY_OPTIONS" />
                </div>
            </SettingsSection>

            <SettingsSection title="แถบหลัก">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="ขอบเขตความกว้าง" />
                        <SegmentedChoice v-model="draft.main_width" :options="WIDTH_OPTIONS" />
                    </div>
                    <div>
                        <InputLabel value="สีตัวอักษร" />
                        <ColorPickerInput v-model="draft.main_text_color" />
                    </div>
                </div>
                <BackgroundFields :fields="draft" />
            </SettingsSection>

            <SettingsSection v-if="draft.layout_type === 'topbar_main'" title="แถบบน">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="ขอบเขตความกว้าง" />
                        <SegmentedChoice v-model="draft.topbar_width" :options="WIDTH_OPTIONS" />
                    </div>
                    <div>
                        <InputLabel value="สีพื้นหลัง" />
                        <ColorPickerInput v-model="draft.topbar_background_color" transparent />
                    </div>
                    <div>
                        <InputLabel value="สีตัวอักษร" />
                        <ColorPickerInput v-model="draft.topbar_text_color" />
                    </div>
                </div>
            </SettingsSection>

            <SettingsSection v-if="draft.layout_type === 'main_menubar'" title="แถวเมนู">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="ขอบเขตความกว้าง" />
                        <SegmentedChoice v-model="draft.menubar_width" :options="WIDTH_OPTIONS" />
                    </div>
                    <div>
                        <InputLabel value="สีพื้นหลัง" />
                        <ColorPickerInput v-model="draft.menubar_background_color" transparent />
                    </div>
                </div>
            </SettingsSection>
        </div>
    </LayoutDialog>
</template>
