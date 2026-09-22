<script setup lang="ts">
import { computed } from 'vue';
import FlagField from './FlagField.vue';
import TextStyleFields from '../TextStyleFields.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { SLIDESET_LINES_OPTIONS, settingTextStyle } from '@/utils/pageWidget';
import type { CardListSetting } from '@/utils/pageWidget';

/**
 * ตั้งค่าของข้อความ 1 ส่วนบนการ์ด/แถว (Slideset หรือ Grid) — ขนาด, ฟอนต์, สี, ตัวหนา (ทุกส่วน) และเพิ่ม จัดตำแหน่ง / กดลิงก์ได้ / จำนวนบรรทัดที่แสดง
 * เมื่อเป็นข้อความที่ยาวได้ (`rich`: หัวเรื่อง, ข้อความเกริ่นนำ) ส่วนข้อมูลเสริม (วันที่เผยแพร่, จำนวนเข้าชม) มีแค่ขนาด/ฟอนต์/สี/ตัวหนา
 * ชื่อฟิลด์ใน setting = `<part>_font_size`, `<part>_bold`, `<part>_lines` ฯลฯ (ตรงกับคอลัมน์ฝั่ง backend — ตรงกันทั้งสองประเภท)
 */
const props = defineProps<{
    setting: CardListSetting;
    part: 'title' | 'intro_text' | 'date' | 'views';
    fonts: string[];
    /** true = มีจัดตำแหน่ง/กดลิงก์ได้/จำนวนบรรทัด (หัวเรื่อง, ข้อความเกริ่นนำ) */
    rich?: boolean;
}>();

// เข้าถึงฟิลด์ตามชื่อส่วน (`title_bold` ฯลฯ) — พิมพ์ชนิดหลวม ๆ เพราะชื่อคีย์ประกอบจาก part
const record = computed(() => props.setting as unknown as Record<string, unknown>);

const style = computed(() => settingTextStyle(props.setting, props.part));

const bold = computed({
    get: () => record.value[`${props.part}_bold`] as 'Y' | 'N',
    set: (value: 'Y' | 'N') => {
        record.value[`${props.part}_bold`] = value;
    },
});

const clickable = computed({
    get: () => record.value[`${props.part}_clickable`] as 'Y' | 'N',
    set: (value: 'Y' | 'N') => {
        record.value[`${props.part}_clickable`] = value;
    },
});

const lines = computed({
    get: () => String(record.value[`${props.part}_lines`]),
    set: (value: string) => {
        record.value[`${props.part}_lines`] = Number(value);
    },
});
</script>

<template>
    <div class="space-y-4">
        <TextStyleFields :text-style="style" :fonts="fonts" :show-align="rich" />

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="flex items-end pb-2">
                <FlagField v-model="bold" label="ตัวหนา" />
            </div>
            <template v-if="rich">
                <div>
                    <InputLabel value="จำนวนบรรทัดที่แสดง" />
                    <SearchableSelect v-model="lines" :options="SLIDESET_LINES_OPTIONS" />
                    <p class="mt-1 text-xs text-gray-500">เกินกว่านี้ตัดด้วย ...</p>
                </div>
                <div class="flex items-end pb-2">
                    <FlagField v-model="clickable" label="กดลิงก์ได้" />
                </div>
            </template>
        </div>
    </div>
</template>
