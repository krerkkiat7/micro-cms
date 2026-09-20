import { inject } from 'vue';
import type { InjectionKey } from 'vue';
import type { ColumnData, RowData, WidgetData } from '@/utils/pageLayout';
import type { LanguageOption } from '@/types';

/**
 * บริบทของตัวแก้ไขโครงสร้างหน้าเพจ — `Pages/Admin/Page/Item/Layout.vue` เป็นเจ้าของข้อมูล (rows) และ dialog ทั้งหมดเพียงชุดเดียว
 * แล้ว provide ฟังก์ชันเหล่านี้ลงมาให้ RowBlock/ColumnBlock/WidgetBlock เรียกใช้ผ่าน inject (แทนการส่ง event ต่อกันหลายชั้น
 * แถว → คอลัมน์ → widget และแทนการสร้าง dialog ซ้ำในทุกบล็อก)
 */
export interface PageLayoutEditor {
    languages: LanguageOption[];
    /** true = ผู้ใช้ไม่มีสิทธิ์แก้ไข (ซ่อนแถบจัดการ/ปิดการลาก) */
    readonly: boolean;
    addColumn: (row: RowData) => void;
    addWidget: (column: ColumnData) => void;
    /** สลับแสดง/ซ่อน (status Y/N) ของแถว/คอลัมน์/widget */
    toggleStatus: (item: { status: 'Y' | 'N' }) => void;
    /** ขอลบ (มี dialog ยืนยันก่อน) — การลบมีผลกับฐานข้อมูลเมื่อกด "บันทึกโครงสร้าง" */
    removeRow: (row: RowData) => void;
    removeColumn: (row: RowData, column: ColumnData) => void;
    removeWidget: (column: ColumnData, widget: WidgetData) => void;
    editRow: (row: RowData) => void;
    editColumn: (row: RowData, column: ColumnData) => void;
    editWidget: (column: ColumnData, widget: WidgetData) => void;
    reorderRows: () => void;
}

export const PAGE_LAYOUT_EDITOR: InjectionKey<PageLayoutEditor> = Symbol('pageLayoutEditor');

export function usePageLayoutEditor(): PageLayoutEditor {
    const editor = inject(PAGE_LAYOUT_EDITOR);

    if (!editor) {
        throw new Error('usePageLayoutEditor() ต้องใช้ภายใต้หน้า Admin/Page/Item/Layout');
    }

    return editor;
}
