<script setup lang="ts">
import type { AttentionTone, DashboardAttention } from '@/utils/dashboard';
import { formatNumber } from '@/utils/report';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, ChevronRight, Clock, Mail, ShieldAlert } from 'lucide-vue-next';

/** รายการที่ควรดำเนินการ — backend ส่งมาเฉพาะรายการที่มีจำนวน > 0 */
defineProps<{
    items: DashboardAttention[];
}>();

const ICONS: Record<string, unknown> = {
    contactus_unread: Mail,
    contactus_considering: Mail,
    article_expiring: Clock,
    popup_expiring: Clock,
    login_failed: ShieldAlert,
};

const TONE_CLASS: Record<AttentionTone, string> = {
    brand: 'bg-brand-50 text-brand-600',
    amber: 'bg-amber-50 text-amber-600',
    red: 'bg-red-50 text-red-600',
};
</script>

<template>
    <section class="rounded-2xl border border-amber-200 bg-amber-50/40 p-4 shadow-xs">
        <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-800">
            <AlertTriangle class="size-4 text-amber-500" aria-hidden="true" />
            รายการที่ควรดำเนินการ
        </h2>
        <ul class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="item in items" :key="item.key">
                <Link
                    :href="item.href"
                    class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5 transition hover:border-brand-300"
                >
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg" :class="TONE_CLASS[item.tone]">
                        <component :is="ICONS[item.key] ?? AlertTriangle" class="size-4" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 flex-1 text-sm text-gray-700">{{ item.label }}</span>
                    <span class="text-lg font-semibold text-gray-900 tabular-nums">{{ formatNumber(item.count) }}</span>
                    <ChevronRight class="size-4 shrink-0 text-gray-400 group-hover:text-brand-500" aria-hidden="true" />
                </Link>
            </li>
        </ul>
    </section>
</template>
