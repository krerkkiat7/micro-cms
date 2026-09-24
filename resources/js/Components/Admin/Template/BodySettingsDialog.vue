<script setup lang="ts">
import { ref, watch } from 'vue';
import LayoutDialog from '@/Components/Admin/PageLayout/LayoutDialog.vue';
import BackgroundFields from '@/Components/Admin/PageLayout/BackgroundFields.vue';
import SettingsSection from '@/Components/Admin/Template/SettingsSection.vue';
import { cloneDeep } from '@/utils/pageLayout';
import type { BodyZone } from '@/utils/template';

/** ตั้งค่าโซน main body — กำหนดพื้นหลังของพื้นที่เนื้อหา */
const props = defineProps<{
    show: boolean;
    zone: BodyZone | null;
}>();

const emit = defineEmits<{
    close: [];
    save: [zone: BodyZone];
}>();

const draft = ref<BodyZone | null>(null);

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
    <LayoutDialog :show="show" title="ตั้งค่า Main Body" @close="emit('close')" @confirm="confirm">
        <div v-if="draft" class="space-y-6">
            <SettingsSection title="กำหนดพื้นหลัง" description="พื้นหลังของพื้นที่เนื้อหาระหว่าง header กับ footer (หน้าเพจแต่ละหน้าตั้งพื้นหลังของตัวเองทับได้)">
                <BackgroundFields :fields="draft" />
            </SettingsSection>
        </div>
    </LayoutDialog>
</template>
