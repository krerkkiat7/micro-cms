<script setup lang="ts">
/**
 * ตัวเลขในช่วงจำกัด — แถบเลื่อน + ช่องกรอกเองคู่กัน (แก้ที่ไหนอีกที่ตามทันที) พร้อมบอกช่วงที่กรอกได้ในตัว
 * ค่าที่พิมพ์เกินช่วงถูกปรับให้อยู่ในช่วงตอนออกจากช่อง (ว่าง/ไม่ใช่ตัวเลข = ค่าต่ำสุด)
 */
const model = defineModel<number>({ required: true });

const props = withDefaults(
    defineProps<{
        min: number;
        max: number;
        step?: number;
        /** หน่วยต่อท้ายตัวเลข เช่น % หรือ px */
        unit?: string;
    }>(),
    { step: 1, unit: '' },
);

function clamp(value: number): number {
    return Math.min(props.max, Math.max(props.min, value));
}

function onRange(event: Event) {
    model.value = Number((event.target as HTMLInputElement).value);
}

function onNumber(event: Event, commit: boolean) {
    const input = event.target as HTMLInputElement;
    const parsed = Number.parseInt(input.value, 10);

    if (!commit) {
        if (!Number.isNaN(parsed) && parsed >= props.min && parsed <= props.max) model.value = parsed;

        return;
    }

    model.value = clamp(Number.isNaN(parsed) ? props.min : parsed);
    input.value = String(model.value);
}
</script>

<template>
    <div class="flex items-center gap-3">
        <input
            type="range"
            :min="min"
            :max="max"
            :step="step"
            :value="model"
            class="h-2 min-w-0 flex-1 cursor-pointer accent-brand-500"
            @input="onRange"
        />
        <span class="flex shrink-0 items-center gap-1">
            <input
                type="number"
                :min="min"
                :max="max"
                :step="step"
                :value="model"
                class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-500/10"
                @input="onNumber($event, false)"
                @change="onNumber($event, true)"
            />
            <span v-if="unit" class="text-sm text-gray-500">{{ unit }}</span>
        </span>
    </div>
    <p class="mt-1 text-xs text-gray-400">กรอกได้ {{ min }} - {{ max }}{{ unit }}</p>
</template>
