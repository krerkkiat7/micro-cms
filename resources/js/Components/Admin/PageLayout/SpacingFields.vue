<script setup lang="ts">
import YesNoCheckbox from '@/Components/Admin/Template/YesNoCheckbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { GAP_MAX, PADDING_MAX } from '@/utils/pageLayout';
import type { GapFields, PaddingFields, PaddingSide } from '@/utils/pageLayout';

/**
 * ส่วน "ระยะห่าง" ใน dialog ตั้งค่าแถว/คอลัมน์/widget — แก้ค่าบน object ที่ส่งเข้ามา (draft ของ dialog) ตรง ๆ แบบเดียวกับ BackgroundFields
 * - ระยะขอบด้านใน: ติ๊กเปิดแล้วค่อยแสดงช่องตัวเลข 4 ด้าน วางเป็นแผนภาพกล่อง (บน/ซ้าย/ขวา/ล่าง รอบ "เนื้อหา") ให้เข้าใจได้โดยไม่ต้องรู้คำว่า padding
 * - `gaps` (เฉพาะแถว): ระยะห่างระหว่างคอลัมน์ ซ้าย-ขวา / บน-ล่าง
 * ค่าที่พิมพ์ถูกแปลงเป็นจำนวนเต็มและจำกัดช่วงทันที (ว่าง/ไม่ใช่ตัวเลข = 0)
 */
const props = defineProps<{
    fields: PaddingFields & Partial<GapFields>;
    /** ชื่อของสิ่งที่ตั้งค่า ใช้ในคำอธิบาย เช่น "แถว" */
    subject: string;
    /** แสดงส่วนระยะห่างระหว่างคอลัมน์ (เฉพาะแถว) */
    gaps?: boolean;
}>();

const SIDES: { side: PaddingSide; label: string; area: string }[] = [
    { side: 'top', label: 'ด้านบน', area: 'col-start-2 row-start-1' },
    { side: 'left', label: 'ด้านซ้าย', area: 'col-start-1 row-start-2' },
    { side: 'right', label: 'ด้านขวา', area: 'col-start-3 row-start-2' },
    { side: 'bottom', label: 'ด้านล่าง', area: 'col-start-2 row-start-3' },
];

/** แปลงค่าที่พิมพ์เป็นจำนวนเต็มในช่วง 0 - max แล้วเขียนกลับลงช่อง (กรณีค่าหลังแปลงเท่าเดิม Vue จะไม่ render ช่องใหม่ให้) */
function toNumber(event: Event, max: number): number {
    const input = event.target as HTMLInputElement;
    const number = parseInt(input.value, 10);
    const value = Number.isNaN(number) ? 0 : Math.min(max, Math.max(0, number));
    input.value = String(value);

    return value;
}

function setPadding(side: PaddingSide, event: Event) {
    props.fields[`padding_${side}`] = toNumber(event, PADDING_MAX);
}

function setGap(key: keyof GapFields, event: Event) {
    props.fields[key] = toNumber(event, GAP_MAX);
}
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-3">
            <YesNoCheckbox
                v-model="fields.use_padding"
                label="เว้นระยะขอบด้านใน"
                :description="`เว้นที่ว่างระหว่างขอบของ${subject}กับเนื้อหาข้างใน เพื่อไม่ให้เนื้อหาชิดขอบเกินไป (ไม่ติ๊ก = ไม่เว้น)`"
            />

            <div v-if="fields.use_padding === 'Y'" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="mb-3 text-xs text-gray-500">กรอกระยะของแต่ละด้าน หน่วยเป็นพิกเซล (px) ตั้งแต่ 0 - {{ PADDING_MAX }} — 16 px ประมาณความสูงของตัวอักษร 1 บรรทัด</p>
                <div class="mx-auto grid max-w-sm grid-cols-3 grid-rows-3 items-center gap-2">
                    <label v-for="item in SIDES" :key="item.side" class="flex flex-col items-center gap-1" :class="item.area">
                        <span class="text-xs font-medium text-gray-600">{{ item.label }}</span>
                        <span class="flex items-center gap-1">
                            <input
                                type="number"
                                min="0"
                                :max="PADDING_MAX"
                                class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-500/10"
                                :value="fields[`padding_${item.side}`]"
                                @change="setPadding(item.side, $event)"
                            />
                            <span class="text-xs text-gray-400">px</span>
                        </span>
                    </label>
                    <div
                        class="col-start-2 row-start-2 flex h-14 items-center justify-center rounded-lg border-2 border-dashed border-brand-300 bg-white text-xs font-medium text-brand-600"
                    >
                        เนื้อหา
                    </div>
                </div>
            </div>
        </div>

        <div v-if="gaps" class="space-y-2">
            <InputLabel value="ระยะห่างระหว่างคอลัมน์" />
            <p class="text-xs text-gray-500">ช่องว่างที่คั่นระหว่างคอลัมน์ภายในแถวนี้ หน่วยเป็นพิกเซล (px) ตั้งแต่ 0 - {{ GAP_MAX }} (ค่าแนะนำ 24 px)</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-medium text-gray-600">ซ้าย-ขวา (ระหว่างคอลัมน์ที่อยู่ข้างกัน)</span>
                    <span class="mt-1 flex items-center gap-1">
                        <input
                            type="number"
                            min="0"
                            :max="GAP_MAX"
                            class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-500/10"
                            :value="fields.gap_x"
                            @change="setGap('gap_x', $event)"
                        />
                        <span class="text-xs text-gray-400">px</span>
                    </span>
                </label>
                <label class="block">
                    <span class="text-xs font-medium text-gray-600">บน-ล่าง (เมื่อคอลัมน์ขึ้นบรรทัดใหม่ หรือแสดงบนมือถือ)</span>
                    <span class="mt-1 flex items-center gap-1">
                        <input
                            type="number"
                            min="0"
                            :max="GAP_MAX"
                            class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-500/10"
                            :value="fields.gap_y"
                            @change="setGap('gap_y', $event)"
                        />
                        <span class="text-xs text-gray-400">px</span>
                    </span>
                </label>
            </div>
        </div>
    </div>
</template>
