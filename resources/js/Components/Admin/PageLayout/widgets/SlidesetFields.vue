<script setup lang="ts">
import { computed } from 'vue';
import { Laptop, Monitor, Smartphone, Tablet } from 'lucide-vue-next';
import FlagField from './FlagField.vue';
import SettingSection from './SettingSection.vue';
import SlidesetTextFields from './SlidesetTextFields.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import {
    LINK_TARGET_OPTIONS,
    SLIDESET_DEVICES,
    SLIDESET_IMAGE_FIT_OPTIONS,
    SLIDESET_PER_ROW_OPTIONS,
    SLIDESHOW_ASPECT_OPTIONS,
    SLIDESHOW_INTERVAL_RANGE,
    SLIDESHOW_MAX_ITEMS_LIMIT,
    SLIDESHOW_SPEED_RANGE,
    slidesetConfig,
} from '@/utils/pageWidget';
import type { SlidesetSetting } from '@/utils/pageWidget';

/**
 * ฟอร์มตั้งค่าเฉพาะของ widget "Slideset จาก article" (อยู่ในกล่องบนสุดของ dialog ตั้งค่า widget) — มีหลายฟิลด์ จึงแบ่งเป็นการ์ดตามหัวข้อ:
 * ข้อมูลที่แสดง / จำนวนต่อแถวตามหน้าจอ / การเลื่อน / ส่วนของการ์ด (รูป, หัวเรื่อง, ข้อความเกริ่นนำ, วันที่, เข้าชม — ติ๊กเปิด/ปิดแต่ละส่วนได้
 * ปิดแล้วพับรายละเอียดทิ้ง) / การเปิดลิงก์ — แก้ค่าบน `setting` ที่ส่งเข้ามาตรง ๆ (เป็นสำเนา draft ของ dialog อยู่แล้ว)
 */
const props = defineProps<{
    widgetType: string;
    setting: SlidesetSetting;
    categories: { id: number; title: string | null }[];
    /** รายการชื่อฟอนต์ให้เลือก (จาก backend) */
    fonts: string[];
    errors: Record<string, string>;
}>();

const config = computed(() => slidesetConfig(props.widgetType)!);

const categoryOptions = computed(() => props.categories.map((c) => ({ value: String(c.id), label: c.title || `หมวดหมู่ #${c.id}` })));

// SearchableSelect ผูกกับ string เสมอ — แปลงจาก/เป็นเลข id (ยังไม่เลือก = '' ↔ null)
const category = computed({
    get: () => (props.setting.article_category_info_id ? String(props.setting.article_category_info_id) : ''),
    set: (value: string) => {
        props.setting.article_category_info_id = value === '' ? null : Number(value);
    },
});

// จำนวนที่แสดงสูงสุด: ช่องว่างหรือ 0 = แสดงทั้งหมด (แสดงเป็นช่องว่างเมื่อเป็น 0)
const maxItems = computed({
    get: () => (props.setting.max_items > 0 ? String(props.setting.max_items) : ''),
    set: (value: string) => {
        const parsed = Number.parseInt(value, 10);
        props.setting.max_items = Number.isNaN(parsed) ? 0 : parsed;
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

const interval = numberModel('autoplay_interval');
const speed = numberModel('transition_speed');

const DEVICE_ICONS = { pc: Monitor, notebook: Laptop, tablet: Tablet, mobile: Smartphone };
const perRow = (key: (typeof SLIDESET_DEVICES)[number]['key']) =>
    computed({
        get: () => String(props.setting[`per_row_${key}` as const]),
        set: (value: string) => {
            props.setting[`per_row_${key}` as const] = Number(value);
        },
    });
const perRowModels = Object.fromEntries(SLIDESET_DEVICES.map((d) => [d.key, perRow(d.key)]));
</script>

<template>
    <div class="space-y-3">
        <SettingSection title="ข้อมูลที่แสดง">
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
                    <InputLabel value="จำนวนข้อมูลที่แสดงทั้งหมด" />
                    <TextInput v-model="maxItems" type="number" min="0" :max="SLIDESHOW_MAX_ITEMS_LIMIT" placeholder="0" />
                    <p class="mt-1 text-xs text-gray-500">หากไม่กรอกหรือเป็น 0 จะแสดงทั้งหมด</p>
                    <InputError :message="errors.max_items" />
                </div>
            </div>
        </SettingSection>

        <SettingSection title="จำนวนที่แสดงต่อแถว" description="กำหนดว่าแสดงกี่บทความต่อ 1 แถว ตามขนาดหน้าจอ">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div v-for="device in SLIDESET_DEVICES" :key="device.key">
                    <InputLabel>
                        <span class="inline-flex items-center gap-1.5">
                            <component :is="DEVICE_ICONS[device.key]" class="size-4 text-gray-500" /> {{ device.label }}
                        </span>
                    </InputLabel>
                    <SearchableSelect v-model="perRowModels[device.key].value" :options="SLIDESET_PER_ROW_OPTIONS" />
                    <p class="mt-1 text-[11px] text-gray-400">{{ device.range }}</p>
                    <InputError :message="errors[`per_row_${device.key}`]" />
                </div>
            </div>
        </SettingSection>

        <SettingSection title="การเลื่อน">
            <div class="grid gap-3 sm:grid-cols-3">
                <FlagField v-model="setting.show_arrows" label="แสดงลูกศร" hint="ให้กดเลื่อนเองได้" />
                <FlagField v-model="setting.show_dots" label="แสดงจุด" hint="อยู่ด้านล่างใต้การ์ด" />
                <FlagField v-model="setting.autoplay" label="เลื่อนอัตโนมัติ" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-if="setting.autoplay === 'Y'">
                    <InputLabel value="ค้างต่อภาพ (วินาที)" />
                    <TextInput v-model="interval" type="number" :min="SLIDESHOW_INTERVAL_RANGE.min" :max="SLIDESHOW_INTERVAL_RANGE.max" />
                    <InputError :message="errors.autoplay_interval" />
                </div>
                <div>
                    <InputLabel value="ความเร็วในการเลื่อน (มิลลิวินาที)" />
                    <TextInput v-model="speed" type="number" :min="SLIDESHOW_SPEED_RANGE.min" :max="SLIDESHOW_SPEED_RANGE.max" step="100" />
                    <InputError :message="errors.transition_speed" />
                </div>
            </div>
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_image" toggleable title="รูปภาพ">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="อัตราส่วนของรูปภาพ" />
                    <SearchableSelect v-model="setting.aspect_ratio" :options="SLIDESHOW_ASPECT_OPTIONS" />
                </div>
                <div>
                    <InputLabel value="ประเภทการแสดงรูปภาพ" />
                    <SearchableSelect v-model="setting.image_fit" :options="SLIDESET_IMAGE_FIT_OPTIONS" />
                </div>
            </div>
            <FlagField v-model="setting.image_clickable" label="รูปภาพกดลิงก์ได้" hint="ลิงก์ไปหน้าบทความ" />
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_title" toggleable title="หัวเรื่อง">
            <SlidesetTextFields :setting="setting" part="title" :fonts="fonts" rich />
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_intro_text" toggleable title="ข้อความเกริ่นนำ">
            <SlidesetTextFields :setting="setting" part="intro_text" :fonts="fonts" rich />
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_date" toggleable title="วันที่เผยแพร่">
            <SlidesetTextFields :setting="setting" part="date" :fonts="fonts" />
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_views" toggleable title="จำนวนเข้าชม">
            <SlidesetTextFields :setting="setting" part="views" :fonts="fonts" />
        </SettingSection>

        <SettingSection title="การเปิดลิงก์" description="ใช้ร่วมกับรูปภาพ หัวเรื่อง และข้อความเกริ่นนำที่ตั้งให้กดลิงก์ได้">
            <div class="max-w-sm">
                <InputLabel value="เป้าหมายการเปิดลิงก์" />
                <SearchableSelect v-model="setting.link_target" :options="LINK_TARGET_OPTIONS" />
            </div>
        </SettingSection>
    </div>
</template>
