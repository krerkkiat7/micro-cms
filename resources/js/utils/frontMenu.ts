import type { FrontMenuDetailForm, FrontMenuNode, LanguageOption } from '@/types';

/** ตัวเลือก Y/N ทั่วไป (เป็นหน้าหลัก / ตัวหนา) — ต่างจาก STATUS_OPTIONS/SHOW_OPTIONS ที่มีความหมายเฉพาะทาง */
export const YES_NO_OPTIONS = [
    { value: 'Y', label: 'ใช่' },
    { value: 'N', label: 'ไม่ใช่' },
];

/** ค่าคงที่ประเภทเมนู — ต้องตรงกับ App\Support\FrontMenuType ฝั่ง backend เสมอ */
export const FrontMenuType = {
    NONE: 'none',
    HEADING: 'heading',
    EXTERNAL: 'external',
    ARTICLE_CATEGORY: 'article_category',
    ARTICLE_ITEM: 'article_item',
    PAGE: 'page',
    CONTACTUS: 'contactus',
} as const;

/** ประเภทเมนูที่ลิงก์ไปหน้าเนื้อหาจริงบนเว็บนี้ — เฉพาะกลุ่มนี้เท่านั้นที่มีชุดตั้งค่า "หัวเรื่องของหน้าเป้าหมาย" */
export const CONTENT_MENU_TYPES: string[] = [FrontMenuType.ARTICLE_CATEGORY, FrontMenuType.ARTICLE_ITEM, FrontMenuType.PAGE, FrontMenuType.CONTACTUS];

/** อัตราส่วนรูปภาพส่วนหัว — 'natural' = ไม่ครอป แสดงตามขนาดจริงของรูป (ไม่มี image_fit/สีพื้นหลังให้ตั้ง) */
export const HEADER_IMAGE_ASPECT_OPTIONS = [
    { value: 'natural', label: 'ตามขนาดรูปภาพ' },
    { value: '16:9', label: '16:9 (มาตรฐาน)' },
    { value: '21:9', label: '21:9 (แบนเนอร์กว้าง)' },
    { value: '4:3', label: '4:3' },
    { value: '1:1', label: '1:1 (จัตุรัส)' },
];

export function menuTypeOptions(menuTypes: Record<string, string>): { value: string; label: string }[] {
    return Object.entries(menuTypes).map(([value, label]) => ({ value, label }));
}

function emptyMenuDetail(): FrontMenuDetailForm {
    return { name: '', title: '', subtitle: '' };
}

export function emptyMenuDetails(languages: LanguageOption[]): Record<string, FrontMenuDetailForm> {
    const detail: Record<string, FrontMenuDetailForm> = {};
    languages.forEach((lang) => {
        detail[lang.code] = emptyMenuDetail();
    });

    return detail;
}

/**
 * ตัวเลือก Parent Menu แบบ flat มีเยื้องระดับ (indent ด้วยจำนวนขีด) — แสดงเฉพาะเมนูประเภท "เมนูหัวข้อ"
 * และตัดตัวเอง + เมนูลูกหลานของตัวเองออก (กัน cycle) เมื่อแก้ไขเมนูที่มีอยู่แล้ว (editingId)
 */
export function parentMenuOptions(
    tree: FrontMenuNode[],
    editingId: number | null,
): { value: string; label: string }[] {
    const excluded = new Set<number>();

    if (editingId !== null) {
        collectDescendantIds(tree, editingId, excluded);
        excluded.add(editingId);
    }

    const options: { value: string; label: string }[] = [];

    function walk(nodes: FrontMenuNode[], depth: number) {
        for (const node of nodes) {
            if (!excluded.has(node.id) && node.menu_type === FrontMenuType.HEADING) {
                options.push({ value: String(node.id), label: `${'— '.repeat(depth)}${defaultMenuName(node)}` });
            }
            if (!excluded.has(node.id)) {
                walk(node.children, depth + 1);
            }
        }
    }

    walk(tree, 0);

    return options;
}

function collectDescendantIds(tree: FrontMenuNode[], targetId: number, into: Set<number>) {
    const target = findNode(tree, targetId);
    if (!target) return;

    function walk(nodes: FrontMenuNode[]) {
        for (const node of nodes) {
            into.add(node.id);
            walk(node.children);
        }
    }

    walk(target.children);
}

function findNode(nodes: FrontMenuNode[], id: number): FrontMenuNode | null {
    for (const node of nodes) {
        if (node.id === id) return node;
        const found = findNode(node.children, id);
        if (found) return found;
    }

    return null;
}

/** ชื่อเมนูภาษาหลักไว้แสดงในรายการ/การเรียงลำดับ/ตัวเลือก — ภาษาหลักไม่มีชื่อ ค่อยใช้ภาษาอื่นที่มีค่า */
export function defaultMenuName(node: FrontMenuNode): string {
    if (node.name) return node.name;

    const withName = Object.values(node.detail).find((d) => d.name);

    return withName?.name ?? `เมนู #${node.id}`;
}

export interface ReorderItem {
    id: number;
    parent_id: number | null;
    sort_order: number;
}

/** แปลง tree (หลังลากจัดเรียงใน dialog) เป็น flat list {id, parent_id, sort_order} ส่งให้ backend ทีเดียว */
export function flattenForReorder(tree: FrontMenuNode[]): ReorderItem[] {
    const items: ReorderItem[] = [];

    function walk(nodes: FrontMenuNode[], parentId: number | null) {
        nodes.forEach((node, index) => {
            items.push({ id: node.id, parent_id: parentId, sort_order: index });
            walk(node.children, node.id);
        });
    }

    walk(tree, null);

    return items;
}
