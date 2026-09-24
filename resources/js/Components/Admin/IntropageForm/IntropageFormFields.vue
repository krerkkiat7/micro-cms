<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { CSSProperties } from 'vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import DateTimeInput from '@/Components/Admin/DateTimeInput.vue';
import FilePickerField from '@/Components/Admin/FileManager/FilePickerField.vue';
import BackgroundAttachmentPicker from '@/Components/Admin/IntropageBackground/AttachmentPicker.vue';
import BackgroundPositionPicker from '@/Components/Admin/IntropageBackground/PositionPicker.vue';
import BackgroundRepeatPicker from '@/Components/Admin/IntropageBackground/RepeatPicker.vue';
import BackgroundSizePicker from '@/Components/Admin/IntropageBackground/SizePicker.vue';
import ButtonList from '@/Components/Admin/IntropageButton/ButtonList.vue';
import IntropageDisplaySizePicker from '@/Components/Admin/IntropageDisplaySizePicker.vue';
import IntropageDisplayTypePicker from '@/Components/Admin/IntropageDisplayTypePicker.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import type { FileItem, LanguageOption } from '@/types';
import { STATUS_OPTIONS } from '@/utils/options';
import { FONT_SIZE_OPTIONS } from '@/utils/pageLayout';

/**
 * ฟิลด์ของฟอร์มเพิ่ม/แก้ไข Intropage (ใช้ร่วมกันระหว่าง Add.vue / Edit.vue) — เรียงตามลำดับที่ผู้ใช้กรอก แบ่งเป็นการ์ด:
 * 1. ข้อความ: ชื่อ → ข้อความต้อนรับ → ฟอนต์ / ขนาดฟอนต์ / สีตัวอักษรของข้อความต้อนรับ (+ ตัวอย่าง)
 * 2. สื่อหลัก: ประเภทการแสดงผล → รูปภาพ/วิดีโอ → ขนาดการแสดงผล
 * 3. พื้นหลัง
 * 4. การจัดการปุ่ม: แสดงโซนปุ่ม (checkbox — ซ่อนการตั้งค่าที่เหลือเมื่อไม่แสดง) → ขนาดฟอนต์/ฟอนต์ของปุ่ม → รายการปุ่ม
 * 5. การเผยแพร่: วันที่ประกาศ / วันที่ปิดประกาศ → สถานะ
 *
 * `form` คือ object จาก useForm() ของหน้า — แก้ property ตรง ๆ (ไม่ตั้งชื่อ prop ว่า style/class — ดูข้อควรระวังใน CLAUDE.md)
 */
const props = defineProps<{
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    form: Record<string, any> & { errors: Record<string, string> };
    languages: LanguageOption[];
    fonts: string[];
    /** ไฟล์ที่เลือกไว้แล้ว (หน้าแก้ไข) */
    initialBackgroundImage?: FileItem | null;
    initialImageFile?: FileItem | null;
    initialVdoFile?: FileItem | null;
}>();

/* eslint-disable vue/no-mutating-props */
const backgroundImage = ref<FileItem[]>(props.initialBackgroundImage ? [props.initialBackgroundImage] : []);
watch(backgroundImage, (files) => (props.form.background_image_id = files[0]?.id ?? null));

const imageFile = ref<FileItem[]>(props.initialImageFile ? [props.initialImageFile] : []);
watch(imageFile, (files) => (props.form.image_file_id = files[0]?.id ?? null));

const vdoFile = ref<FileItem[]>(props.initialVdoFile ? [props.initialVdoFile] : []);
watch(vdoFile, (files) => (props.form.vdo_file_id = files[0]?.id ?? null));

const fontOptions = computed(() => props.fonts.map((font) => ({ value: font, label: font })));

/** ตัวเลือกขนาดฟอนต์ (มีค่าปัจจุบันเสมอ แม้ไม่อยู่ในรายการมาตรฐาน) — ฟอร์มเก็บเป็นตัวเลข */
function sizeOptions(current: number) {
    const value = String(current);

    return FONT_SIZE_OPTIONS.some((o) => o.value === value) ? FONT_SIZE_OPTIONS : [...FONT_SIZE_OPTIONS, { value, label: `${value} px` }];
}

function sizeModel(field: 'detail_font_size' | 'button_font_size') {
    return computed({
        get: () => String(props.form[field]),
        set: (value: string) => (props.form[field] = Number(value)),
    });
}

const detailFontSize = sizeModel('detail_font_size');
const buttonFontSize = sizeModel('button_font_size');
/* eslint-enable vue/no-mutating-props */

function detailError(lang: string, field: string): string | undefined {
    return props.form.errors[`detail.${lang}.${field}`];
}

// ตัวอย่างข้อความต้อนรับ (ภาษาหลัก) ตามฟอนต์/ขนาด/สีที่เลือก บนพื้นหลังที่ตั้งไว้
const defaultLang = computed(() => props.languages.find((l) => l.is_default)?.code ?? props.languages[0]?.code);
const previewText = computed(() => (defaultLang.value ? props.form.detail[defaultLang.value]?.detail : '') || 'ตัวอย่างข้อความต้อนรับ');
const previewStyle = computed<CSSProperties>(() => ({
    fontFamily: `'${props.form.detail_font_family}', sans-serif`,
    fontSize: `${props.form.detail_font_size}px`,
    color: props.form.detail_color,
    backgroundColor: props.form.background_color || '#FFFFFF',
}));

const card = 'rounded-2xl border border-gray-200 bg-white p-6 shadow-xs lg:p-8';
</script>

<template>
    <!-- 1. ข้อความ -->
    <section :class="card">
        <h2 class="text-base font-semibold text-gray-800">ข้อความ</h2>
        <p class="mt-0.5 text-xs text-gray-500">ชื่อใช้ในหลังบ้าน/ชื่อหน้าเว็บ (ไม่แสดงบนหน้า Intropage) ข้อความต้อนรับแสดงใต้รูปภาพ/วิดีโอ</p>

        <div class="mt-5 space-y-5">
            <LangFieldGroup label="ชื่อ" :languages="languages" required>
                <template #default="{ lang }">
                    <TextInput v-model="form.detail[lang.code].title" type="text" />
                    <InputError :message="detailError(lang.code, 'title')" />
                </template>
            </LangFieldGroup>

            <LangFieldGroup label="ข้อความต้อนรับ" :languages="languages">
                <template #default="{ lang }">
                    <Textarea v-model="form.detail[lang.code].detail" rows="3" />
                    <InputError :message="detailError(lang.code, 'detail')" />
                </template>
            </LangFieldGroup>

            <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                <h3 class="text-sm font-medium text-gray-700">รูปแบบข้อความต้อนรับ</h3>
                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel value="ฟอนต์ข้อความต้อนรับ" required />
                        <SearchableSelect v-model="form.detail_font_family" :options="fontOptions" />
                        <InputError :message="form.errors.detail_font_family" />
                    </div>
                    <div>
                        <InputLabel value="ขนาดฟอนต์ข้อความต้อนรับ" required />
                        <SearchableSelect v-model="detailFontSize" :options="sizeOptions(form.detail_font_size)" />
                        <InputError :message="form.errors.detail_font_size" />
                    </div>
                    <div>
                        <InputLabel value="สีตัวอักษร" required />
                        <ColorPickerInput v-model="form.detail_color" />
                        <InputError :message="form.errors.detail_color" />
                    </div>
                </div>
                <p class="mt-4 whitespace-pre-line rounded-lg border border-gray-200 px-4 py-3 text-center" :style="previewStyle">{{ previewText }}</p>
            </div>
        </div>
    </section>

    <!-- 2. สื่อหลัก -->
    <section :class="card">
        <h2 class="text-base font-semibold text-gray-800">รูปภาพ / วิดีโอ</h2>
        <p class="mt-0.5 text-xs text-gray-500">แสดงชิดขอบบนของหน้า Intropage</p>

        <div class="mt-5 space-y-5">
            <div>
                <InputLabel value="ประเภทการแสดงผล" required />
                <IntropageDisplayTypePicker v-model="form.display_type" />
                <InputError :message="form.errors.display_type" />
            </div>

            <div v-if="form.display_type === 'image'">
                <InputLabel value="รูปภาพ" required />
                <FilePickerField v-model="imageFile" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                <InputError :message="form.errors.image_file_id" />
            </div>
            <div v-else-if="form.display_type === 'vdo'">
                <InputLabel value="ไฟล์วิดีโอ" required />
                <FilePickerField v-model="vdoFile" :accept="['mp4']" />
                <InputError :message="form.errors.vdo_file_id" />
            </div>
            <div v-else>
                <InputLabel :value="form.display_type === 'youtubeurl' ? 'YouTube URL' : 'URL วิดีโอ'" required />
                <TextInput
                    v-model="form.vdo_url"
                    type="text"
                    :placeholder="form.display_type === 'youtubeurl' ? 'https://www.youtube.com/watch?v=...' : 'https://...'"
                />
                <InputError :message="form.errors.vdo_url" />
            </div>

            <div>
                <InputLabel value="ขนาดการแสดงผล" required />
                <IntropageDisplaySizePicker v-model="form.display_size" />
                <InputError :message="form.errors.display_size" />
            </div>
        </div>
    </section>

    <!-- 3. พื้นหลัง -->
    <section :class="card">
        <h2 class="text-base font-semibold text-gray-800">การจัดการพื้นหลัง</h2>

        <div class="mt-5 space-y-5">
            <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <InputLabel value="สีพื้นหลัง" />
                    <ColorPickerInput v-model="form.background_color" />
                    <InputError :message="form.errors.background_color" />
                </div>
                <div class="sm:col-span-4">
                    <InputLabel value="รูปภาพพื้นหลัง" />
                    <FilePickerField v-model="backgroundImage" :accept="['jpg', 'jpeg', 'png', 'gif', 'webp']" />
                    <InputError :message="form.errors.background_image_id" />
                </div>
            </div>

            <!-- ตัวเลือก CSS ของรูปพื้นหลัง — มีความหมายเฉพาะเมื่อเลือกรูปแล้ว -->
            <div v-if="backgroundImage.length" class="space-y-5 border-t border-gray-100 pt-5">
                <div>
                    <InputLabel value="การเรียงซ้ำ (Background Repeat)" />
                    <BackgroundRepeatPicker v-model="form.background_repeat" />
                    <InputError :message="form.errors.background_repeat" />
                </div>
                <div>
                    <InputLabel value="ขนาด (Background Size)" />
                    <BackgroundSizePicker v-model="form.background_size" />
                    <InputError :message="form.errors.background_size" />
                </div>
                <div>
                    <InputLabel value="การเลื่อน (Background Attachment)" />
                    <BackgroundAttachmentPicker v-model="form.background_attachment" />
                    <InputError :message="form.errors.background_attachment" />
                </div>
                <div>
                    <InputLabel value="ตำแหน่ง (Background Position)" />
                    <BackgroundPositionPicker v-model="form.background_position" />
                    <InputError :message="form.errors.background_position" />
                </div>
            </div>
        </div>
    </section>

    <!-- 4. ปุ่ม -->
    <section :class="card">
        <h2 class="text-base font-semibold text-gray-800">การจัดการปุ่ม</h2>
        <p class="mt-0.5 text-xs text-gray-500">ปุ่ม "เข้าหน้าแรก" มีอยู่เสมอ 1 ปุ่ม (ลบไม่ได้ แต่เรียงลำดับได้)</p>

        <div class="mt-5 space-y-5">
            <div>
                <YesNoCheckbox v-model="form.show_button" label="แสดงโซนปุ่ม" description="ไม่เลือก = หน้า Intropage ไม่มีปุ่มใด ๆ" />
                <InputError :message="form.errors.show_button" />
            </div>

            <template v-if="form.show_button === 'Y'">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="ขนาดฟอนต์ของปุ่ม" required />
                        <SearchableSelect v-model="buttonFontSize" :options="sizeOptions(form.button_font_size)" />
                        <InputError :message="form.errors.button_font_size" />
                    </div>
                    <div>
                        <InputLabel value="ฟอนต์ของปุ่ม" required />
                        <SearchableSelect v-model="form.button_font_family" :options="fontOptions" />
                        <InputError :message="form.errors.button_font_family" />
                    </div>
                </div>

                <div>
                    <ButtonList v-model="form.buttons" :languages="languages" :form-errors="form.errors" />
                    <InputError :message="form.errors.buttons" />
                </div>
            </template>
        </div>
    </section>

    <!-- 5. การเผยแพร่ -->
    <section :class="card">
        <h2 class="text-base font-semibold text-gray-800">การเผยแพร่</h2>

        <div class="mt-5 grid gap-4 sm:grid-cols-6">
            <div class="sm:col-span-3">
                <InputLabel value="วันที่ประกาศ" required />
                <DateTimeInput v-model="form.publish_date" />
                <InputError :message="form.errors.publish_date" />
            </div>
            <div class="sm:col-span-3">
                <InputLabel value="วันที่ปิดประกาศ" required />
                <DateTimeInput v-model="form.publish_down" />
                <InputError :message="form.errors.publish_down" />
            </div>
            <div class="sm:col-span-2">
                <InputLabel value="สถานะ" required />
                <SearchableSelect v-model="form.status" :options="STATUS_OPTIONS" />
                <InputError :message="form.errors.status" />
            </div>
        </div>
    </section>
</template>
