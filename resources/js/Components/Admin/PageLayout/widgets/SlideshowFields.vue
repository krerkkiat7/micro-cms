<script setup lang="ts">
import { computed } from 'vue';
import FlagField from './FlagField.vue';
import TextStyleFields from '../TextStyleFields.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import {
    LINK_TARGET_OPTIONS,
    SLIDESHOW_ASPECT_OPTIONS,
    SLIDESHOW_EFFECT_OPTIONS,
    SLIDESHOW_INTERVAL_RANGE,
    SLIDESHOW_MAX_ITEMS_LIMIT,
    SLIDESHOW_SPEED_RANGE,
    SLIDESHOW_TEXT_ALIGN_OPTIONS,
    SLIDESHOW_TEXT_WIDTH_OPTIONS,
    slideshowConfig,
    settingTextStyle,
} from '@/utils/pageWidget';
import type { SlideshowCommonSetting } from '@/utils/pageWidget';

/**
 * ฟอร์มตั้งค่าเฉพาะของ widget กลุ่ม Slideshow (จาก banner / จาก article — ตั้งค่าเหมือนกัน ต่างที่หมวดหมู่ที่ให้เลือกและตัวเลือกการเรียงลำดับ)
 * อยู่ในกล่องบนสุดของ dialog ตั้งค่า widget — แก้ค่าบน `setting` ที่ส่งเข้ามาตรง ๆ (เป็นสำเนา draft ของ dialog อยู่แล้ว)
 * `errors` = ข้อความ error ตามชื่อฟิลด์จากตัวตรวจฝั่งหน้าจอ
 */
const props = defineProps<{
    widgetType: string;
    setting: SlideshowCommonSetting;
    categories: { id: number; title: string | null }[];
    /** รายการชื่อฟอนต์ให้เลือก (จาก backend) ใช้กับตัวอักษรของหัวเรื่อง/ข้อความเกริ่นนำบนภาพ */
    fonts: string[];
    errors: Record<string, string>;
}>();

const config = computed(() => slideshowConfig(props.widgetType)!);
// ฟิลด์หมวดหมู่ใน setting ชื่อต่างกันตามประเภท (banner_category_info_id / article_category_info_id)
const settingRecord = computed(() => props.setting as unknown as Record<string, number | null>);

const categoryOptions = computed(() => props.categories.map((c) => ({ value: String(c.id), label: c.title || `หมวดหมู่ #${c.id}` })));

// SearchableSelect ผูกกับ string เสมอ — แปลงจาก/เป็นเลข id (ยังไม่เลือก = '' ↔ null)
const category = computed({
    get: () => {
        const id = settingRecord.value[config.value.categoryKey];

        return id ? String(id) : '';
    },
    set: (value: string) => {
        settingRecord.value[config.value.categoryKey] = value === '' ? null : Number(value);
    },
});

// ช่องตัวเลข: ค่าที่กรอกไม่ใช่เลขจำนวนเต็ม = 0 (ตัวตรวจตอนกด "ตกลง" จะแจ้งว่าอยู่นอกช่วง)
function numberModel(field: 'autoplay_interval' | 'transition_speed') {
    return computed({
        get: () => String(props.setting[field]),
        set: (value: string) => {
            const parsed = Number.parseInt(value, 10);
            props.setting[field] = Number.isNaN(parsed) ? 0 : parsed;
        },
    });
}

// จำนวนที่แสดงสูงสุด: ช่องว่างหรือ 0 = แสดงทั้งหมด (แสดงเป็นช่องว่างเมื่อเป็น 0)
const maxItems = computed({
    get: () => (props.setting.max_items > 0 ? String(props.setting.max_items) : ''),
    set: (value: string) => {
        const parsed = Number.parseInt(value, 10);
        props.setting.max_items = Number.isNaN(parsed) ? 0 : parsed;
    },
});

// ตัวอักษรของหัวเรื่อง/ข้อความเกริ่นนำบนภาพ มองเป็น TextStyle (ขนาด/ฟอนต์/สี) ให้ใช้กับ TextStyleFields
const titleStyle = computed(() => settingTextStyle(props.setting, 'title'));
const introStyle = computed(() => settingTextStyle(props.setting, 'intro_text'));

const interval = numberModel('autoplay_interval');
const speed = numberModel('transition_speed');

const hasText = computed(() => props.setting.show_title === 'Y' || props.setting.show_intro_text === 'Y');
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <InputLabel :value="config.categoryLabel" required />
                <SearchableSelect v-model="category" :options="categoryOptions" :placeholder="`เลือก${config.categoryLabel}`" />
                <p v-if="categories.length === 0" class="mt-1 text-xs text-amber-600">ยังไม่มี{{ config.categoryLabel }} ที่เปิดใช้งาน — สร้างที่เมนูของโมดูลนั้นก่อน</p>
                <InputError :message="errors[config.categoryKey]" />
            </div>
            <div>
                <InputLabel value="ลำดับการเรียงลำดับ" />
                <SearchableSelect v-model="setting.sort_by" :options="config.sortOptions" />
            </div>
            <div>
                <InputLabel value="จำนวนที่แสดงสูงสุด" />
                <TextInput v-model="maxItems" type="number" min="0" :max="SLIDESHOW_MAX_ITEMS_LIMIT" placeholder="0" />
                <p class="mt-1 text-xs text-gray-500">หากไม่กรอกหรือเป็น 0 จะแสดงทั้งหมด</p>
                <InputError :message="errors.max_items" />
            </div>
            <div>
                <InputLabel value="สัดส่วนภาพ" />
                <SearchableSelect v-model="setting.aspect_ratio" :options="SLIDESHOW_ASPECT_OPTIONS" />
                <p class="mt-1 text-xs text-gray-500">ภาพที่สัดส่วนต่างกันจะถูกครอปให้เต็มกรอบ</p>
            </div>
        </div>

        <div class="space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">การเลื่อน</h4>
            <div class="grid gap-3 sm:grid-cols-3">
                <FlagField v-model="setting.show_arrows" label="แสดงลูกศร" hint="ให้กดเลื่อนเองได้" />
                <FlagField v-model="setting.show_dots" label="แสดงจุด" hint="อยู่ด้านล่างในกรอบภาพ" />
                <FlagField v-model="setting.autoplay" label="เลื่อนอัตโนมัติ" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div v-if="setting.autoplay === 'Y'">
                    <InputLabel value="ค้างต่อภาพ (วินาที)" />
                    <TextInput v-model="interval" type="number" :min="SLIDESHOW_INTERVAL_RANGE.min" :max="SLIDESHOW_INTERVAL_RANGE.max" />
                    <InputError :message="errors.autoplay_interval" />
                </div>
                <div>
                    <InputLabel value="ความเร็วเปลี่ยนภาพ (มิลลิวินาที)" />
                    <TextInput v-model="speed" type="number" :min="SLIDESHOW_SPEED_RANGE.min" :max="SLIDESHOW_SPEED_RANGE.max" step="100" />
                    <InputError :message="errors.transition_speed" />
                </div>
                <div>
                    <InputLabel value="ประเภทการเลื่อน" />
                    <SearchableSelect v-model="setting.transition_effect" :options="SLIDESHOW_EFFECT_OPTIONS" />
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">ลิงก์</h4>
            <div class="grid gap-4 sm:grid-cols-2">
                <FlagField v-model="setting.is_clickable" label="กดลิงก์ได้" :hint="config.linkHint" />
                <div v-if="setting.is_clickable === 'Y'">
                    <InputLabel value="เป้าหมายการเปิดลิงก์" />
                    <SearchableSelect v-model="setting.link_target" :options="LINK_TARGET_OPTIONS" />
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">ข้อความบนภาพ</h4>
            <div class="grid gap-3 sm:grid-cols-2">
                <FlagField v-model="setting.show_title" label="แสดงหัวเรื่องบนภาพ" :hint="config.titleHint" />
                <FlagField v-model="setting.show_intro_text" label="แสดงข้อความเกริ่นนำบนภาพ" />
            </div>
            <div v-if="hasText" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="ตำแหน่งที่แสดง" />
                    <SearchableSelect v-model="setting.text_align" :options="SLIDESHOW_TEXT_ALIGN_OPTIONS" />
                </div>
                <div>
                    <InputLabel value="ขอบเขตของข้อความ" />
                    <SearchableSelect v-model="setting.text_width" :options="SLIDESHOW_TEXT_WIDTH_OPTIONS" />
                </div>
            </div>
            <div v-if="setting.show_title === 'Y'" class="space-y-2 rounded-lg border border-gray-200 bg-white p-3">
                <h5 class="text-xs font-medium text-gray-600">ตัวอักษรของหัวเรื่อง</h5>
                <TextStyleFields :text-style="titleStyle" :fonts="fonts" :show-align="false" />
            </div>
            <div v-if="setting.show_intro_text === 'Y'" class="space-y-2 rounded-lg border border-gray-200 bg-white p-3">
                <h5 class="text-xs font-medium text-gray-600">ตัวอักษรของข้อความเกริ่นนำ</h5>
                <TextStyleFields :text-style="introStyle" :fonts="fonts" :show-align="false" />
            </div>
        </div>
    </div>
</template>
