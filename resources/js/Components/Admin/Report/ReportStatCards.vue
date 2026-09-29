<script setup lang="ts">
import { formatDate } from '@/utils/date';
import { formatDecimal, formatNumber, useReportTerms } from '@/utils/report';
import type { ReportSummary } from '@/utils/report';
import { ArrowDownRight, ArrowUpRight, Minus } from 'lucide-vue-next';
import { computed } from 'vue';

/**
 * การ์ดสรุปตัวเลขของช่วงวันที่ที่เลือก + % เปลี่ยนแปลงเทียบกับช่วงก่อนหน้าที่ยาวเท่ากัน
 * showItems = แสดงจำนวนรายการที่มีข้อมูล (รายงานภาพรวม)
 */
const props = defineProps<{
    summary: ReportSummary;
    showItems?: boolean;
}>();

const terms = useReportTerms();

const previousRange = computed(() => `${formatDate(props.summary.previous.date_from)} – ${formatDate(props.summary.previous.date_to)}`);

function changeIcon(value: number | null) {
    if (value === null || value === 0) return Minus;
    return value > 0 ? ArrowUpRight : ArrowDownRight;
}

function changeClass(value: number | null): string {
    if (value === null || value === 0) return 'text-gray-500';
    return value > 0 ? 'text-emerald-700' : 'text-red-700';
}

function changeText(value: number | null): string {
    if (value === null) return 'ช่วงก่อนหน้าไม่มีข้อมูล';
    return `${value > 0 ? '+' : ''}${formatDecimal(value)}% จากช่วงก่อนหน้า`;
}

const cards = computed(() => {
    const s = props.summary;
    const list: { label: string; value: string; hint: string; change?: number | null }[] = [
        { label: terms.count, value: formatNumber(s.views), hint: '', change: s.change.views },
        { label: `${terms.unique}${terms.uniqueSuffix}`, value: formatNumber(s.sessions), hint: '', change: s.change.sessions },
        { label: 'IP Address ไม่ซ้ำ', value: formatNumber(s.ips), hint: `เฉลี่ย ${formatDecimal(s.views_per_session)} ครั้ง / ${terms.per}` },
        {
            label: 'เฉลี่ยต่อวัน',
            value: formatDecimal(s.avg_per_day),
            hint: `มีการ${terms.verb} ${formatNumber(s.active_days)} จาก ${formatNumber(s.days)} วัน`,
        },
        {
            label: `ช่วงที่มีการ${terms.verb}สูงสุด`,
            value: s.peak ? formatNumber(s.peak.views) : '-',
            hint: s.peak?.label ?? 'ไม่มีข้อมูลในช่วงนี้',
        },
    ];

    if (props.showItems) {
        list.splice(2, 0, { label: `${terms.item}ที่มีการ${terms.verb}`, value: formatNumber(s.items), hint: 'รายการ' });
    }

    return list;
});
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2" :class="showItems ? 'xl:grid-cols-6' : 'xl:grid-cols-5'">
        <div v-for="card in cards" :key="card.label" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-gray-500">{{ card.label }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ card.value }}</p>
            <p v-if="card.change !== undefined" class="mt-1 inline-flex items-center gap-1 text-xs" :class="changeClass(card.change)" :title="`ช่วงก่อนหน้า: ${previousRange}`">
                <component :is="changeIcon(card.change)" class="size-3.5" aria-hidden="true" />
                {{ changeText(card.change) }}
            </p>
            <p v-else class="mt-1 text-xs text-gray-500">{{ card.hint }}</p>
        </div>
    </div>
</template>
