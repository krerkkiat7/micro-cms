<script setup lang="ts">
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Link2, ListTree } from 'lucide-vue-next';
import OptionCardPicker from './OptionCardPicker.vue';
import ReadAllButton from './ReadAllButton.vue';
import SettingSection from './SettingSection.vue';
import TextStyleFields from '../TextStyleFields.vue';
import ColorPickerInput from '@/Components/Admin/ColorPickerInput.vue';
import SegmentedChoice from '@/Components/Admin/Template/SegmentedChoice.vue';
import LangFieldGroup from '@/Components/Admin/LangFieldGroup.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import { LINK_TARGET_OPTIONS, settingTextStyle } from '@/utils/pageWidget';
import {
    READ_ALL_DEFAULT_COLORS,
    READ_ALL_DEFAULT_TEXT,
    READ_ALL_ICONS,
    READ_ALL_ICON_POSITIONS,
    READ_ALL_LINK_TYPES,
    READ_ALL_POSITIONS,
    READ_ALL_STYLES,
    readAllIconComponent,
} from '@/utils/readAllButton';
import type { FrontMenuPickerOption, ReadAllSettingFields } from '@/utils/readAllButton';
import type { WidgetOptions } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * ฟอร์มตั้งค่าปุ่ม "อ่านทั้งหมด" — ใช้ร่วมกันระหว่าง widget ที่มีปุ่มนี้ (Slideset จาก article, Grid จาก article — ชื่อฟิลด์ตรงกันทุกตัว
 * ดู HasReadAllButton ฝั่ง backend) ครอบด้วย SettingSection ที่ติ๊กเปิด/ปิดได้ในตัว (v-model:enabled="setting.show_read_all")
 * แก้ค่าบน `setting` ที่ส่งเข้ามาตรง ๆ (เป็นสำเนา draft ของ dialog อยู่แล้ว)
 */
const props = defineProps<{
    setting: ReadAllSettingFields;
    fonts: string[];
    /** ภาษาที่เปิดใช้ (ภาษาหลักก่อน) — ไว้กรอกข้อความปุ่มอ่านทั้งหมดแยกภาษา */
    languages: LanguageOption[];
    errors: Record<string, string>;
}>();

// ลิงก์ปลายทาง: เลือกจากเมนูหน้าบ้าน (รายการจาก prop widgetOptions ของหน้าโครงสร้าง) หรือกำหนด URL เอง
const LINK_TYPE_ICONS = { menu: ListTree, custom: Link2 };
const linkTypeOptions = READ_ALL_LINK_TYPES.map((option) => ({ ...option, icon: LINK_TYPE_ICONS[option.value] }));

const page = usePage();
const menus = computed<FrontMenuPickerOption[]>(() => (page.props.widgetOptions as WidgetOptions | undefined)?.front_menus ?? []);

// แสดงเมนูทั้งหมดที่เปิดใช้งาน เยื้องตามระดับ — เมนูหัวข้อ/ไม่กำหนดเลือกไม่ได้ (ไม่มีลิงก์ของตัวเอง)
const menuOptions = computed(() =>
    menus.value.map((menu) => ({
        value: String(menu.id),
        label: `${'\u00A0\u00A0\u00A0'.repeat(menu.depth)}${menu.depth > 0 ? '└ ' : ''}${menu.name || `เมนู #${menu.id}`}${menu.selectable ? '' : ' (หัวข้อ)'}`,
        disabled: !menu.selectable,
    })),
);

const menuId = computed({
    get: () => (props.setting.read_all_menu_id ? String(props.setting.read_all_menu_id) : ''),
    set: (value: string) => {
        props.setting.read_all_menu_id = value === '' ? null : Number(value);
    },
});

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

// ตัวอย่างในการ์ดเลือกตำแหน่งปุ่ม: แถบเล็กในกรอบจำลอง วางตามตำแหน่งนั้น (ชื่อ class ต้องเป็นตัวเต็มให้ Tailwind สแกนเจอ)
const POSITION_BAR: Record<string, string> = {
    top_left: 'left-1.5 top-1.5',
    top_center: 'left-1/2 top-1.5 -translate-x-1/2',
    top_right: 'right-1.5 top-1.5',
    bottom_left: 'bottom-1.5 left-1.5',
    bottom_center: 'bottom-1.5 left-1/2 -translate-x-1/2',
    bottom_right: 'bottom-1.5 right-1.5',
};
</script>

<template>
    <SettingSection v-model:enabled="setting.show_read_all" toggleable title="ปุ่มอ่านทั้งหมด" description="ปุ่ม/ลิงก์ไปหน้ารวมของรายการทั้งหมด">
        <div>
            <InputLabel value="ตำแหน่งที่แสดง" />
            <OptionCardPicker v-model="setting.read_all_position" name="read_all_position" :options="READ_ALL_POSITIONS" columns="grid-cols-3">
                <template #visual="{ option }">
                    <div class="relative h-9 w-full rounded border border-gray-300 bg-gray-50">
                        <span class="absolute h-1.5 w-6 rounded-sm bg-brand-500" :class="POSITION_BAR[option.value]" />
                    </div>
                </template>
            </OptionCardPicker>
        </div>

        <LangFieldGroup label="ข้อความแทน" :languages="languages" :description="`กรอกแทนข้อความ &quot;${READ_ALL_DEFAULT_TEXT}&quot; แยกตามภาษา — ไม่กรอกจะใช้ &quot;${READ_ALL_DEFAULT_TEXT}&quot;`">
            <template #default="{ lang }">
                <TextInput v-model="setting.read_all_text[lang.code]" :placeholder="READ_ALL_DEFAULT_TEXT" maxlength="100" />
                <InputError :message="errors[`read_all_text.${lang.code}`]" />
            </template>
        </LangFieldGroup>

        <div>
            <InputLabel value="ไอคอนที่แสดงร่วมกับข้อความ" />
            <OptionCardPicker v-model="setting.read_all_icon" name="read_all_icon" :options="READ_ALL_ICONS" columns="grid-cols-2 sm:grid-cols-4">
                <template #visual="{ option }">
                    <component :is="readAllIconComponent(option.value)" v-if="readAllIconComponent(option.value)" class="size-6" />
                    <span v-else class="text-lg leading-none text-gray-300">—</span>
                </template>
            </OptionCardPicker>
        </div>

        <div v-if="setting.read_all_icon !== 'none'">
            <InputLabel value="ตำแหน่งไอคอน" />
            <OptionCardPicker v-model="setting.read_all_icon_position" name="read_all_icon_position" :options="READ_ALL_ICON_POSITIONS" columns="grid-cols-2">
                <template #visual="{ option }">
                    <ReadAllButton :text="sampleText" :icon="sampleIcon" :icon-position="option.value as 'before' | 'after'" style-type="link" small />
                </template>
            </OptionCardPicker>
        </div>

        <div>
            <InputLabel value="รูปแบบลิงก์" />
            <OptionCardPicker v-model="setting.read_all_style" name="read_all_style" :options="READ_ALL_STYLES" columns="grid-cols-3">
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
            <div v-if="setting.read_all_style !== 'link'">
                <InputLabel value="สีพื้นหลัง" />
                <ColorPickerInput v-model="setting.read_all_background" />
                <p class="mt-1 text-xs text-gray-500">ใช้กับรูปแบบปุ่มและปุ่มมนใหญ่ (ลิงก์ข้อความไม่มีพื้นหลัง)</p>
                <InputError :message="errors.read_all_background" />
            </div>
            <InputError :message="errors.read_all_font_size || errors.read_all_font_family || errors.read_all_color" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <InputLabel value="ประเภทลิงก์ปลายทาง" />
                <SegmentedChoice v-model="setting.read_all_link_type" :options="linkTypeOptions" />
            </div>
            <template v-if="setting.read_all_link_type !== 'custom'">
                <div class="sm:col-span-2">
                    <InputLabel value="เมนูปลายทาง" :required="true" />
                    <SearchableSelect v-model="menuId" :options="menuOptions" placeholder="เลือกเมนู" />
                    <p class="mt-1 text-xs text-gray-500">
                        เลือกได้เฉพาะเมนูที่ลิงก์ไปยังบทความ หน้าเพจ หรือลิงค์ภายนอก (เมนูหัวข้อเลือกไม่ได้) — เปิดลิงก์ตามที่ตั้งไว้ในเมนูนั้น
                    </p>
                    <p v-if="menus.length === 0" class="mt-1 text-xs text-amber-600">ยังไม่มีเมนูหน้าบ้านที่เปิดใช้งาน — สร้างที่ "จัดการเมนูหน้าบ้าน" ก่อน</p>
                    <InputError :message="errors.read_all_menu_id" />
                </div>
            </template>
            <template v-else>
                <div class="sm:col-span-2">
                    <InputLabel value="ลิงก์ URL ปลายทาง" :required="true" />
                    <TextInput v-model="setting.read_all_url" placeholder="https://example.com/news หรือ /news" maxlength="500" />
                    <p class="mt-1 text-xs text-gray-500">
                        ขึ้นต้นด้วย http://, https:// หรือ / — ลิงก์ภายในเว็บที่ไม่ได้ขึ้นต้นด้วยภาษา (เช่น /news) จะเติมภาษาของหน้าที่เปิดอยู่ให้อัตโนมัติ
                    </p>
                    <InputError :message="errors.read_all_url" />
                </div>
                <div>
                    <InputLabel value="เป้าหมายการเปิดลิงก์" />
                    <SearchableSelect v-model="setting.read_all_link_target" :options="LINK_TARGET_OPTIONS" />
                </div>
            </template>
        </div>
    </SettingSection>
</template>
