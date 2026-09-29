<script setup lang="ts">
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import RowBlock from '@/Components/Admin/PageLayout/RowBlock.vue';
import RowSettingsDialog from '@/Components/Admin/PageLayout/RowSettingsDialog.vue';
import ColumnSettingsDialog from '@/Components/Admin/PageLayout/ColumnSettingsDialog.vue';
import WidgetSettingsDialog from '@/Components/Admin/PageLayout/WidgetSettingsDialog.vue';
import WidgetTypePickerDialog from '@/Components/Admin/PageLayout/WidgetTypePickerDialog.vue';
import RowReorderDialog from '@/Components/Admin/PageLayout/RowReorderDialog.vue';
import ColumnReorderDialog from '@/Components/Admin/PageLayout/ColumnReorderDialog.vue';
import WidgetReorderDialog from '@/Components/Admin/PageLayout/WidgetReorderDialog.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, router } from '@inertiajs/vue3';
import type { RequestPayload } from '@inertiajs/core';
import { Plus, Save } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue';
import { PAGE_LAYOUT_EDITOR } from '@/composables/usePageLayoutEditor';
import { clearWidgetPreviewCache } from '@/composables/useWidgetPreview';
import {
    backgroundStyle,
    createColumn,
    createRow,
    createWidget,
    layoutFromServer,
    layoutToPayload,
} from '@/utils/pageLayout';
import type { ColumnData, ColumnSettings, RowData, RowSettings, ServerRow, WidgetData, WidgetOptions, WidgetSettings } from '@/utils/pageLayout';
import type { FileItem, LanguageOption } from '@/types';

const props = defineProps<{
    item: {
        id: number;
        title: string | null;
        background_color: string | null;
        background_image: FileItem | null;
        background_repeat: string | null;
        background_size: string | null;
        background_attachment: string | null;
        background_position: string | null;
        layout_updated_at: string | null;
    };
    rows: ServerRow[];
    languages: LanguageOption[];
    /** รายการชื่อฟอนต์ไทยให้เลือกใน dialog ตั้งค่า + URL สไตล์ชีตที่โหลดฟอนต์เหล่านั้น (ให้เห็นฟอนต์จริงในตัวอย่าง) */
    fonts: string[];
    fontsUrl: string;
    /** ข้อมูลประกอบฟอร์มตั้งค่า widget เฉพาะประเภท (จาก PageWidgetRegistry::options()) */
    widgetOptions: WidgetOptions;
    can: { manage: boolean };
}>();

const rows = ref<RowData[]>(layoutFromServer(props.rows));

// ค่าโครงสร้างที่บันทึกล่าสุด (serialize แล้ว) ใช้เทียบว่ามีการเปลี่ยนแปลงที่ยังไม่บันทึกหรือไม่
const savedSnapshot = ref(JSON.stringify(layoutToPayload(rows.value)));
const dirty = computed(() => JSON.stringify(layoutToPayload(rows.value)) !== savedSnapshot.value);

// หลังบันทึก backend redirect กลับมาพร้อม id ของรายการที่สร้างใหม่ — โหลดโครงสร้างใหม่ทับเพื่อให้ id ตรงกับฐานข้อมูล
watch(
    () => props.rows,
    (serverRows) => {
        rows.value = layoutFromServer(serverRows);
        savedSnapshot.value = JSON.stringify(layoutToPayload(rows.value));
    },
);

// ---- บันทึก ----
// ส่งด้วย router ตรง ๆ (ไม่ใช้ useForm) เพราะข้อมูลเป็นโครงสร้างซ้อนหลายชั้นที่ชนิดของ useForm รองรับไม่ดี และไม่ต้องใช้สถานะฟอร์มอื่น
const saving = ref(false);
const errors = ref<Record<string, string>>({});

function save() {
    router.put(
        route('admin.page.item.layout.update', props.item.id),
        // `setting` ของ widget เป็น JSON อิสระ ชนิดข้อมูลของ Inertia (FormDataConvertible) ไม่รองรับ จึงแปลงชนิดตรงนี้
        { rows: layoutToPayload(rows.value) } as unknown as RequestPayload,
        {
            preserveScroll: true,
            onBefore: () => {
                saving.value = true;
            },
            onError: (e) => (errors.value = e),
            onSuccess: () => (errors.value = {}),
            onFinish: () => (saving.value = false),
        },
    );
}

const errorMessages = computed(() => Object.values(errors.value));

// ---- กันออกจากหน้าโดยลืมบันทึก ----
function onBeforeUnload(e: BeforeUnloadEvent) {
    if (dirty.value) {
        e.preventDefault();
    }
}

let removeInertiaGuard: (() => void) | null = null;

// โหลดสไตล์ชีตฟอนต์ไทยทั้งหมดครั้งเดียว (ไม่ผูกกับหน้า — เบราว์เซอร์ดาวน์โหลดไฟล์ฟอนต์เฉพาะตัวที่ถูกใช้จริง)
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
    clearWidgetPreviewCache(); // เข้าหน้าโครงสร้างใหม่ทุกครั้ง ดึงข้อมูลตัวอย่าง (banner ฯลฯ) ล่าสุดเสมอ
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

// ---- แก้ไขโครงสร้างในหน่วยความจำ ----
function addRow() {
    rows.value.push(createRow(props.languages));
}

const rowDialog = ref<RowData | null>(null);
const columnDialog = ref<{ row: RowData; column: ColumnData } | null>(null);
const widgetDialog = ref<{ column: ColumnData; widget: WidgetData } | null>(null);
// เพิ่ม widget: ขั้น 1 เลือกประเภท (widgetPicker) → ขั้น 2 ตั้งค่า (widgetAdd) — widget ใหม่ยังไม่ถูกใส่ลงคอลัมน์จนกว่าจะกดยืนยันขั้น 2
const widgetPicker = ref<ColumnData | null>(null);
const widgetAdd = ref<{ column: ColumnData; widget: WidgetData } | null>(null);
const showReorder = ref(false);
const showColumnReorder = ref(false);
const showWidgetReorder = ref(false);

function saveRow(settings: RowSettings) {
    if (rowDialog.value) Object.assign(rowDialog.value, settings);
    rowDialog.value = null;
}

function deleteRow(row: RowData | null) {
    rows.value = rows.value.filter((r) => r !== row);
}

function deleteColumn(row: RowData, column: ColumnData) {
    row.columns = row.columns.filter((c) => c !== column);
}

function deleteWidget(column: ColumnData, widget: WidgetData) {
    column.widgets = column.widgets.filter((w) => w !== widget);
}

// ลบจาก dialog ตั้งค่า (dialog ถามยืนยันของตัวเองแล้ว)
function removeRow() {
    deleteRow(rowDialog.value);
    rowDialog.value = null;
}

function saveColumn(settings: ColumnSettings) {
    if (columnDialog.value) Object.assign(columnDialog.value.column, settings);
    columnDialog.value = null;
}

function removeColumn() {
    if (columnDialog.value) {
        deleteColumn(columnDialog.value.row, columnDialog.value.column);
    }
    columnDialog.value = null;
}

function saveWidget(settings: WidgetSettings) {
    if (widgetDialog.value) Object.assign(widgetDialog.value.widget, settings);
    widgetDialog.value = null;
}

function chooseWidgetType(widgetType: string) {
    if (widgetPicker.value) {
        widgetAdd.value = { column: widgetPicker.value, widget: createWidget(props.languages, widgetType) };
    }
    widgetPicker.value = null;
}

function backToWidgetPicker() {
    widgetPicker.value = widgetAdd.value?.column ?? null;
    widgetAdd.value = null;
}

function addWidget(settings: WidgetSettings) {
    if (widgetAdd.value) {
        Object.assign(widgetAdd.value.widget, settings);
        widgetAdd.value.column.widgets.push(widgetAdd.value.widget);
    }
    widgetAdd.value = null;
}

function removeWidget() {
    if (widgetDialog.value) {
        deleteWidget(widgetDialog.value.column, widgetDialog.value.widget);
    }
    widgetDialog.value = null;
}

// ลบจากไอคอนถังขยะบนแถบจัดการ — ถามยืนยันด้วย dialog เดียวกับที่อยู่ใน dialog ตั้งค่า แล้วค่อยลบ
type PendingDelete =
    | { kind: 'row'; row: RowData }
    | { kind: 'column'; row: RowData; column: ColumnData }
    | { kind: 'widget'; column: ColumnData; widget: WidgetData };

const pendingDelete = ref<PendingDelete | null>(null);

const DELETE_TEXTS = {
    row: { title: 'ยืนยันการลบแถว', confirm: 'ลบแถว', message: 'ต้องการลบแถวนี้พร้อมคอลัมน์และ Widget ทั้งหมดภายในใช่หรือไม่?' },
    column: { title: 'ยืนยันการลบคอลัมน์', confirm: 'ลบคอลัมน์', message: 'ต้องการลบคอลัมน์นี้พร้อม Widget ทั้งหมดภายในใช่หรือไม่?' },
    widget: { title: 'ยืนยันการลบ Widget', confirm: 'ลบ Widget', message: 'ต้องการลบ Widget นี้ใช่หรือไม่?' },
} as const;

function confirmDelete() {
    const target = pendingDelete.value;

    if (target?.kind === 'row') deleteRow(target.row);
    if (target?.kind === 'column') deleteColumn(target.row, target.column);
    if (target?.kind === 'widget') deleteWidget(target.column, target.widget);

    pendingDelete.value = null;
}

function applyRowOrder(order: RowData[]) {
    rows.value = order;
    showReorder.value = false;
}

function applyColumnOrder(order: { row: RowData; columns: ColumnData[] }[]) {
    order.forEach(({ row, columns }) => {
        row.columns = columns;
    });
    showColumnReorder.value = false;
}

function applyWidgetOrder(order: { row: RowData; columns: { column: ColumnData; widgets: WidgetData[] }[] }[]) {
    order.forEach(({ columns }) => {
        columns.forEach(({ column, widgets }) => {
            column.widgets = widgets;
        });
    });
    showWidgetReorder.value = false;
}

provide(PAGE_LAYOUT_EDITOR, {
    languages: props.languages,
    readonly: !props.can.manage,
    addColumn: (row) => row.columns.push(createColumn(props.languages, row.columns)),
    addWidget: (column) => (widgetPicker.value = column),
    toggleStatus: (target) => {
        target.status = target.status === 'Y' ? 'N' : 'Y';
    },
    removeRow: (row) => (pendingDelete.value = { kind: 'row', row }),
    removeColumn: (row, column) => (pendingDelete.value = { kind: 'column', row, column }),
    removeWidget: (column, widget) => (pendingDelete.value = { kind: 'widget', column, widget }),
    editRow: (row) => (rowDialog.value = row),
    editColumn: (row, column) => (columnDialog.value = { row, column }),
    editWidget: (column, widget) => (widgetDialog.value = { column, widget }),
    reorderRows: () => (showReorder.value = true),
    reorderColumns: () => (showColumnReorder.value = true),
    reorderWidgets: () => (showWidgetReorder.value = true),
});

// ---- หน้าจอ ----
const pageTitle = computed(() => props.item.title || `หน้าเพจ #${props.item.id}`);

const tabs = computed(() => [
    { label: 'ข้อมูลทั่วไป', href: route('admin.page.item.edit', props.item.id), active: false },
    { label: 'โครงสร้าง', href: route('admin.page.item.layout', props.item.id), active: true },
]);

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'หน้าเพจ', href: route('admin.page.item.index') },
    { label: pageTitle.value },
]);

// พื้นหลังของทั้งหน้า (จากแท็บข้อมูลทั่วไป) ใช้เป็นพื้นของ canvas ให้เห็นภาพใกล้เคียงหน้าจริง
const canvasStyle = computed(() =>
    backgroundStyle({
        background_color: props.item.background_color ?? '',
        background_image: props.item.background_image ? [props.item.background_image] : [],
        background_repeat: props.item.background_repeat ?? '',
        background_size: props.item.background_size ?? '',
        background_attachment: props.item.background_attachment ?? '',
        background_position: props.item.background_position ?? '',
    }),
);

function formatDate(value: string | null): string {
    if (!value) return '-';

    return new Date(value.replace(' ', 'T')).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <Head :title="`โครงสร้างหน้าเพจ: ${pageTitle}`" />

    <AdminLayout>
        <template #header>
            <PageHeader :title="pageTitle" :breadcrumbs="breadcrumbs" />
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
                <span class="text-xs text-gray-500 sm:ml-auto">บันทึกโครงสร้างล่าสุด: {{ formatDate(item.layout_updated_at) }}</span>
            </div>

            <div v-if="errorMessages.length" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <p class="font-medium">บันทึกโครงสร้างไม่สำเร็จ</p>
                <ul class="mt-1 list-inside list-disc">
                    <li v-for="(message, index) in errorMessages" :key="index">{{ message }}</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 shadow-xs" :style="canvasStyle">
                <div v-if="can.manage" class="mb-4">
                    <SecondaryButton type="button" @click="addRow">
                        <Plus class="mr-1.5 size-4" /> เพิ่มแถว
                    </SecondaryButton>
                </div>

                <div class="space-y-4">
                    <RowBlock v-for="(row, index) in rows" :key="row._key" :row="row" :index="index" />
                </div>

                <p v-if="rows.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white/70 py-10 text-center text-sm text-gray-500">
                    ยังไม่มีโครงสร้าง — กด "เพิ่มแถว" เพื่อเริ่มจัดหน้า
                </p>

                <div v-if="can.manage && rows.length > 0" class="mt-4">
                    <SecondaryButton type="button" @click="addRow">
                        <Plus class="mr-1.5 size-4" /> เพิ่มแถว
                    </SecondaryButton>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <template v-if="can.manage">
                    <PrimaryButton type="button" :disabled="saving" @click="save">
                        <Save class="mr-1.5 size-4" /> บันทึกโครงสร้าง
                    </PrimaryButton>
                    <span v-if="dirty" class="text-sm font-medium text-amber-600">มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก</span>
                </template>
                <BackToListButton :href="route('admin.page.item.index')" />
            </div>
        </div>

        <RowSettingsDialog
            :show="rowDialog !== null"
            :row="rowDialog"
            :languages="languages"
            :fonts="fonts"
            @close="rowDialog = null"
            @save="saveRow"
            @remove="removeRow"
        />
        <ColumnSettingsDialog
            :show="columnDialog !== null"
            :column="columnDialog?.column ?? null"
            :languages="languages"
            :fonts="fonts"
            @close="columnDialog = null"
            @save="saveColumn"
            @remove="removeColumn"
        />
        <WidgetTypePickerDialog :show="widgetPicker !== null" @close="widgetPicker = null" @next="chooseWidgetType" />
        <WidgetSettingsDialog
            :show="widgetAdd !== null"
            mode="add"
            :widget="widgetAdd?.widget ?? null"
            :languages="languages"
            :fonts="fonts"
            :widget-options="widgetOptions"
            @close="widgetAdd = null"
            @back="backToWidgetPicker"
            @save="addWidget"
        />
        <WidgetSettingsDialog
            :show="widgetDialog !== null"
            mode="edit"
            :widget="widgetDialog?.widget ?? null"
            :languages="languages"
            :fonts="fonts"
            :widget-options="widgetOptions"
            @close="widgetDialog = null"
            @save="saveWidget"
            @remove="removeWidget"
        />
        <ConfirmDialog
            :show="pendingDelete !== null"
            :title="pendingDelete ? DELETE_TEXTS[pendingDelete.kind].title : ''"
            :confirm-text="pendingDelete ? DELETE_TEXTS[pendingDelete.kind].confirm : 'ลบ'"
            @confirm="confirmDelete"
            @cancel="pendingDelete = null"
        >
            {{ pendingDelete ? DELETE_TEXTS[pendingDelete.kind].message : '' }}
            (การลบจะมีผลเมื่อกด "บันทึกโครงสร้าง")
        </ConfirmDialog>
        <RowReorderDialog :show="showReorder" :rows="rows" :languages="languages" @close="showReorder = false" @confirm="applyRowOrder" />
        <ColumnReorderDialog :show="showColumnReorder" :rows="rows" :languages="languages" @close="showColumnReorder = false" @confirm="applyColumnOrder" />
        <WidgetReorderDialog :show="showWidgetReorder" :rows="rows" :languages="languages" @close="showWidgetReorder = false" @confirm="applyWidgetOrder" />
    </AdminLayout>
</template>
