<script setup lang="ts">
import { ref, watch } from 'vue';
import LayoutDialog from '@/Components/Admin/PageLayout/LayoutDialog.vue';
import BackgroundFields from '@/Components/Admin/PageLayout/BackgroundFields.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SettingsSection from '@/Components/Admin/Template/SettingsSection.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import AsideDisplayPicker from '@/Components/Admin/Template/AsideDisplayPicker.vue';
import AsideMenuStylePicker from '@/Components/Admin/Template/AsideMenuStylePicker.vue';
import { cloneDeep } from '@/utils/pageLayout';
import { ASIDE_TOGGLE_OPTIONS } from '@/utils/template';
import type { AsideZone } from '@/utils/template';

/** ตั้งค่าโซน aside (เมนูข้าง — เปิดจากไอคอนใน header) */
const props = defineProps<{
    show: boolean;
    zone: AsideZone | null;
}>();

const emit = defineEmits<{
    close: [];
    save: [zone: AsideZone];
}>();

const draft = ref<AsideZone | null>(null);

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
    <LayoutDialog :show="show" title="ตั้งค่า Aside (เมนูข้าง)" @close="emit('close')" @confirm="confirm">
        <div v-if="draft" class="space-y-6">
            <SettingsSection title="การแสดง aside">
                <YesNoCheckbox v-model="draft.status" label="ใช้งานเมนูข้าง" description="แสดงไอคอนเปิดเมนูข้างใน header" />
                <div>
                    <InputLabel value="ตำแหน่งไอคอนที่กดเปิด" />
                    <SegmentedChoice v-model="draft.toggle_position" :options="ASIDE_TOGGLE_OPTIONS" />
                </div>
                <div>
                    <InputLabel value="รูปแบบการแสดง" />
                    <AsideDisplayPicker v-model="draft.display_type" :side="draft.toggle_position" />
                </div>
            </SettingsSection>

            <SettingsSection title="พื้นหลัง / ตัวอักษร">
                <div class="sm:w-1/2">
                    <InputLabel value="สีตัวอักษร" />
                    <ColorPickerInput v-model="draft.text_color" />
                </div>
                <BackgroundFields :fields="draft" />
            </SettingsSection>

            <SettingsSection title="รูปแบบการแสดงเมนู">
                <AsideMenuStylePicker v-model="draft.menu_style" />
            </SettingsSection>
        </div>
    </LayoutDialog>
</template>
