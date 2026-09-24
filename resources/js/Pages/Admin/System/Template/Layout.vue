<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ZoneToolbar from '@/Components/Admin/Template/ZoneToolbar.vue';
import HeaderPreview from '@/Components/Admin/Template/Preview/HeaderPreview.vue';
import BodyPreview from '@/Components/Admin/Template/Preview/BodyPreview.vue';
import FooterPreview from '@/Components/Admin/Template/Preview/FooterPreview.vue';
import AsidePreview from '@/Components/Admin/Template/Preview/AsidePreview.vue';
import HeaderSettingsDialog from '@/Components/Admin/Template/HeaderSettingsDialog.vue';
import BodySettingsDialog from '@/Components/Admin/Template/BodySettingsDialog.vue';
import FooterSettingsDialog from '@/Components/Admin/Template/FooterSettingsDialog.vue';
import AsideSettingsDialog from '@/Components/Admin/Template/AsideSettingsDialog.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { RequestPayload } from '@inertiajs/core';
import { Save } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { templateTabs, zonesFromServer, zonesToPayload } from '@/utils/template';
import type { AsideZone, BodyZone, FooterZone, HeaderZone, ServerZones, TemplatePreviewData, ZoneName } from '@/utils/template';

/**
 * แท็บโครงสร้างของ template — พื้นที่ตรงกลางเป็นตัวอย่างหน้าเว็บแบ่ง 4 โซน (header / main body / aside / footer) ตามค่าตั้งค่า
 * แต่ละโซนมีแถบจัดการ (เฟือง = เปิด dialog ตั้งค่า, ลูกตา = แสดง/ซ่อนโซน) — แก้ในหน่วยความจำแล้วกด "บันทึกโครงสร้าง" ทีเดียว
 * (pattern เดียวกับ Pages/Admin/Page/Item/Layout.vue: dirty snapshot + กันออกจากหน้าโดยลืมบันทึก)
 */
const props = defineProps<{
    template: { id: number; name: string; preset: string | null; status: string; layout_updated_at: string | null };
    zones: ServerZones;
    preview: TemplatePreviewData;
    fonts: string[];
    fontsUrl: string;
    can: { manage: boolean };
}>();

const zones = ref(zonesFromServer(props.zones));

const savedSnapshot = ref(JSON.stringify(zonesToPayload(zones.value)));
const dirty = computed(() => JSON.stringify(zonesToPayload(zones.value)) !== savedSnapshot.value);

watch(
    () => props.zones,
    (serverZones) => {
        zones.value = zonesFromServer(serverZones);
        savedSnapshot.value = JSON.stringify(zonesToPayload(zones.value));
    },
);

// ---- บันทึก ----
const saving = ref(false);
const errors = ref<Record<string, string>>({});

function save() {
    router.put(route('admin.system.template.layout.update', props.template.id), zonesToPayload(zones.value) as unknown as RequestPayload, {
        preserveScroll: true,
        onBefore: () => {
            saving.value = true;
        },
        onError: (e) => (errors.value = e),
        onSuccess: () => (errors.value = {}),
        onFinish: () => (saving.value = false),
    });
}

const errorMessages = computed(() => [...new Set(Object.values(errors.value))]);

// ---- กันออกจากหน้าโดยลืมบันทึก ----
function onBeforeUnload(e: BeforeUnloadEvent) {
    if (dirty.value) {
        e.preventDefault();
    }
}

let removeInertiaGuard: (() => void) | null = null;

// ฟอนต์ไทยของตัวอย่าง footer (โหลดสไตล์ชีตชุดเดียวกับหน้าโครงสร้างของ page)
function loadFonts() {
    const id = 'page-layout-fonts';

    if (document.getElementById(id)) {
        return;
    }

    const link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href = props.fontsUrl;
    document.head.appendChild(link);
}

onMounted(() => {
    loadFonts();
    window.addEventListener('beforeunload', onBeforeUnload);
    removeInertiaGuard = router.on('before', (event) => {
        if (dirty.value && !saving.value && !window.confirm('มีการเปลี่ยนแปลงโครงสร้างที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')) {
            event.preventDefault();
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
    removeInertiaGuard?.();
});

// ---- แก้ไขในหน่วยความจำ ----
const editing = ref<ZoneName | null>(null);

function toggleZone(zone: 'header' | 'footer' | 'aside') {
    zones.value[zone].status = zones.value[zone].status === 'Y' ? 'N' : 'Y';
}

function applyZone<K extends ZoneName>(zone: K, values: (typeof zones.value)[K]) {
    Object.assign(zones.value[zone], values);
    editing.value = null;
}

const readonly = computed(() => !props.can.manage);

const asideSide = computed(() => zones.value.aside.toggle_position);
const asideWidth = computed(() => (zones.value.aside.display_type === 'fullscreen' ? 'sm:w-1/2' : 'sm:w-72'));

// ---- หน้าจอ ----
const tabs = computed(() => templateTabs(props.template.id, 'layout'));

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการ Template', href: route('admin.system.template.index') },
    { label: props.template.name },
]);

function formatDate(value: string | null): string {
    if (!value) return '-';

    return new Date(value.replace(' ', 'T')).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <Head :title="`โครงสร้าง Template: ${template.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="template.name" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-4">
            <TabNav :tabs="tabs" />

            <div class="flex flex-wrap items-center gap-3">
                <template v-if="can.manage">
                    <PrimaryButton type="button" :disabled="saving" @click="save">
                        <Save class="mr-1.5 size-4" /> บันทึกโครงสร้าง
                    </PrimaryButton>
                    <span v-if="dirty" class="text-sm font-medium text-amber-600">มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก</span>
                </template>
                <Link :href="route('admin.system.template.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    กลับไปหน้ารายการ
                </Link>
                <span class="text-xs text-gray-500 sm:ml-auto">บันทึกโครงสร้างล่าสุด: {{ formatDate(template.layout_updated_at) }}</span>
            </div>

            <div v-if="errorMessages.length" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <p class="font-medium">บันทึกโครงสร้างไม่สำเร็จ</p>
                <ul class="mt-1 list-inside list-disc">
                    <li v-for="(message, index) in errorMessages" :key="index">{{ message }}</li>
                </ul>
            </div>

            <p class="text-xs text-gray-500">
                ตัวอย่างใช้ข้อมูลจริงของระบบ ณ ปัจจุบัน (โลโก้ / ชื่อเว็บ / เมนูหน้าบ้าน / ข้อมูลติดต่อ / Social Media) — ลิงก์ในตัวอย่างกดไม่ได้
            </p>

            <!-- ตัวอย่างหน้าเว็บ 4 โซน -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <!-- Header -->
                <div class="relative border-b-2 border-dashed border-brand-200 pt-7">
                    <ZoneToolbar
                        title="Header"
                        toggleable
                        :hidden="zones.header.status !== 'Y'"
                        :readonly="readonly"
                        @settings="editing = 'header'"
                        @toggle="toggleZone('header')"
                    />
                    <div v-if="zones.header.status === 'Y'" class="pointer-events-none select-none">
                        <HeaderPreview :zone="zones.header" :aside="zones.aside" :preview="preview" />
                    </div>
                    <p v-else class="px-4 py-4 text-center text-sm text-gray-400">Header ถูกซ่อน</p>
                </div>

                <!-- Main body + Aside -->
                <div class="flex flex-col sm:flex-row" :class="asideSide === 'left' ? 'sm:flex-row-reverse' : ''">
                    <div class="relative min-w-0 flex-1 pt-7">
                        <ZoneToolbar title="Main Body" :readonly="readonly" @settings="editing = 'body'" />
                        <div class="pointer-events-none h-full select-none">
                            <BodyPreview :zone="zones.body" />
                        </div>
                    </div>

                    <div
                        class="relative shrink-0 border-dashed border-brand-200 pt-7 max-sm:border-t-2"
                        :class="[asideWidth, asideSide === 'left' ? 'sm:border-r-2' : 'sm:border-l-2']"
                    >
                        <ZoneToolbar
                            title="Aside"
                            toggleable
                            :hidden="zones.aside.status !== 'Y'"
                            :readonly="readonly"
                            @settings="editing = 'aside'"
                            @toggle="toggleZone('aside')"
                        />
                        <div v-if="zones.aside.status === 'Y'" class="pointer-events-none h-full select-none">
                            <AsidePreview :zone="zones.aside" :menu="preview.menu" />
                        </div>
                        <p v-else class="px-4 py-4 text-center text-sm text-gray-400">ไม่ใช้เมนูข้าง</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="relative border-t-2 border-dashed border-brand-200 pt-7">
                    <ZoneToolbar
                        title="Footer"
                        toggleable
                        :hidden="zones.footer.status !== 'Y'"
                        :readonly="readonly"
                        @settings="editing = 'footer'"
                        @toggle="toggleZone('footer')"
                    />
                    <div v-if="zones.footer.status === 'Y'" class="pointer-events-none select-none">
                        <FooterPreview :zone="zones.footer" :preview="preview" />
                    </div>
                    <p v-else class="px-4 py-4 text-center text-sm text-gray-400">Footer ถูกซ่อน</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <template v-if="can.manage">
                    <PrimaryButton type="button" :disabled="saving" @click="save">
                        <Save class="mr-1.5 size-4" /> บันทึกโครงสร้าง
                    </PrimaryButton>
                    <span v-if="dirty" class="text-sm font-medium text-amber-600">มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก</span>
                </template>
                <Link :href="route('admin.system.template.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    กลับไปหน้ารายการ
                </Link>
            </div>
        </div>

        <HeaderSettingsDialog
            :show="editing === 'header'"
            :zone="zones.header"
            @close="editing = null"
            @save="(z: HeaderZone) => applyZone('header', z)"
        />
        <BodySettingsDialog :show="editing === 'body'" :zone="zones.body" @close="editing = null" @save="(z: BodyZone) => applyZone('body', z)" />
        <FooterSettingsDialog
            :show="editing === 'footer'"
            :zone="zones.footer"
            :fonts="fonts"
            @close="editing = null"
            @save="(z: FooterZone) => applyZone('footer', z)"
        />
        <AsideSettingsDialog :show="editing === 'aside'" :zone="zones.aside" @close="editing = null" @save="(z: AsideZone) => applyZone('aside', z)" />
    </AdminLayout>
</template>
