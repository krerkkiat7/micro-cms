<script setup lang="ts">
import { computed } from 'vue';
import { ArrowUpDown, Eye, EyeOff, Plus, Settings, Trash2 } from 'lucide-vue-next';

/**
 * แถบจัดการที่มุมซ้ายบนของแถว / คอลัมน์ / widget ในหน้า "โครงสร้าง" (ดู docs/PRD-page.md) — แต่ละชนิดมีสีต่างกัน
 * ให้แยกออกว่าแถบนี้เป็นของชั้นไหน: ปุ่มเรียงลำดับ (เปิด dialog เรียงลำดับของชั้นนั้นเสมอ — แถว/คอลัมน์/widget ทั้งหมดเรียงผ่าน
 * dialog ไม่มีการลากสลับตรงในหน้าจอแล้ว เพราะ widget แสดงตัวอย่างจริงที่สูง/ซับซ้อนขึ้นเรื่อย ๆ ลากยาก), เฟือง (ตั้งค่า), ลูกตา (แสดง/ซ่อน),
 * ถังขยะ (ลบ — ขอยืนยันก่อนที่หน้าโครงสร้าง), ปุ่มเพิ่มลูก (เฉพาะแถว/คอลัมน์) และชื่อหัวเรื่อง — `readonly` = ผู้ใช้ไม่มีสิทธิ์แก้ไข แสดงแค่ชื่อ
 */
const props = defineProps<{
    kind: 'row' | 'column' | 'widget';
    title: string;
    hidden: boolean;
    /** ข้อความปุ่มเพิ่มลูก เช่น "เพิ่มคอลัมน์" — ไม่ระบุ = ไม่แสดงปุ่ม */
    addLabel?: string;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    reorder: [];
    settings: [];
    toggle: [];
    remove: [];
    add: [];
}>();

const KIND_STYLES = {
    row: { bar: 'bg-brand-600', label: 'แถว' },
    column: { bar: 'bg-emerald-600', label: 'คอลัมน์' },
    widget: { bar: 'bg-amber-500', label: 'Widget' },
} as const;

const style = computed(() => KIND_STYLES[props.kind]);

const buttonClass = 'rounded p-1 transition-colors hover:bg-white/25 focus:outline-hidden focus-visible:bg-white/25';
</script>

<template>
    <div
        class="absolute left-0 top-0 z-10 flex max-w-full items-center gap-0.5 rounded-br-lg px-1 py-0.5 text-xs font-medium text-white shadow-xs"
        :class="[style.bar, hidden ? 'opacity-60' : '']"
    >
        <template v-if="!readonly">
            <button type="button" :class="buttonClass" :title="`เรียงลำดับ${style.label}`" @click="emit('reorder')">
                <ArrowUpDown class="size-3.5" />
            </button>

            <button type="button" :class="buttonClass" :title="`ตั้งค่า${style.label}`" @click="emit('settings')">
                <Settings class="size-3.5" />
            </button>

            <button type="button" :class="buttonClass" :title="hidden ? `แสดง${style.label}` : `ซ่อน${style.label}`" @click="emit('toggle')">
                <component :is="hidden ? EyeOff : Eye" class="size-3.5" />
            </button>

            <button type="button" :class="[buttonClass, 'hover:!bg-red-600/80']" :title="`ลบ${style.label}`" @click="emit('remove')">
                <Trash2 class="size-3.5" />
            </button>

            <button v-if="addLabel" type="button" :class="[buttonClass, 'flex items-center gap-0.5 pr-1.5']" @click="emit('add')">
                <Plus class="size-3.5" />
                {{ addLabel }}
            </button>
        </template>

        <span class="ml-1 mr-1.5 truncate">{{ title }}</span>
    </div>
</template>
