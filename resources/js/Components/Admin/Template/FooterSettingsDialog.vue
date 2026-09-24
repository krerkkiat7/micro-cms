<script setup lang="ts">
import { ref, watch } from 'vue';
import LayoutDialog from '@/Components/Admin/PageLayout/LayoutDialog.vue';
import BackgroundFields from '@/Components/Admin/PageLayout/BackgroundFields.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SettingsSection from '@/Components/Admin/Template/SettingsSection.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import FontStyleFields from '@/Components/Admin/Template/FontStyleFields.vue';
import FooterLayoutPicker from '@/Components/Admin/Template/FooterLayoutPicker.vue';
import { cloneDeep } from '@/utils/pageLayout';
import { WIDTH_OPTIONS } from '@/utils/template';
import type { FooterZone } from '@/utils/template';

/** ตั้งค่าโซน footer + แถบลิขสิทธิ์ — ข้อมูลที่แสดง (ที่อยู่/เบอร์/อีเมล/social/ลิขสิทธิ์) มาจากหน้าตั้งค่าระบบ */
const props = defineProps<{
    show: boolean;
    zone: FooterZone | null;
    fonts: string[];
}>();

const emit = defineEmits<{
    close: [];
    save: [zone: FooterZone];
}>();

const draft = ref<FooterZone | null>(null);

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
    <LayoutDialog :show="show" title="ตั้งค่า Footer" @close="emit('close')" @confirm="confirm">
        <div v-if="draft" class="space-y-6">
            <SettingsSection title="การแสดง footer">
                <YesNoCheckbox v-model="draft.status" label="แสดง footer" />
            </SettingsSection>

            <SettingsSection title="รูปแบบการแสดง">
                <FooterLayoutPicker v-model="draft.layout_type" />
                <div>
                    <InputLabel value="ขอบเขตความกว้าง" />
                    <SegmentedChoice v-model="draft.width" :options="WIDTH_OPTIONS" />
                </div>
            </SettingsSection>

            <SettingsSection title="พื้นหลัง">
                <BackgroundFields :fields="draft" />
            </SettingsSection>

            <SettingsSection title="ตัวอักษรหัวข้อ">
                <FontStyleFields :fields="draft" prefix="heading" :fonts="fonts" />
            </SettingsSection>

            <SettingsSection title="ตัวอักษรเนื้อหา">
                <FontStyleFields :fields="draft" prefix="text" :fonts="fonts" />
            </SettingsSection>

            <SettingsSection title="ข้อมูลที่แสดง" description="ค่าจากหน้าตั้งค่าระบบ → ข้อมูลติดต่อ / Social Media (ไม่ได้ตั้งค่าไว้จะไม่แสดง)">
                <div class="grid gap-3 sm:grid-cols-2">
                    <YesNoCheckbox v-model="draft.show_address" label="แสดงที่อยู่" />
                    <YesNoCheckbox v-model="draft.show_phone" label="แสดงเบอร์ติดต่อ" />
                    <YesNoCheckbox v-model="draft.show_fax" label="แสดงเบอร์แฟกซ์" />
                    <YesNoCheckbox v-model="draft.show_mobile" label="แสดงเบอร์มือถือ" />
                    <YesNoCheckbox v-model="draft.show_email" label="แสดงอีเมล" />
                    <YesNoCheckbox v-model="draft.show_social" label="แสดง Social Media" />
                    <YesNoCheckbox
                        v-if="draft.layout_type === 'site_contact_menu'"
                        v-model="draft.show_menu"
                        label="แสดงเมนู"
                        description="เมนูระดับแรกของเมนูหน้าบ้าน"
                    />
                </div>
            </SettingsSection>

            <SettingsSection title="แถบลิขสิทธิ์" description="ปีและชื่อเจ้าของไซต์มาจากหน้าตั้งค่าระบบ">
                <YesNoCheckbox v-model="draft.copyright_status" label="แสดงแถบลิขสิทธิ์" />
                <template v-if="draft.copyright_status === 'Y'">
                    <YesNoCheckbox v-model="draft.copyright_show_owner" label="แสดงชื่อเจ้าของไซต์" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="ขอบเขตความกว้าง" />
                            <SegmentedChoice v-model="draft.copyright_width" :options="WIDTH_OPTIONS" />
                        </div>
                        <div>
                            <InputLabel value="สีพื้นหลัง" />
                            <ColorPickerInput v-model="draft.copyright_background_color" transparent />
                        </div>
                    </div>
                    <FontStyleFields :fields="draft" prefix="copyright" :fonts="fonts" :bold="false" align />
                </template>
            </SettingsSection>
        </div>
    </LayoutDialog>
</template>
