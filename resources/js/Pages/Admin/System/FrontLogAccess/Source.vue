<script setup lang="ts">
import StatsShell from '@/Components/Admin/LogStats/StatsShell.vue';
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import ReportStatCards from '@/Components/Admin/Report/ReportStatCards.vue';
import { sourceLabel } from '@/utils/report';
import type { BreakdownItem, ReportFilters, ReportSummary, Referrers } from '@/utils/report';
import { computed } from 'vue';

const props = defineProps<{
    filters: ReportFilters;
    summary: ReportSummary;
    referrers: Referrers;
    languages: BreakdownItem[];
    acceptLanguages: BreakdownItem[];
}>();

const sources = computed(() => props.referrers.sources.filter((s) => s.views > 0).map((s) => ({ label: sourceLabel(s.key), views: s.views })));
const hosts = computed(() => props.referrers.hosts.map((h) => ({ label: h.key, views: h.views })));
const paths = computed(() => props.referrers.paths.map((p) => ({ label: p.key, views: p.views })));
const languages = computed(() => props.languages.map((l) => ({ label: l.key ? l.key.toUpperCase() : 'ไม่ระบุ (หน้าแรก / Intropage)', views: l.views })));
// Accept-Language เก็บดิบ (เช่น "th-TH,th;q=0.9,en;q=0.8") — แสดงเฉพาะภาษาแรกที่เบราว์เซอร์ต้องการ
const acceptLanguages = computed(() => {
    const merged = new Map<string, number>();

    for (const item of props.acceptLanguages) {
        const first = item.key ? item.key.split(',')[0].split(';')[0].trim() : '';
        const label = first !== '' ? first : 'ไม่ทราบ';
        merged.set(label, (merged.get(label) ?? 0) + item.views);
    }

    return [...merged.entries()].map(([label, views]) => ({ label, views })).sort((a, b) => b.views - a.views);
});
</script>

<template>
    <StatsShell log="frontAccess" tab="source" :filters="filters" :show-period="false" :show-chart-type="false">
        <ReportStatCards :summary="summary" />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <BreakdownList title="แหล่งที่มา" :items="sources" />
            <BreakdownList title="เว็บไซต์ที่อ้างอิงมา (ภายนอก)" :items="hosts" empty-text="ไม่มีการเข้าชมจากเว็บไซต์ภายนอก" />
            <BreakdownList title="หน้าในเว็บไซต์ที่อ้างอิงมา" :items="paths" empty-text="ไม่มีการเข้ามาจากหน้าอื่นในเว็บไซต์" />
            <BreakdownList title="ภาษาของเว็บไซต์ที่เปิด" :items="languages" />
            <BreakdownList title="ภาษาที่เบราว์เซอร์ของผู้เข้าชมตั้งไว้" :items="acceptLanguages" />
        </div>
        <p class="text-xs text-gray-500">
            ภาษาของเบราว์เซอร์ช่วยตัดสินใจว่าควรเปิดภาษาใดเพิ่ม — เทียบกับภาษาของเว็บไซต์ที่ผู้เข้าชมเลือกเปิดจริง
        </p>
    </StatsShell>
</template>
