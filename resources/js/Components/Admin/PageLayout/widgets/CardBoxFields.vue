<script setup lang="ts">
import FlagField from './FlagField.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

/**
 * ฟิลด์ของกล่อง/แถวที่ครอบแต่ละรายการ — ใช้ร่วมกันระหว่าง Slideset และ Grid ทุกแหล่งข้อมูล (ดู CategoryListWidget::cardBoxFields()
 * ฝั่ง backend): แสดงเส้นขอบ + สีเส้นขอบ (เลือกได้เมื่อแสดงเส้นขอบ), มุมมน, สีพื้นหลังของแต่ละรายการ (เลือก transparent ได้)
 * ตัวเลือกสีเต็มความกว้างของกล่องตั้งค่า; slot default = ฟิลด์เพิ่มเติมต่อท้าย (เช่น ตำแหน่งของข้อมูลของ Grid แบบแถว)
 * แก้ค่าบน `setting` ที่ส่งเข้ามาตรง ๆ (เป็นสำเนา draft ของ dialog อยู่แล้ว)
 */
defineProps<{
    setting: { show_border: 'Y' | 'N'; border_color: string; rounded_corners: 'Y' | 'N'; item_background: string };
    errors: Record<string, string>;
}>();
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <FlagField v-model="setting.show_border" label="แสดงเส้นขอบ" hint="เส้นบาง ๆ รอบกล่อง" />
            <FlagField v-model="setting.rounded_corners" label="มุมมน" hint="ปิดเพื่อให้เป็นมุมเหลี่ยม" />
        </div>
        <div v-if="setting.show_border === 'Y'">
            <InputLabel value="สีเส้นขอบ" />
            <ColorPickerInput v-model="setting.border_color" />
            <InputError :message="errors.border_color" />
        </div>
        <div>
            <InputLabel value="สีพื้นหลังของแต่ละรายการ" />
            <ColorPickerInput v-model="setting.item_background" transparent />
            <InputError :message="errors.item_background" />
        </div>
        <slot />
    </div>
</template>
