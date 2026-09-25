<script setup lang="ts">
import { computed } from 'vue';
import BlockTexts from '@/Components/Front/PageLayout/BlockTexts.vue';
import PageWidget from '@/Components/Front/PageLayout/PageWidget.vue';
import { backgroundCss } from '@/utils/front';
import { gapCss, paddingCss } from '@/utils/frontPage';
import type { FrontRowData } from '@/utils/frontPage';

/**
 * แถวของหน้าเพจ — พื้นหลังเต็มความกว้างเสมอ, เนื้อหาอยู่ใน container เมื่อ use_container ไม่งั้นเต็มจอ
 * คอลัมน์เรียงใน grid 12 ช่อง (จอเล็กกว่า md ทุกคอลัมน์เต็มแถว — CSS .front-col ใน app.css), หัวเรื่องแถว h2 / คอลัมน์ h3
 * ระยะขอบด้านในของแถว/คอลัมน์ และระยะห่างระหว่างคอลัมน์ (แนวนอน/แนวตั้ง) มาจากการตั้งค่าในหน้าโครงสร้าง (ปิด = ไม่เว้นระยะ)
 */
const props = defineProps<{ row: FrontRowData }>();

const headingId = computed(() => (props.row.title ? `row-${props.row.id}-title` : undefined));
</script>

<template>
    <section :style="{ ...backgroundCss(row.background), ...paddingCss(row.padding) }" :aria-labelledby="headingId">
        <div :class="row.use_container ? 'mx-auto max-w-7xl px-4' : 'px-0'">
            <BlockTexts :block="row" tag="h2" :heading-id="headingId" class="mb-5" :class="row.use_container ? '' : 'px-4'" />

            <div class="grid grid-cols-12" :style="gapCss(row)">
                <div
                    v-for="column in row.columns"
                    :key="column.id"
                    class="front-col col-span-12 min-w-0 space-y-6"
                    :style="{ '--span': column.column_size, ...backgroundCss(column.background), ...paddingCss(column.padding) }"
                >
                    <BlockTexts :block="column" tag="h3" />
                    <PageWidget v-for="widget in column.widgets" :key="widget.id" :widget="widget" />
                </div>
            </div>
        </div>
    </section>
</template>
