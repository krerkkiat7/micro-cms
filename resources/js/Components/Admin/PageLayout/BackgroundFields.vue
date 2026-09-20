<script setup lang="ts">
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import BackgroundRepeatPicker from '@/Components/Admin/IntropageBackground/RepeatPicker.vue';
import BackgroundSizePicker from '@/Components/Admin/IntropageBackground/SizePicker.vue';
import BackgroundAttachmentPicker from '@/Components/Admin/IntropageBackground/AttachmentPicker.vue';
import BackgroundPositionPicker from '@/Components/Admin/IntropageBackground/PositionPicker.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import type { BackgroundFields } from '@/utils/pageLayout';

/**
 * กลุ่มฟิลด์ "พื้นหลัง" ที่ใช้ซ้ำทั้งในฟอร์มข้อมูลทั่วไปของหน้าเพจ และ dialog ตั้งค่าแถว/คอลัมน์ — สี (มีตัวเลือกโปร่งใส) +
 * รูปภาพ (เลือกจากระบบจัดการไฟล์) และตัวเลือก CSS background 4 ค่า (repeat/size/attachment/position — ใช้ picker เห็นภาพเดียวกับ
 * Intropage) ซึ่งจะแสดงต่อเมื่อเลือกรูปภาพแล้วเท่านั้น (ไม่มีรูป = ตั้งค่าเหล่านี้ไม่มีความหมาย)
 * `fields` = object ที่เก็บค่า (แก้ property ภายในตรง ๆ ไม่ใช้ v-model ทั้งก้อน เพราะพาเรนต์อาจส่ง object ที่มีฟิลด์อื่นปนมาด้วย)
 * `errors` = ข้อความ error ต่อชื่อฟิลด์ (ไม่บังคับ)
 */
defineProps<{
    fields: BackgroundFields;
    errors?: Record<string, string | undefined>;
}>();
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-6">
            <div class="sm:col-span-3">
                <InputLabel value="สีพื้นหลัง" />
                <ColorPickerInput v-model="fields.background_color" transparent />
                <InputError :message="errors?.background_color" />
            </div>
            <div class="sm:col-span-3">
                <InputLabel value="รูปภาพพื้นหลัง" />
                <FilePickerField v-model="fields.background_image" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                <InputError :message="errors?.background_image_id" />
            </div>
        </div>

        <template v-if="fields.background_image.length > 0">
            <div>
                <InputLabel value="การเรียงซ้ำ (Background Repeat)" />
                <BackgroundRepeatPicker v-model="fields.background_repeat" />
                <InputError :message="errors?.background_repeat" />
            </div>

            <div>
                <InputLabel value="ขนาด (Background Size)" />
                <BackgroundSizePicker v-model="fields.background_size" />
                <InputError :message="errors?.background_size" />
            </div>

            <div>
                <InputLabel value="การเลื่อน (Background Attachment)" />
                <BackgroundAttachmentPicker v-model="fields.background_attachment" />
                <InputError :message="errors?.background_attachment" />
            </div>

            <div>
                <InputLabel value="ตำแหน่ง (Background Position)" />
                <BackgroundPositionPicker v-model="fields.background_position" />
                <InputError :message="errors?.background_position" />
            </div>
        </template>
    </div>
</template>
