<script setup lang="ts">
import { ref, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { X } from 'lucide-vue-next';

/**
 * ช่องกรอกวันที่ + เวลา (ชั่วโมง/นาทีแยก dropdown) ผูกกับค่าเดียวแบบ 'YYYY-MM-DD HH:mm:00'
 * (รูปแบบ datetime ของ MySQL) — ใช้กับ publish_date (required) และ publish_down (optional, ลบค่าได้)
 */
const props = withDefaults(
    defineProps<{
        clearable?: boolean;
    }>(),
    {
        clearable: false,
    },
);

const model = defineModel<string | null>({ required: true });

const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const minutes = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));
const hourOptions = hours.map((h) => ({ value: h, label: h }));
const minuteOptions = minutes.map((m) => ({ value: m, label: m }));

function parse(value: string | null) {
    if (!value) {
        return { date: '', hour: '00', minute: '00' };
    }

    const [datePart, timePart] = value.split(' ');
    const [hour, minute] = (timePart ?? '00:00').split(':');

    return { date: datePart ?? '', hour: hour ?? '00', minute: minute ?? '00' };
}

const initial = parse(model.value);
const date = ref(initial.date);
const hour = ref(initial.hour);
const minute = ref(initial.minute);

watch([date, hour, minute], ([d, h, m]) => {
    model.value = d ? `${d} ${h}:${m}:00` : null;
});

function clear() {
    date.value = '';
    hour.value = '00';
    minute.value = '00';
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="w-44"><TextInput v-model="date" type="date" /></div>
        <div class="w-20"><SearchableSelect v-model="hour" :options="hourOptions" /></div>
        <span class="text-gray-400">:</span>
        <div class="w-20"><SearchableSelect v-model="minute" :options="minuteOptions" /></div>
        <SecondaryButton v-if="clearable && date" type="button" @click="clear">
            <X class="mr-1 size-4" /> ล้างค่า
        </SecondaryButton>
    </div>
</template>
