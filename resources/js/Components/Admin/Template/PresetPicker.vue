<script setup lang="ts">
import VisualPicker from '@/Components/Admin/Template/VisualPicker.vue';
import { TEMPLATE_PRESETS } from '@/utils/template';

/**
 * เลือกแม่แบบตั้งต้นตอนเพิ่ม template — ภาพย่อของหน้าเว็บ (header / เนื้อหา / footer) ตามลักษณะของแต่ละแม่แบบ
 * ค่าจริงของแต่ละแม่แบบอยู่ที่ App\Support\Template\TemplatePreset
 */
const model = defineModel<string>({ required: true });
</script>

<template>
    <VisualPicker v-model="model" :options="TEMPLATE_PRESETS" name="template_preset">
        <template #default="{ value }">
            <svg viewBox="0 0 120 80" class="h-24 w-full rounded-md border border-gray-100">
                <!-- classic: แถบบนเข้ม + แถบหลักขาว โลโก้ซ้าย เมนูขวา / footer 3 ส่วน -->
                <template v-if="value === 'classic'">
                    <rect width="120" height="80" class="fill-gray-50" />
                    <rect width="120" height="6" class="fill-brand-900" />
                    <rect y="6" width="120" height="14" class="fill-white" />
                    <rect x="6" y="9" width="16" height="8" rx="1.5" class="fill-brand-400" />
                    <rect x="70" y="12" width="9" height="2" class="fill-gray-400" />
                    <rect x="82" y="12" width="9" height="2" class="fill-gray-400" />
                    <rect x="94" y="12" width="9" height="2" class="fill-brand-500" />
                    <rect x="94" y="15" width="9" height="1" class="fill-brand-500" />
                    <rect x="106" y="12" width="9" height="2" class="fill-gray-400" />
                    <rect x="8" y="26" width="104" height="30" rx="2" class="fill-gray-200" />
                    <rect y="60" width="120" height="16" class="fill-gray-700" />
                    <rect x="8" y="64" width="30" height="2" class="fill-gray-400" />
                    <rect x="46" y="64" width="30" height="2" class="fill-gray-400" />
                    <rect x="84" y="64" width="28" height="2" class="fill-gray-400" />
                    <rect y="76" width="120" height="4" class="fill-gray-900" />
                </template>

                <!-- corporate: แถบหลักขาว + แถวเมนูสีแบรนด์ / footer บล็อกพื้นอ่อน -->
                <template v-else-if="value === 'corporate'">
                    <rect width="120" height="80" class="fill-white" />
                    <rect x="6" y="3" width="18" height="9" rx="1.5" class="fill-brand-400" />
                    <rect x="96" y="6" width="18" height="3" rx="1" class="fill-gray-300" />
                    <rect y="14" width="120" height="8" class="fill-brand-600" />
                    <rect x="6" y="15.5" width="14" height="5" rx="2.5" class="fill-white/40" />
                    <rect x="24" y="17" width="10" height="2" class="fill-white" />
                    <rect x="38" y="17" width="10" height="2" class="fill-white" />
                    <rect x="52" y="17" width="10" height="2" class="fill-white" />
                    <rect x="8" y="28" width="104" height="28" rx="2" class="fill-gray-200" />
                    <rect y="60" width="120" height="16" class="fill-gray-100" />
                    <rect x="8" y="64" width="44" height="8" rx="1" class="fill-gray-300" />
                    <rect x="68" y="64" width="44" height="8" rx="1" class="fill-gray-300" />
                    <rect y="76" width="120" height="4" class="fill-gray-200" />
                </template>

                <!-- centered: โลโก้กลาง + เมนูกลางคั่นเส้น / footer กึ่งกลาง -->
                <template v-else-if="value === 'centered'">
                    <rect width="120" height="80" class="fill-gray-50" />
                    <rect y="0" width="120" height="22" class="fill-white" />
                    <rect x="50" y="3" width="20" height="8" rx="1.5" class="fill-brand-400" />
                    <rect x="30" y="15" width="10" height="2" class="fill-gray-400" />
                    <rect x="43" y="14" width="0.8" height="4" class="fill-gray-300" />
                    <rect x="47" y="15" width="10" height="2" class="fill-brand-500" />
                    <rect x="60" y="14" width="0.8" height="4" class="fill-gray-300" />
                    <rect x="64" y="15" width="10" height="2" class="fill-gray-400" />
                    <rect x="77" y="14" width="0.8" height="4" class="fill-gray-300" />
                    <rect x="81" y="15" width="10" height="2" class="fill-gray-400" />
                    <rect x="8" y="27" width="104" height="29" rx="2" class="fill-gray-200" />
                    <rect y="60" width="120" height="20" class="fill-slate-900" />
                    <rect x="40" y="64" width="40" height="2.5" class="fill-gray-400" />
                    <rect x="30" y="69" width="60" height="2" class="fill-gray-500" />
                    <rect x="45" y="74" width="30" height="2" class="fill-gray-600" />
                </template>

                <!-- minimal: แถบเดียว ติดบน / footer ขาวกึ่งกลาง -->
                <template v-else-if="value === 'minimal'">
                    <rect width="120" height="80" class="fill-white" />
                    <rect width="120" height="12" class="fill-white" />
                    <rect y="12" width="120" height="0.8" class="fill-gray-200" />
                    <rect x="6" y="3" width="10" height="6" rx="1.5" class="fill-brand-400" />
                    <rect x="66" y="5" width="8" height="2" class="fill-gray-500" />
                    <rect x="78" y="5" width="8" height="2" class="fill-gray-500" />
                    <rect x="90" y="5" width="8" height="2" class="fill-gray-500" />
                    <rect x="104" y="3.5" width="10" height="5" rx="1" class="fill-gray-200" />
                    <rect x="8" y="18" width="104" height="38" rx="2" class="fill-gray-100" />
                    <rect y="62" width="120" height="0.8" class="fill-gray-200" />
                    <rect x="40" y="67" width="40" height="2.5" class="fill-gray-400" />
                    <rect x="35" y="73" width="50" height="2" class="fill-gray-300" />
                </template>

                <!-- dark: header / footer มืด -->
                <template v-else-if="value === 'dark'">
                    <rect width="120" height="80" class="fill-gray-100" />
                    <rect width="120" height="14" class="fill-gray-900" />
                    <rect x="6" y="3" width="16" height="8" rx="1.5" class="fill-brand-400" />
                    <rect x="66" y="4.5" width="12" height="5" rx="2.5" class="fill-white/25" />
                    <rect x="68" y="6" width="8" height="2" class="fill-white" />
                    <rect x="82" y="6" width="8" height="2" class="fill-gray-400" />
                    <rect x="94" y="6" width="8" height="2" class="fill-gray-400" />
                    <rect x="8" y="20" width="104" height="36" rx="2" class="fill-gray-300" />
                    <rect y="60" width="120" height="16" class="fill-gray-950" />
                    <rect x="8" y="64" width="30" height="2" class="fill-gray-500" />
                    <rect x="46" y="64" width="30" height="2" class="fill-gray-500" />
                    <rect x="84" y="64" width="28" height="2" class="fill-gray-500" />
                    <rect y="76" width="120" height="4" class="fill-black" />
                </template>
            </svg>
        </template>
    </VisualPicker>
</template>
