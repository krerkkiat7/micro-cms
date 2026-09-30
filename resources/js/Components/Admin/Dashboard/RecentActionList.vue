<script setup lang="ts">
import { ACTION_TYPE_CLASS, ACTION_TYPE_LABEL } from '@/utils/dashboard';
import type { DashboardAction } from '@/utils/dashboard';
import { formatDateTime } from '@/utils/date';

/** การกระทำล่าสุดของผู้ดูแลในหลังบ้าน (log_back_action) */
defineProps<{
    items: DashboardAction[];
}>();
</script>

<template>
    <p v-if="items.length === 0" class="py-6 text-center text-sm text-gray-400">ยังไม่มีประวัติการกระทำ</p>
    <ul v-else class="-my-2 divide-y divide-gray-100">
        <li v-for="item in items" :key="item.id" class="flex items-start gap-3 py-2.5">
            <span
                class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                :class="ACTION_TYPE_CLASS[item.action_type ?? ''] ?? 'bg-gray-100 text-gray-600'"
            >
                {{ ACTION_TYPE_LABEL[item.action_type ?? ''] ?? item.action_type ?? '-' }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-gray-800">{{ item.value_string || '-' }}</p>
                <p class="truncate text-xs text-gray-500">
                    {{ item.user_name ?? '-' }} · <span class="font-mono">{{ item.module_code }}</span> · {{ formatDateTime(item.created_at) }}
                </p>
            </div>
        </li>
    </ul>
</template>
