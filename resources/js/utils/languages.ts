/**
 * ป้ายชื่อภาษาสำหรับแสดงผล — ต้องตรงกับ UpdateSiteSettingRequest::AVAILABLE_LANGUAGES ฝั่ง backend
 * (เทียบเคียง LANGUAGE_OPTIONS ใน Pages/Admin/System/Setting/Index.vue) รหัสภาษาที่ไม่อยู่ในนี้
 * (เช่น เพิ่มภาษาใหม่ในอนาคตแต่ยังไม่ได้แก้ที่นี่) จะ fallback ไปแสดงเป็นรหัสภาษาตัวพิมพ์ใหญ่แทน
 */
const LANGUAGE_LABELS: Record<string, string> = {
    th: 'ภาษาไทย',
    en: 'ภาษาอังกฤษ',
};

export function languageLabel(code: string): string {
    return LANGUAGE_LABELS[code] ?? code.toUpperCase();
}
