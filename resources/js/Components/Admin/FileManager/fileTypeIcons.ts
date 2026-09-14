import {
    File as FileIcon,
    FileArchive,
    FileSpreadsheet,
    FileText,
    Music,
    Presentation,
    Video,
    type LucideIcon,
} from 'lucide-vue-next';

/**
 * ไอคอนตามนามสกุลไฟล์ — ใช้กับไฟล์ที่ไม่ใช่รูปภาพ (รูปภาพแสดง thumbnail จริงแทน)
 */
const ICONS: Record<string, LucideIcon> = {
    doc: FileText,
    docx: FileText,
    pdf: FileText,
    xls: FileSpreadsheet,
    xlsx: FileSpreadsheet,
    ppt: Presentation,
    pptx: Presentation,
    mp3: Music,
    mp4: Video,
    zip: FileArchive,
};

export function fileTypeIcon(extension: string | null | undefined): LucideIcon {
    if (!extension) {
        return FileIcon;
    }

    return ICONS[extension.toLowerCase()] ?? FileIcon;
}
