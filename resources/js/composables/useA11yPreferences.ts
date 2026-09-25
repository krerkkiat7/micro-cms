import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * เครื่องมือช่วยการเข้าถึงของหน้าบ้าน (WCAG) — ปรับขนาดตัวอักษร และการแสดงสี (ปกติ / ความคมชัดสูง / ขาวดำ)
 * จำค่าไว้ใน localStorage และใส่ผลที่ `<html>` (class/zoom) เพื่อให้มีผลทั้งหน้ารวมถึงข้อความที่กำหนดขนาดเป็น px จากหลังบ้าน
 * สไตล์ของแต่ละโหมดอยู่ที่ resources/css/app.css (html.front-contrast-*)
 */
export type ContrastMode = 'normal' | 'high' | 'grayscale';

/** ระดับขนาดตัวอักษร (-1 = เล็กลง, 0 = ปกติ, 1-2 = ใหญ่ขึ้น) → ค่า zoom */
const FONT_SCALES: Record<number, number> = { [-1]: 0.9, 0: 1, 1: 1.15, 2: 1.3 };
const MIN_LEVEL = -1;
const MAX_LEVEL = 2;
const FONT_KEY = 'front.a11y.font';
const CONTRAST_KEY = 'front.a11y.contrast';

const fontLevel = ref(0);
const contrast = ref<ContrastMode>('normal');
let users = 0;

function read(): void {
    try {
        const level = Number(localStorage.getItem(FONT_KEY) ?? 0);
        fontLevel.value = Number.isInteger(level) && level >= MIN_LEVEL && level <= MAX_LEVEL ? level : 0;
        const mode = localStorage.getItem(CONTRAST_KEY);
        contrast.value = mode === 'high' || mode === 'grayscale' ? mode : 'normal';
    } catch {
        /* localStorage ใช้ไม่ได้ (โหมดส่วนตัว) — ใช้ค่าปกติ */
    }
}

function apply(): void {
    const root = document.documentElement;
    const scale = FONT_SCALES[fontLevel.value] ?? 1;

    root.style.setProperty('zoom', scale === 1 ? '' : String(scale));
    root.classList.toggle('front-contrast-high', contrast.value === 'high');
    root.classList.toggle('front-contrast-grayscale', contrast.value === 'grayscale');
}

function clear(): void {
    const root = document.documentElement;
    root.style.removeProperty('zoom');
    root.classList.remove('front-contrast-high', 'front-contrast-grayscale');
}

function save(): void {
    try {
        localStorage.setItem(FONT_KEY, String(fontLevel.value));
        localStorage.setItem(CONTRAST_KEY, contrast.value);
    } catch {
        /* เงียบไว้ */
    }
}

watch([fontLevel, contrast], () => {
    if (typeof document === 'undefined') return;
    apply();
    save();
});

export function useA11yPreferences() {
    onMounted(() => {
        if (users++ === 0) {
            read();
            apply();
        }
    });

    onBeforeUnmount(() => {
        // ออกจาก layout หน้าบ้าน (เช่น ไปหน้าหลังบ้าน) — คืนค่า <html> ให้ปกติ
        if (--users === 0) clear();
    });

    return {
        fontLevel,
        contrast,
        canDecrease: () => fontLevel.value > MIN_LEVEL,
        canIncrease: () => fontLevel.value < MAX_LEVEL,
        decrease: () => (fontLevel.value = Math.max(MIN_LEVEL, fontLevel.value - 1)),
        increase: () => (fontLevel.value = Math.min(MAX_LEVEL, fontLevel.value + 1)),
        reset: () => (fontLevel.value = 0),
        setFontLevel: (level: number) => (fontLevel.value = Math.max(MIN_LEVEL, Math.min(MAX_LEVEL, level))),
        setContrast: (mode: ContrastMode) => (contrast.value = mode),
    };
}
