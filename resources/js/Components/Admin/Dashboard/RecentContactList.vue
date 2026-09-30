<script setup lang="ts">
import { CONTACTUS_PROCESS_STATUS_CLASS } from '@/utils/contactus';
import type { DashboardContact } from '@/utils/dashboard';
import { formatDateTime } from '@/utils/date';
import { Link } from '@inertiajs/vue3';

/** ข้อความติดต่อล่าสุด — รายการที่ยังไม่อ่านเป็นตัวหนา (เหมือนหน้ารายการติดต่อเรา) */
defineProps<{
    items: DashboardContact[];
}>();
</script>

<template>
    <p v-if="items.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีข้อความติดต่อ</p>
    <ul v-else class="-my-2 divide-y divide-gray-100">
        <li v-for="item in items" :key="item.id">
            <Link :href="item.href" class="group flex items-start justify-between gap-3 py-2.5">
                <div class="min-w-0">
                    <p class="truncate text-sm text-gray-800 group-hover:text-brand-600" :class="item.process_status === 'unread' ? 'font-semibold' : ''">
                        {{ item.subject || '(ไม่มีหัวข้อ)' }}
                    </p>
                    <p class="truncate text-xs text-gray-500">{{ item.fullname }} · {{ formatDateTime(item.created_at) }}</p>
                </div>
                <span
                    class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                    :class="CONTACTUS_PROCESS_STATUS_CLASS[item.process_status] ?? 'bg-gray-100 text-gray-600 ring-gray-500/20'"
                >
                    {{ item.process_status_label }}
                </span>
            </Link>
        </li>
    </ul>
</template>
