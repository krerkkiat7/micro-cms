<script setup lang="ts">
import { computed, watch } from 'vue';
import { AlignVerticalJustifyCenter, AlignVerticalJustifyEnd, AlignVerticalJustifyStart, Laptop, Monitor, Smartphone, Tablet } from 'lucide-vue-next';
import CardBoxFields from './CardBoxFields.vue';
import FlagField from './FlagField.vue';
import OptionCardPicker from './OptionCardPicker.vue';
import RangeNumberField from './RangeNumberField.vue';
import ReadAllFields from './ReadAllFields.vue';
import SettingSection from './SettingSection.vue';
import SlidesetTextFields from './SlidesetTextFields.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import {
    GRID_CONTENT_ALIGN_OPTIONS,
    GRID_IMAGE_WIDTH_RANGE,
    LINK_TARGET_OPTIONS,
    SLIDESET_DEVICES,
    SLIDESET_IMAGE_FIT_OPTIONS,
    SLIDESET_PER_ROW_OPTIONS,
    SLIDESHOW_ASPECT_OPTIONS,
    SLIDESHOW_MAX_ITEMS_LIMIT,
    gridConfig,
} from '@/utils/pageWidget';
import type { GridContentAlign, GridSetting } from '@/utils/pageWidget';
import type { LanguageOption } from '@/types';

/**
 * ฟอร์มตั้งค่าเฉพาะของ widget Grid (จาก article / จาก banner — อยู่ในกล่องบนสุดของ dialog ตั้งค่า widget) — คล้าย Slideset แต่ไม่เลื่อน
 * (ไม่มีลูกศร/จุด/เลื่อนอัตโนมัติ) แบ่งเป็นการ์ดตามหัวข้อ: ข้อมูลที่แสดง / รูปแบบการแสดงผล (การ์ด, แถวที่มีรูปภาพ และเฉพาะ article: แถวที่แสดงวันที่แทนรูปภาพ) /
 * จำนวนคอลัมน์ต่อแถวตามหน้าจอ / กล่องของการ์ด / ส่วนของรายการ (รูป, หัวเรื่อง, ข้อความเกริ่นนำ และเฉพาะ article: วันที่, เข้าชม, กล่องวันที่เผยแพร่ —
 * ติ๊กเปิด/ปิดแต่ละส่วนได้ ปิดแล้วพับรายละเอียดทิ้ง — รูปแบบแถวบังคับแสดงหัวเรื่องเสมอ, แถวที่แสดงวันที่แทนรูปภาพบังคับแสดงวันที่เสมอ) /
 * ปุ่มอ่านทั้งหมด (เฉพาะ article) / การเปิดลิงก์ — แก้ค่าบน `setting` ที่ส่งเข้ามาตรง ๆ (เป็นสำเนา draft ของ dialog อยู่แล้ว)
 */
const props = defineProps<{
    widgetType: string;
    setting: GridSetting;
    categories: { id: number; title: string | null }[];
    /** รายการชื่อฟอนต์ให้เลือก (จาก backend) */
    fonts: string[];
    /** ภาษาที่เปิดใช้ (ภาษาหลักก่อน) — ไว้กรอกข้อความปุ่มอ่านทั้งหมดแยกภาษา */
    languages: LanguageOption[];
    errors: Record<string, string>;
}>();

const config = computed(() => gridConfig(props.widgetType)!);
// ฟิลด์ของปุ่มอ่านทั้งหมด (เฉพาะ article — ฟอร์มส่วนนี้แสดงเมื่อประเภทมีปุ่มเท่านั้น จึงถือว่ามีค่าครบ)
const article = computed(() => props.setting as Required<GridSetting>);

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

// ตำแหน่งของข้อมูล (รูปแบบแถว) — ไอคอนสื่อความหมายคู่ข้อความ; article แบบ "บน" วันที่/จำนวนเข้าชมอยู่ล่างเสมอ
const CONTENT_ALIGN_ICONS: Record<GridContentAlign, typeof AlignVerticalJustifyStart> = {
    top: AlignVerticalJustifyStart,
    center: AlignVerticalJustifyCenter,
    bottom: AlignVerticalJustifyEnd,
};
const contentAlignOptions = GRID_CONTENT_ALIGN_OPTIONS.map((option) => ({ ...option, icon: CONTENT_ALIGN_ICONS[option.value] }));
const contentAlignHint = computed(() => {
    const hints: Record<string, string> = config.value.hasMeta
        ? {
              top: 'หัวเรื่องและข้อความเกริ่นนำชิดด้านบน ส่วนวันที่และจำนวนเข้าชมอยู่ด้านล่างเสมอ',
              center: 'หัวเรื่อง ข้อความเกริ่นนำ วันที่และจำนวนเข้าชม อยู่กึ่งกลางแนวตั้ง',
              bottom: 'หัวเรื่อง ข้อความเกริ่นนำ วันที่และจำนวนเข้าชม ชิดด้านล่าง',
          }
        : {
              top: 'หัวเรื่องและข้อความเกริ่นนำชิดด้านบน',
              center: 'หัวเรื่องและข้อความเกริ่นนำอยู่กึ่งกลางแนวตั้ง',
              bottom: 'หัวเรื่องและข้อความเกริ่นนำชิดด้านล่าง',
          };

    return hints[props.setting.content_align] ?? hints.top;
});

// รูปแบบแถว (row_image / row_date) บังคับแสดงหัวเรื่องเสมอ, แถวที่แสดงวันที่แทนรูปภาพบังคับแสดงวันที่เสมอ — สอดคล้องกับที่ backend บังคับตอนบันทึก
watch(
    () => props.setting.display_type,
    (type) => {
        if (type !== 'card') {
            props.setting.show_title = 'Y';
        }

        if (type === 'row_date') {
            props.setting.show_date = 'Y';
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

        <SettingSection title="รูปแบบการแสดงผล">
            <OptionCardPicker v-model="setting.display_type" name="display_type" :options="config.displayTypeOptions" columns="grid-cols-1 sm:grid-cols-3">
                <template #visual="{ option }">
                    <svg viewBox="0 0 120 64" class="h-14 w-full text-gray-300">
                        <!-- card: การ์ดเรียง 3 ใบ รูปด้านบน ข้อความด้านล่าง -->
                        <template v-if="option.value === 'card'">
                            <rect x="4" y="4" width="34" height="56" rx="3" class="fill-gray-100" />
                            <rect x="6" y="6" width="30" height="18" rx="2" class="fill-brand-300" />
                            <rect x="6" y="28" width="26" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="6" y="35" width="22" height="3" rx="1.5" class="fill-gray-300" />
                            <rect x="43" y="4" width="34" height="56" rx="3" class="fill-gray-100" />
                            <rect x="45" y="6" width="30" height="18" rx="2" class="fill-brand-400" />
                            <rect x="45" y="28" width="26" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="45" y="35" width="22" height="3" rx="1.5" class="fill-gray-300" />
                            <rect x="82" y="4" width="34" height="56" rx="3" class="fill-gray-100" />
                            <rect x="84" y="6" width="30" height="18" rx="2" class="fill-brand-300" />
                            <rect x="84" y="28" width="26" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="84" y="35" width="22" height="3" rx="1.5" class="fill-gray-300" />
                        </template>
                        <!-- row_image: แถวรูปซ้าย + ข้อความขวา 3 แถว -->
                        <template v-else-if="option.value === 'row_image'">
                            <rect x="4" y="4" width="112" height="15" rx="2" class="fill-gray-100" />
                            <rect x="6" y="6" width="20" height="11" rx="1.5" class="fill-brand-300" />
                            <rect x="30" y="7" width="60" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="30" y="13" width="40" height="3" rx="1.5" class="fill-gray-300" />
                            <rect x="4" y="24" width="112" height="15" rx="2" class="fill-gray-100" />
                            <rect x="6" y="26" width="20" height="11" rx="1.5" class="fill-brand-400" />
                            <rect x="30" y="27" width="60" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="30" y="33" width="40" height="3" rx="1.5" class="fill-gray-300" />
                            <rect x="4" y="44" width="112" height="15" rx="2" class="fill-gray-100" />
                            <rect x="6" y="46" width="20" height="11" rx="1.5" class="fill-brand-300" />
                            <rect x="30" y="47" width="60" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="30" y="53" width="40" height="3" rx="1.5" class="fill-gray-300" />
                        </template>
                        <!-- row_date: กล่องวันที่ (เลข + เดือน) ซ้าย + ข้อความขวา 3 แถว -->
                        <template v-else>
                            <rect x="4" y="4" width="112" height="15" rx="2" class="fill-gray-100" />
                            <rect x="6" y="6" width="16" height="11" rx="1.5" class="fill-brand-200" />
                            <rect x="9" y="8" width="10" height="4" rx="1" class="fill-brand-600" />
                            <rect x="10" y="13" width="8" height="2.5" rx="1" class="fill-brand-500" />
                            <rect x="26" y="7" width="60" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="26" y="13" width="40" height="3" rx="1.5" class="fill-gray-300" />
                            <rect x="4" y="24" width="112" height="15" rx="2" class="fill-gray-100" />
                            <rect x="6" y="26" width="16" height="11" rx="1.5" class="fill-brand-200" />
                            <rect x="9" y="28" width="10" height="4" rx="1" class="fill-brand-600" />
                            <rect x="10" y="33" width="8" height="2.5" rx="1" class="fill-brand-500" />
                            <rect x="26" y="27" width="60" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="26" y="33" width="40" height="3" rx="1.5" class="fill-gray-300" />
                            <rect x="4" y="44" width="112" height="15" rx="2" class="fill-gray-100" />
                            <rect x="6" y="46" width="16" height="11" rx="1.5" class="fill-brand-200" />
                            <rect x="9" y="48" width="10" height="4" rx="1" class="fill-brand-600" />
                            <rect x="10" y="53" width="8" height="2.5" rx="1" class="fill-brand-500" />
                            <rect x="26" y="47" width="60" height="4" rx="1.5" class="fill-brand-400" />
                            <rect x="26" y="53" width="40" height="3" rx="1.5" class="fill-gray-300" />
                        </template>
                    </svg>
                </template>
            </OptionCardPicker>
        </SettingSection>

        <SettingSection title="จำนวนคอลัมน์ที่แสดง" description="กำหนดว่าแสดงกี่คอลัมน์ต่อ 1 แถว ตามขนาดหน้าจอ">
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

        <SettingSection title="กล่องของการ์ด" description="เส้นขอบ สี และมุมของกล่องที่ครอบแต่ละรายการ">
            <CardBoxFields :setting="setting" :errors="errors">
                <div v-if="setting.display_type !== 'card'">
                    <InputLabel value="ตำแหน่งของข้อมูล" />
                    <SegmentedChoice v-model="setting.content_align" :options="contentAlignOptions" />
                    <p class="mt-1 text-xs text-gray-500">{{ contentAlignHint }}</p>
                </div>
            </CardBoxFields>
        </SettingSection>

        <SettingSection v-if="setting.display_type !== 'row_date'" v-model:enabled="setting.show_image" toggleable title="รูปภาพ">
            <p v-if="setting.display_type === 'row_image'" class="text-xs text-gray-500">ถ้าปิด พื้นที่รูปภาพจะหายไปและส่วนข้อมูลขยายเต็มแทน</p>
            <div v-if="setting.display_type === 'row_image'">
                <InputLabel value="ความกว้างของพื้นที่แสดงรูปภาพ (%)" />
                <RangeNumberField v-model="setting.image_width_percent" :min="GRID_IMAGE_WIDTH_RANGE.min" :max="GRID_IMAGE_WIDTH_RANGE.max" unit="%" />
                <p class="mt-1 text-xs text-gray-500">สัดส่วนความกว้างของรูปเทียบกับทั้งแถว ที่เหลือเป็นพื้นที่ข้อความ — แนะนำ 20 - 35% (น้อยไปรูปจะเล็กมาก มากไปข้อความจะเบียด)</p>
                <InputError :message="errors.image_width_percent" />
            </div>
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
            <FlagField v-model="setting.image_clickable" label="รูปภาพกดลิงก์ได้" hint="ลิงก์ไปหน้าบทความ" />
        </SettingSection>

        <SettingSection
            v-model:enabled="setting.show_title"
            :toggleable="setting.display_type === 'card'"
            title="หัวเรื่อง"
            :description="setting.display_type !== 'card' ? 'บังคับแสดงเสมอในรูปแบบแถว' : undefined"
        >
            <SlidesetTextFields :setting="setting" part="title" :fonts="fonts" rich />
        </SettingSection>

        <SettingSection v-model:enabled="setting.show_intro_text" toggleable title="ข้อความเกริ่นนำ">
            <SlidesetTextFields :setting="setting" part="intro_text" :fonts="fonts" rich />
        </SettingSection>

        <!-- รูปแบบ row_date ไม่ใช้สไตล์วันที่บรรทัดเดียวนี้เลย (ใช้ "กล่องวันที่เผยแพร่" ด้านล่างแทน) จึงซ่อนไปทั้งหมดไม่ให้สับสน -->
        <SettingSection
            v-if="config.hasMeta && setting.display_type !== 'row_date'"
            v-model:enabled="setting.show_date"
            toggleable
            title="วันที่เผยแพร่"
        >
            <SlidesetTextFields :setting="setting" part="date" :fonts="fonts" />
        </SettingSection>

        <SettingSection v-if="config.hasMeta" v-model:enabled="setting.show_views" toggleable title="จำนวนเข้าชม">
            <SlidesetTextFields :setting="setting" part="views" :fonts="fonts" />
        </SettingSection>

        <SettingSection v-if="config.hasMeta && setting.display_type === 'row_date'" title="กล่องวันที่เผยแพร่" description="แทนที่พื้นที่รูปภาพทั้งหมดในรูปแบบนี้">
            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-3">
                <p class="text-sm font-medium text-gray-700">วัน (ตัวเลขวันที่)</p>
                <SlidesetTextFields :setting="setting" part="date_day" :fonts="fonts" />
            </div>
            <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-3">
                <p class="text-sm font-medium text-gray-700">เดือน/ปี</p>
                <SlidesetTextFields :setting="setting" part="date_month" :fonts="fonts" />
            </div>
            <div>
                <InputLabel value="สีพื้นหลังกล่อง" />
                <ColorPickerInput v-model="setting.date_box_background" transparent />
                <InputError :message="errors.date_box_background" />
            </div>
        </SettingSection>

        <ReadAllFields v-if="config.hasReadAll" :setting="article" :fonts="fonts" :languages="languages" :errors="errors" />

        <SettingSection title="การเปิดลิงก์" description="ใช้ร่วมกับรูปภาพ หัวเรื่อง และข้อความเกริ่นนำที่ตั้งให้กดลิงก์ได้">
            <div class="max-w-sm">
                <InputLabel value="เป้าหมายการเปิดลิงก์" />
                <SearchableSelect v-model="setting.link_target" :options="LINK_TARGET_OPTIONS" />
            </div>
        </SettingSection>
    </div>
</template>
