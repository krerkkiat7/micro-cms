<script setup lang="ts">
import FlagField from './FlagField.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

/**
 * ฟิลด์ของกล่อง/แถวที่ครอบแต่ละรายการ — ใช้ร่วมกันระหว่าง Slideset และ Grid ทุกแหล่งข้อมูล (ดู CategoryListWidget::cardBoxFields()
 * ฝั่ง backend): แสดงเส้นขอบ + สีเส้นขอบ (เลือกได้เมื่อแสดงเส้นขอบ), มุมมน, สีพื้นหลังของแต่ละรายการ (เลือก transparent ได้)
 * แก้ค่าบน `setting` ที่ส่งเข้ามาตรง ๆ (เป็นสำเนา draft ของ dialog อยู่แล้ว)
 */
defineProps<{
    setting: { show_border: 'Y' | 'N'; border_color: string; rounded_corners: 'Y' | 'N'; item_background: string };
    errors: Record<string, string>;
}>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <FlagField v-model="setting.show_border" label="แสดงเส้นขอบ" hint="เส้นบาง ๆ รอบกล่อง" />
            <div v-if="setting.show_border === 'Y'" class="mt-2 max-w-40">
                <InputLabel value="สีเส้นขอบ" />
                <ColorPickerInput v-model="setting.border_color" />
                <InputError :message="errors.border_color" />
            </div>
        </div>
        <FlagField v-model="setting.rounded_corners" label="มุมมน" hint="ปิดเพื่อให้เป็นมุมเหลี่ยม" />
        <div class="sm:col-span-2 sm:max-w-40">
            <InputLabel value="สีพื้นหลังของแต่ละรายการ" />
            <ColorPickerInput v-model="setting.item_background" transparent />
            <InputError :message="errors.item_background" />
        </div>
    </div>
</template>
