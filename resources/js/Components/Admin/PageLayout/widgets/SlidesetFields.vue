<script setup lang="ts">
import { computed, watch } from 'vue';
import { Laptop, Monitor, Smartphone, Tablet } from 'lucide-vue-next';
import FlagField from './FlagField.vue';
import OptionCardPicker from './OptionCardPicker.vue';
import ReadAllButton from './ReadAllButton.vue';
import SettingSection from './SettingSection.vue';
import SlidesetTextFields from './SlidesetTextFields.vue';
import TextStyleFields from '../TextStyleFields.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
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
    settingTextStyle,
    slidesetConfig,
} from '@/utils/pageWidget';
import type { SlidesetSetting } from '@/utils/pageWidget';
import {
    READ_ALL_DEFAULT_COLORS,
    READ_ALL_DEFAULT_TEXT,
    READ_ALL_ICONS,
    READ_ALL_ICON_POSITIONS,
    READ_ALL_POSITIONS,
    READ_ALL_STYLES,
    readAllIconComponent,
} from '@/utils/readAllButton';
import type { LanguageOption } from '@/types';

/**
 * ฟอร์มตั้งค่าเฉพาะของ widget Slideset (จาก article / จาก banner — อยู่ในกล่องบนสุดของ dialog ตั้งค่า widget) — มีหลายฟิลด์ จึงแบ่งเป็นการ์ดตามหัวข้อ:
 * ข้อมูลที่แสดง / จำนวนต่อแถวตามหน้าจอ / การเลื่อน / ส่วนของการ์ด (รูป, หัวเรื่อง, ข้อความเกริ่นนำ และของ article: วันที่, เข้าชม — ติ๊กเปิด/ปิดแต่ละส่วนได้
 * ปิดแล้วพับรายละเอียดทิ้ง) / ปุ่มอ่านทั้งหมด (เฉพาะ article) / การเปิดลิงก์ — แก้ค่าบน `setting` ที่ส่งเข้ามาตรง ๆ (เป็นสำเนา draft ของ dialog อยู่แล้ว)
 */
const props = defineProps<{
    widgetType: string;
    setting: SlidesetSetting;
    categories: { id: number; title: string | null }[];
    /** รายการชื่อฟอนต์ให้เลือก (จาก backend) */
    fonts: string[];
    /** ภาษาที่เปิดใช้ (ภาษาหลักก่อน) — ไว้กรอกข้อความปุ่มอ่านทั้งหมดแยกภาษา */
    languages: LanguageOption[];
    errors: Record<string, string>;
}>();

const config = computed(() => slidesetConfig(props.widgetType)!);
// ฟิลด์ของปุ่มอ่านทั้งหมด (เฉพาะ article — ฟอร์มส่วนนี้แสดงเมื่อประเภทมีปุ่มเท่านั้น จึงถือว่ามีค่าครบ)
const article = computed(() => props.setting as Required<SlidesetSetting>);

const categoryOptions = computed(() => props.categories.map((c) => ({ value: String(c.id), label: c.title || `หมวดหมู่ #${c.id}` })));

// SearchableSelect ผูกกับ string เสมอ — แปลงจาก/เป็นเลข id (ยังไม่เลือก = '' ↔ null)
// ฟิลด์หมวดหมู่ใน setting ชื่อต่างกันตามแหล่งข้อมูล (article_category_info_id / banner_category_info_id)
const settingRecord = computed(() => props.setting as unknown as Record<string, number | null>);
const category = computed({
    get: () => {
        const id = settingRecord.value[config.value.categoryKey];

        return id ? String(id) : '';
    },
    set: (value: string) => {
        settingRecord.value[config.value.categoryKey] = value === '' ? null : Number(value);
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

// ตัวอย่างในการ์ดเลือกตำแหน่งปุ่ม: แถบเล็กในกรอบจำลอง วางตามตำแหน่งนั้น (ชื่อ class ต้องเป็นตัวเต็มให้ Tailwind สแกนเจอ)
const POSITION_BAR: Record<string, string> = {
    top_left: 'left-1.5 top-1.5',
    top_center: 'left-1/2 top-1.5 -translate-x-1/2',
    top_right: 'right-1.5 top-1.5',
    bottom_left: 'bottom-1.5 left-1.5',
    bottom_center: 'bottom-1.5 left-1/2 -translate-x-1/2',
    bottom_right: 'bottom-1.5 right-1.5',
};

// ตัวอย่างข้อความปุ่มในการ์ดเลือก = ข้อความของภาษาหลักที่กรอก (ถ้าว่างใช้ข้อความมาตรฐาน)
const sampleText = computed(() => {
    const main = props.languages.find((l) => l.is_default)?.code;

    return (main ? props.setting.read_all_text?.[main]?.trim() : '') || READ_ALL_DEFAULT_TEXT;
});

// ไอคอนที่ใช้เป็นตัวอย่างในการ์ดเลือกตำแหน่งไอคอน/รูปแบบ (ถ้าเลือก "ไม่เลือก" ใช้ลูกศรขวาเป็นตัวอย่าง)
const sampleIcon = computed(() => (props.setting.read_all_icon && props.setting.read_all_icon !== 'none' ? props.setting.read_all_icon : 'arrow_right'));

// ตัวอักษรของปุ่ม (ขนาด/ฟอนต์/สี) ใช้ TextStyleFields เดียวกับข้อความส่วนอื่น — ชื่อฟิลด์ `read_all_font_size` ฯลฯ
const readAllTextStyle = computed(() => settingTextStyle(props.setting, 'read_all'));

// สีตัวอักษรเริ่มต้นต่างกันตามรูปแบบ (ปุ่ม = ขาว, ลิงก์ = น้ำเงิน) — เปลี่ยนรูปแบบแล้วถ้ายังใช้สีเริ่มต้นของแบบเดิมอยู่ ให้สลับตามแบบใหม่
// (ถ้าผู้ใช้ตั้งสีเองแล้วจะไม่แตะต้อง)
watch(
    () => props.setting.read_all_style,
    (style, oldStyle) => {
        if (style && oldStyle && props.setting.read_all_color?.toUpperCase() === READ_ALL_DEFAULT_COLORS[oldStyle].toUpperCase()) {
            props.setting.read_all_color = READ_ALL_DEFAULT_COLORS[style];
        }
    },
);

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

        <SettingSection title="จำนวนที่แสดงต่อแถว" description="กำหนดว่าแสดงกี่รายการต่อ 1 แถว ตามขนาดหน้าจอ">
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

        <SettingSection title="กล่องของการ์ด" description="เส้นขอบและมุมของกล่องที่ครอบแต่ละรายการ">
            <div class="grid gap-3 sm:grid-cols-2">
                <FlagField v-model="setting.show_border" label="แสดงเส้นขอบ" hint="เส้นบาง ๆ รอบกล่อง" />
                <FlagField v-model="setting.rounded_corners" label="มุมมน" hint="ปิดเพื่อให้เป็นมุมเหลี่ยม" />
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
            <div v-if="setting.image_fit === 'contain'">
                <InputLabel value="สีพื้นหลังของรูปภาพ" />
                <ColorPickerInput v-model="setting.image_background" transparent />
                <p class="mt-1 text-xs text-gray-500">แสดงตรงส่วนที่รูปไม่เต็มกรอบ (เลือก "โปร่งใส" ถ้าไม่ต้องการพื้นหลัง)</p>
                <InputError :message="errors.image_background" />
            </div>
            <FlagField v-model="setting.image_clickable" label="รูปภาพกดลิงก์ได้" :hint="config.hasReadAll ? 'ลิงก์ไปหน้าบทความ' : 'ใช้ลิงก์ของ banner — banner ที่ไม่มีลิงก์จะกดไม่ได้'" />
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_title" toggleable title="หัวเรื่อง">
            <SlidesetTextFields :setting="setting" part="title" :fonts="fonts" rich />
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_intro_text" toggleable title="ข้อความเกริ่นนำ">
            <SlidesetTextFields :setting="setting" part="intro_text" :fonts="fonts" rich />
        </SettingSection>

        <SettingSection v-if="config.hasMeta" v-model:enabled="setting.show_date" toggleable title="วันที่เผยแพร่">
            <SlidesetTextFields :setting="setting" part="date" :fonts="fonts" />
        </SettingSection>

        <SettingSection v-if="config.hasMeta" v-model:enabled="setting.show_views" toggleable title="จำนวนเข้าชม">
            <SlidesetTextFields :setting="setting" part="views" :fonts="fonts" />
        </SettingSection>

        <SettingSection v-if="config.hasReadAll" v-model:enabled="setting.show_read_all" toggleable title="ปุ่มอ่านทั้งหมด" description="ปุ่ม/ลิงก์ไปหน้ารวมของรายการทั้งหมด">
            <div>
                <InputLabel value="ตำแหน่งที่แสดง" />
                <OptionCardPicker v-model="article.read_all_position" name="read_all_position" :options="READ_ALL_POSITIONS" columns="grid-cols-3">
                    <template #visual="{ option }">
                        <div class="relative h-9 w-full rounded border border-gray-300 bg-gray-50">
                            <span class="absolute h-1.5 w-6 rounded-sm bg-brand-500" :class="POSITION_BAR[option.value]" />
                        </div>
                    </template>
                </OptionCardPicker>
            </div>

            <LangFieldGroup label="ข้อความแทน" :languages="languages" :description="`กรอกแทนข้อความ &quot;${READ_ALL_DEFAULT_TEXT}&quot; แยกตามภาษา — ไม่กรอกจะใช้ &quot;${READ_ALL_DEFAULT_TEXT}&quot;`">
                <template #default="{ lang }">
                    <TextInput v-model="article.read_all_text[lang.code]" :placeholder="READ_ALL_DEFAULT_TEXT" maxlength="100" />
                    <InputError :message="errors[`read_all_text.${lang.code}`]" />
                </template>
            </LangFieldGroup>

            <div>
                <InputLabel value="ไอคอนที่แสดงร่วมกับข้อความ" />
                <OptionCardPicker v-model="article.read_all_icon" name="read_all_icon" :options="READ_ALL_ICONS" columns="grid-cols-2 sm:grid-cols-4">
                    <template #visual="{ option }">
                        <component :is="readAllIconComponent(option.value)" v-if="readAllIconComponent(option.value)" class="size-6" />
                        <span v-else class="text-lg leading-none text-gray-300">—</span>
                    </template>
                </OptionCardPicker>
            </div>

            <div v-if="setting.read_all_icon !== 'none'">
                <InputLabel value="ตำแหน่งไอคอน" />
                <OptionCardPicker v-model="article.read_all_icon_position" name="read_all_icon_position" :options="READ_ALL_ICON_POSITIONS" columns="grid-cols-2">
                    <template #visual="{ option }">
                        <ReadAllButton :text="sampleText" :icon="sampleIcon" :icon-position="option.value as 'before' | 'after'" style-type="link" small />
                    </template>
                </OptionCardPicker>
            </div>

            <div>
                <InputLabel value="รูปแบบลิงก์" />
                <OptionCardPicker v-model="article.read_all_style" name="read_all_style" :options="READ_ALL_STYLES" columns="grid-cols-3">
                    <template #visual="{ option }">
                        <ReadAllButton
                            :text="sampleText"
                            :icon="setting.read_all_icon ?? 'none'"
                            :icon-position="setting.read_all_icon_position ?? 'after'"
                            :style-type="option.value as 'button' | 'link' | 'pill'"
                            small
                        />
                    </template>
                </OptionCardPicker>
            </div>

            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-3">
                <p class="text-sm font-medium text-gray-700">ตัวอักษรของปุ่ม</p>
                <TextStyleFields :text-style="readAllTextStyle" :fonts="fonts" :show-align="false" />
                <div v-if="article.read_all_style !== 'link'">
                    <InputLabel value="สีพื้นหลัง" />
                    <ColorPickerInput v-model="article.read_all_background" />
                    <p class="mt-1 text-xs text-gray-500">ใช้กับรูปแบบปุ่มและปุ่มมนใหญ่ (ลิงก์ข้อความไม่มีพื้นหลัง)</p>
                    <InputError :message="errors.read_all_background" />
                </div>
                <InputError :message="errors.read_all_font_size || errors.read_all_font_family || errors.read_all_color" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="ลิงก์ URL ปลายทาง" :required="true" />
                    <TextInput v-model="article.read_all_url" placeholder="https://example.com/news หรือ /th/news" maxlength="500" />
                    <p class="mt-1 text-xs text-gray-500">ขึ้นต้นด้วย http://, https:// หรือ / (ภายหลังจะเลือกจากเมนูของหน้าบ้านได้)</p>
                    <InputError :message="errors.read_all_url" />
                </div>
                <div>
                    <InputLabel value="เป้าหมายการเปิดลิงก์" />
                    <SearchableSelect v-model="article.read_all_link_target" :options="LINK_TARGET_OPTIONS" />
                </div>
            </div>
        </SettingSection>

        <SettingSection title="การเปิดลิงก์" description="ใช้ร่วมกับรูปภาพ หัวเรื่อง และข้อความเกริ่นนำที่ตั้งให้กดลิงก์ได้">
            <div class="max-w-sm">
                <InputLabel value="เป้าหมายการเปิดลิงก์" />
                <SearchableSelect v-model="setting.link_target" :options="LINK_TARGET_OPTIONS" />
            </div>
        </SettingSection>
    </div>
</template>
