<script setup lang="ts">
import DetailDialog from '@/Components/Admin/DetailDialog.vue';
import { formatDateTime } from '@/utils/date';
import { SIDE_LABEL } from '@/utils/errorViewer';
import type { ErrorDetail } from '@/utils/errorViewer';
import { Check, Copy } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/**
 * รายละเอียดของ error 1 รายการ (จาก admin.system.errorviewer.show) — trace แสดงเต็มใน <pre> เลื่อนได้
 * loading/error ของการโหลดส่งมาจากหน้าหลัก
 */
const props = defineProps<{
    show: boolean;
    detail: ErrorDetail | null;
    loading: boolean;
    error: string | null;
}>();

defineEmits<{ close: [] }>();

const copied = ref(false);

async function copyReference(): Promise<void> {
    if (!props.detail) return;

    try {
        await navigator.clipboard.writeText(props.detail.reference);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch {
        // เบราว์เซอร์ไม่อนุญาต clipboard — ผู้ใช้เลือกข้อความเองได้ (select-all)
    }
}

const rows = computed(() => {
    const d = props.detail;
    if (!d) return [];

    const user = d.user_id ? `${d.user_name ?? '-'}${d.user_deleted ? ' (ถูกลบแล้ว)' : ''} · id ${d.user_id}` : null;

    return [
        { label: 'วันเวลา', value: formatDateTime(d.datetime) },
        { label: 'ฝั่ง', value: SIDE_LABEL[d.side] },
        { label: 'ประเภท', value: d.class || '-', mono: true },
        { label: 'ตำแหน่งในโค้ด', value: d.file ?? '-', mono: true },
        { label: 'URL', value: d.url ?? '-', mono: true },
        { label: 'Method / Route', value: [d.method, d.route].filter(Boolean).join(' · ') || '-' },
        { label: 'ผู้ใช้หลังบ้าน', value: user ?? '-' },
        { label: 'ผู้ใช้หน้าบ้าน', value: d.front_user_id ? `id ${d.front_user_id}` : '-' },
        { label: 'IP Address', value: d.ip ?? '-' },
        { label: 'User Agent', value: d.user_agent ?? '-' },
        { label: 'มาจากหน้า (Referer)', value: d.referer ?? '-', mono: true },
        { label: 'ฟิลด์ที่ส่งมา', value: d.input_keys.length ? d.input_keys.join(', ') : '-', mono: true },
    ];
});
</script>

<template>
    <DetailDialog :show="show" title="รายละเอียด Error" wide @close="$emit('close')">
        <p v-if="loading" class="py-10 text-center text-sm text-gray-500">กำลังโหลด...</p>
        <p v-else-if="error" class="py-10 text-center text-sm text-red-600">{{ error }}</p>

        <div v-else-if="detail" class="space-y-5">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-mono text-lg font-semibold text-gray-900 select-all">{{ detail.reference }}</span>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50"
                    @click="copyReference"
                >
                    <component :is="copied ? Check : Copy" class="size-3.5" aria-hidden="true" />
                    {{ copied ? 'คัดลอกแล้ว' : 'คัดลอกรหัส' }}
                </button>
            </div>

            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm whitespace-pre-wrap break-words text-red-800">{{ detail.message || '(ไม่มีข้อความ)' }}</div>

            <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-[11rem_1fr]">
                <template v-for="row in rows" :key="row.label">
                    <dt class="font-medium text-gray-500">{{ row.label }}</dt>
                    <dd class="break-all text-gray-800" :class="row.mono ? 'font-mono text-xs leading-5' : ''">{{ row.value }}</dd>
                </template>
            </dl>

            <div v-if="detail.trace">
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Stack trace</h3>
                <pre class="max-h-80 overflow-auto rounded-lg bg-admin-900 p-4 font-mono text-xs leading-5 text-gray-200">{{ detail.trace }}</pre>
            </div>
        </div>
    </DetailDialog>
</template>
