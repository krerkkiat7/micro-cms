<script setup lang="ts">
import { computed } from 'vue';

/**
 * ตัวเลื่อนหน้ากลาง — ใช้ทั้งหน้ารายการที่ยิง Inertia visit เต็มหน้า (User/Usergroup/BackLog ฯลฯ)
 * และหน้าจัดการไฟล์ที่ยิง ajax เอง (FileBrowser.vue) เพราะไม่ผูกกับ URL ตรง ๆ — รับแค่ current/last page
 * แล้ว emit เลขหน้าที่กดออกไป ให้ผู้เรียกตัดสินใจเองว่าจะ visit() หรือ fetch() ต่อ
 *
 * ปุ่ม: << (หน้าแรก) < (หน้าก่อน) [เลขหน้าสูงสุด 5 ตัว + "..." เมื่อห่างจากหน้าแรก/สุดท้าย] > (หน้าถัดไป) >> (หน้าสุดท้าย)
 */
const props = defineProps<{
    currentPage: number;
    lastPage: number;
}>();

const emit = defineEmits<{
    navigate: [page: number];
}>();

const MAX_VISIBLE = 5;

/** ช่วงเลขหน้าที่แสดง (เลื่อนตามหน้าปัจจุบัน ไม่เกิน MAX_VISIBLE ตัว) */
const range = computed(() => {
    const last = props.lastPage;

    if (last <= MAX_VISIBLE) {
        return { start: 1, end: last };
    }

    const half = Math.floor(MAX_VISIBLE / 2);
    let start = props.currentPage - half;
    let end = props.currentPage + half;

    if (start < 1) {
        end += 1 - start;
        start = 1;
    }
    if (end > last) {
        start -= end - last;
        end = last;
    }

    return { start: Math.max(1, start), end: Math.min(last, end) };
});

const pages = computed(() => {
    const result: number[] = [];
    for (let p = range.value.start; p <= range.value.end; p++) {
        result.push(p);
    }

    return result;
});

function go(page: number) {
    if (page < 1 || page > props.lastPage) {
        return;
    }

    emit('navigate', page);
}
</script>

<template>
    <nav v-if="lastPage > 1" class="flex flex-wrap items-center justify-center gap-1">
        <button
            type="button"
            class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
            :class="
                currentPage === 1
                    ? 'cursor-default border-gray-200 text-gray-300'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            "
            :disabled="currentPage === 1"
            title="หน้าแรกสุด"
            @click="go(1)"
        >
            &lt;&lt;
        </button>
        <button
            type="button"
            class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
            :class="
                currentPage === 1
                    ? 'cursor-default border-gray-200 text-gray-300'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            "
            :disabled="currentPage === 1"
            title="หน้าก่อนหน้า"
            @click="go(currentPage - 1)"
        >
            &lt;
        </button>

        <span v-if="range.start > 1" class="px-1.5 text-sm text-gray-400">...</span>

        <button
            v-for="p in pages"
            :key="p"
            type="button"
            class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
            :class="
                p === currentPage
                    ? 'border-brand-500 bg-brand-500 text-white'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            "
            @click="go(p)"
        >
            {{ p }}
        </button>

        <span v-if="range.end < lastPage" class="px-1.5 text-sm text-gray-400">...</span>

        <button
            type="button"
            class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
            :class="
                currentPage === lastPage
                    ? 'cursor-default border-gray-200 text-gray-300'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            "
            :disabled="currentPage === lastPage"
            title="หน้าถัดไป"
            @click="go(currentPage + 1)"
        >
            &gt;
        </button>
        <button
            type="button"
            class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
            :class="
                currentPage === lastPage
                    ? 'cursor-default border-gray-200 text-gray-300'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            "
            :disabled="currentPage === lastPage"
            title="หน้าสุดท้าย"
            @click="go(lastPage)"
        >
            &gt;&gt;
        </button>
    </nav>
</template>
