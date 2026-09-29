<script setup lang="ts">
import { formatNumber } from '@/utils/report';
import type { ChartType } from '@/utils/report';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';
import type { ChartData, ChartOptions } from 'chart.js';
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';

// register เฉพาะส่วนที่ใช้ (tree-shake ได้)
ChartJS.register(BarElement, CategoryScale, Filler, Legend, LineElement, LinearScale, PointElement, Tooltip);

/**
 * กราฟยอดตามช่วงเวลา/รายการ — แท่งหรือเส้น, แกนเดียว (ทุก series เป็นจำนวนนับหน่วยเดียวกัน)
 * horizontal = กราฟแท่งแนวนอน (ใช้กับอันดับ/หมวดหมู่ที่ชื่อยาว)
 */
const props = withDefaults(
    defineProps<{
        labels: string[];
        datasets: { label: string; data: number[]; color: string }[];
        type?: ChartType;
        horizontal?: boolean;
        height?: number;
    }>(),
    { type: 'bar', horizontal: false, height: 320 },
);

const GRID = '#f3f4f6';
const AXIS_TEXT = '#6b7280';

const data = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map((ds) =>
        props.type === 'line'
            ? {
                  label: ds.label,
                  data: ds.data,
                  borderColor: ds.color,
                  backgroundColor: ds.color,
                  borderWidth: 2,
                  pointRadius: props.labels.length > 60 ? 0 : 3,
                  pointHoverRadius: 5,
                  pointBackgroundColor: ds.color,
                  pointBorderColor: '#ffffff',
                  pointBorderWidth: 2,
                  tension: 0.25,
              }
            : {
                  label: ds.label,
                  data: ds.data,
                  backgroundColor: ds.color,
                  hoverBackgroundColor: ds.color,
                  borderRadius: 4,
                  borderSkipped: 'start' as const,
                  borderWidth: 0,
                  maxBarThickness: 36,
                  categoryPercentage: 0.8,
                  barPercentage: 0.9,
              },
    ),
}));

const options = computed<ChartOptions<'bar'> & ChartOptions<'line'>>(() => {
    const valueAxis = {
        beginAtZero: true,
        grid: { color: GRID },
        border: { display: false },
        ticks: { color: AXIS_TEXT, precision: 0, callback: (v: string | number) => formatNumber(Number(v)) },
    };
    const categoryAxis = {
        grid: { display: false },
        border: { color: '#e5e7eb' },
        ticks: { color: AXIS_TEXT, autoSkip: true, maxRotation: 0, autoSkipPadding: 12 },
    };

    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: props.horizontal ? 'y' : 'x',
        interaction: { mode: 'index', intersect: false, axis: props.horizontal ? 'y' : 'x' },
        plugins: {
            legend: {
                display: props.datasets.length > 1,
                position: 'top',
                align: 'end',
                labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, color: '#374151' },
            },
            tooltip: {
                backgroundColor: '#111827',
                padding: 10,
                boxPadding: 4,
                usePointStyle: true,
                callbacks: {
                    label: (ctx) => ` ${ctx.dataset.label}: ${formatNumber(Number(ctx.parsed[props.horizontal ? 'x' : 'y']))}`,
                },
            },
        },
        scales: props.horizontal ? { x: valueAxis, y: categoryAxis } : { x: categoryAxis, y: valueAxis },
    } as ChartOptions<'bar'> & ChartOptions<'line'>;
});
</script>

<template>
    <div :style="{ height: `${height}px` }" class="relative">
        <Line v-if="type === 'line'" :data="data as ChartData<'line'>" :options="options" />
        <Bar v-else :data="data as ChartData<'bar'>" :options="options" />
    </div>
</template>
