<script setup lang="ts">
import { zoneBackgroundStyle } from '@/utils/template';
import type { BodyZone } from '@/utils/template';

/** ตัวอย่างพื้นที่เนื้อหา — กล่องสมมติแทนเนื้อหาของแต่ละหน้า บนพื้นหลังที่ตั้งไว้ */
defineProps<{ zone: BodyZone }>();
</script>

<template>
    <div class="h-full px-6 py-8" :style="zoneBackgroundStyle(zone)">
        <div class="mx-auto w-[88%] space-y-4">
            <div class="h-32 rounded-xl bg-gray-300/70" />
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="h-24 rounded-xl bg-gray-300/60" />
                <div class="h-24 rounded-xl bg-gray-300/60" />
                <div class="h-24 rounded-xl bg-gray-300/60" />
            </div>
            <div class="space-y-2">
                <div class="h-3 w-3/4 rounded bg-gray-300/60" />
                <div class="h-3 w-2/3 rounded bg-gray-300/60" />
                <div class="h-3 w-1/2 rounded bg-gray-300/60" />
            </div>
            <p class="text-center text-xs text-gray-500">พื้นที่เนื้อหาของแต่ละหน้า</p>
        </div>
    </div>
</template>
