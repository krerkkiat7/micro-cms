<script setup lang="ts">
/**
 * เปลือกของตัวเลือกแบบการ์ดมีภาพประกอบ (pattern เดียวกับ ArticlePart/ImagesDisplayTypePicker.vue) — ใช้กับการเลือก
 * รูปแบบ/สไตล์ต่าง ๆ ของ template แทน dropdown; ภาพ SVG ของแต่ละตัวเลือกส่งมาทาง slot (ได้รับ `value` ของตัวเลือก)
 */
const model = defineModel<string>({ required: true });

withDefaults(
    defineProps<{
        options: { value: string; label: string; description?: string }[];
        name: string;
        /** จำนวนคอลัมน์บนจอกว้าง */
        columns?: 2 | 3 | 4;
        disabled?: boolean;
    }>(),
    { columns: 3, disabled: false },
);

const GRID = { 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-2 lg:grid-cols-3', 4: 'sm:grid-cols-2 lg:grid-cols-4' } as const;
</script>

<template>
    <div class="grid gap-3" :class="GRID[columns]">
        <label
            v-for="opt in options"
            :key="opt.value"
            class="flex flex-col rounded-xl border p-3 transition-colors"
            :class="[
                model === opt.value ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-gray-200 bg-white hover:border-gray-300',
                disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
            ]"
        >
            <input v-model="model" type="radio" :name="name" :value="opt.value" class="sr-only" :disabled="disabled" />
            <slot :value="opt.value" />
            <span class="mt-2 text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span v-if="opt.description" class="mt-0.5 text-xs text-gray-500">{{ opt.description }}</span>
        </label>
    </div>
</template>
