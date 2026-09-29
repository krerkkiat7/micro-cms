<script setup lang="ts">
import BreakdownList from '@/Components/Admin/Report/BreakdownList.vue';
import { deviceLabel, sourceLabel, unknownLabel } from '@/utils/report';
import type { BreakdownItem, Breakdowns, Referrers } from '@/utils/report';
import { computed } from 'vue';

/**
 * สัดส่วนผู้เข้าชม: ภาษา / อุปกรณ์ / เบราว์เซอร์ / ระบบปฏิบัติการ / แหล่งที่มา (+ เว็บไซต์ที่อ้างอิงมา เมื่อ showHosts)
 */
const props = defineProps<{
    breakdowns: Breakdowns;
    referrers: Referrers;
    showHosts?: boolean;
}>();

const toItems = (items: BreakdownItem[], label: (key: string | null) => string) =>
    items.map((i) => ({ label: label(i.key), views: i.views }));

const lang = computed(() => toItems(props.breakdowns.lang, (k) => (k ? k.toUpperCase() : 'ไม่ทราบ')));
const device = computed(() => toItems(props.breakdowns.device_type, deviceLabel));
const browser = computed(() => toItems(props.breakdowns.browser, unknownLabel));
const platform = computed(() => toItems(props.breakdowns.platform, unknownLabel));
const sources = computed(() =>
    props.referrers.sources.filter((s) => s.views > 0).map((s) => ({ label: sourceLabel(s.key), views: s.views })),
);
const hosts = computed(() => props.referrers.hosts.map((h) => ({ label: h.key, views: h.views })));
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <BreakdownList title="แหล่งที่มา" :items="sources" />
        <BreakdownList title="อุปกรณ์" :items="device" />
        <BreakdownList title="ภาษาที่เข้าชม" :items="lang" />
        <BreakdownList title="เบราว์เซอร์" :items="browser" />
        <BreakdownList title="ระบบปฏิบัติการ" :items="platform" />
        <BreakdownList v-if="showHosts" title="เว็บไซต์ที่อ้างอิงมา (ภายนอก)" :items="hosts" empty-text="ไม่มีการเข้าชมจากเว็บไซต์ภายนอก" />
    </div>
    <p class="mt-2 text-xs text-gray-500">
        อุปกรณ์ / เบราว์เซอร์ / ระบบปฏิบัติการ / แหล่งที่มา เริ่มบันทึกตั้งแต่เปิดใช้รายงาน — การเข้าชมก่อนหน้านั้นแสดงเป็น "ไม่ทราบ" / "เข้าตรง"
    </p>
</template>
