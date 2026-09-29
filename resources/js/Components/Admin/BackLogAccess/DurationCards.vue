<script setup lang="ts">
import { formatDuration } from '@/utils/report';
import type { DurationSummary } from '@/utils/backLogAccessReport';

/**
 * เวลาที่ใช้งาน (จาก last_visited - created_at ของแต่ละครั้งที่เปิดหน้าจอ, ตัดที่ cap วินาทีต่อครั้ง)
 */
defineProps<{
    duration: DurationSummary;
    cap: number;
}>();
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-gray-500">เวลาเฉลี่ยต่อการเปิดหน้าจอ</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ formatDuration(duration.avg_seconds) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-gray-500">เวลาเฉลี่ยต่อการเข้าระบบ (session)</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ formatDuration(duration.avg_session_seconds) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-gray-500">เวลาใช้งานรวมโดยประมาณ</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ formatDuration(duration.total_seconds) }}</p>
        </div>
        <p class="text-xs text-gray-500 sm:col-span-3">
            เวลาคำนวณจากช่วงที่หน้าจอเปิดอยู่ (บันทึกทุก 45 วินาทีและตอนออกจากหน้า) — นับไม่เกิน {{ formatDuration(cap) }} ต่อการเปิดหน้าจอ 1 ครั้ง
            กันกรณีเปิดแท็บทิ้งไว้
        </p>
    </div>
</template>
