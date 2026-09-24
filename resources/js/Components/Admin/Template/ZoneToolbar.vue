<script setup lang="ts">
import { Eye, EyeOff, Settings } from 'lucide-vue-next';

/**
 * แถบจัดการที่มุมซ้ายบนของแต่ละโซนในหน้าโครงสร้าง template — มีแค่เฟือง (ตั้งค่า) และลูกตา (แสดง/ซ่อนโซน — บันทึกลง DB)
 * `toggleable = false` = โซนที่ซ่อนไม่ได้ (main body) ไม่แสดงลูกตา; `readonly` = ไม่มีสิทธิ์แก้ไข แสดงแค่ชื่อโซน
 */
defineProps<{
    title: string;
    hidden?: boolean;
    toggleable?: boolean;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    settings: [];
    toggle: [];
}>();

const buttonClass = 'rounded p-1 transition-colors hover:bg-white/25 focus:outline-hidden focus-visible:bg-white/25';
</script>

<template>
    <div
        class="absolute left-0 top-0 z-20 flex items-center gap-0.5 rounded-br-lg bg-brand-600 px-1 py-0.5 text-xs font-medium text-white shadow-xs"
        :class="hidden ? 'opacity-70' : ''"
    >
        <template v-if="!readonly">
            <button type="button" :class="buttonClass" :title="`ตั้งค่า ${title}`" @click="emit('settings')">
                <Settings class="size-3.5" />
            </button>
            <button v-if="toggleable" type="button" :class="buttonClass" :title="hidden ? `แสดง ${title}` : `ซ่อน ${title}`" @click="emit('toggle')">
                <component :is="hidden ? EyeOff : Eye" class="size-3.5" />
            </button>
        </template>
        <span class="ml-1 mr-1.5">{{ title }}<template v-if="hidden"> (ซ่อน)</template></span>
    </div>
</template>
