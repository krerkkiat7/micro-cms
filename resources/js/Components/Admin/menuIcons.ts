import {
    Circle,
    FileText,
    FolderOpen,
    FolderTree,
    History,
    Image,
    LayoutGrid,
    LayoutTemplate,
    List,
    ListTree,
    type LucideIcon,
    Mail,
    Megaphone,
    Newspaper,
    Presentation,
    Settings,
    Shield,
    SlidersHorizontal,
    Tags,
    Users,
} from 'lucide-vue-next';

/**
 * ไอคอนที่ใช้ได้กับ sys_menu_group.icon / sys_menu.icon
 * เก็บชื่อแบบ PascalCase ใน DB แล้ว map เป็น component ที่นี่ (curated เพื่อให้ tree-shake ได้)
 * ถ้าจะเพิ่มไอคอนใหม่: import จาก lucide-vue-next แล้วใส่ในแมพนี้
 */
const MENU_ICONS: Record<string, LucideIcon> = {
    Circle,
    FileText,
    FolderOpen,
    FolderTree,
    History,
    Image,
    LayoutGrid,
    LayoutTemplate,
    List,
    ListTree,
    Mail,
    Megaphone,
    Newspaper,
    Presentation,
    Settings,
    Shield,
    SlidersHorizontal,
    Tags,
    Users,
};

/** คืน component ของไอคอนตามชื่อ — ถ้าไม่มีในแมพ/เป็น null ใช้ fallback */
export function menuIcon(name: string | null | undefined, fallback: LucideIcon = Circle): LucideIcon {
    return (name ? MENU_ICONS[name] : undefined) ?? fallback;
}
