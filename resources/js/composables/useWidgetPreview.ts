import axios from 'axios';
import { onBeforeUnmount, ref, watch } from 'vue';
import type { Ref } from 'vue';

/**
 * ดึง "ข้อมูลตัวอย่าง" ของ widget (เช่น รายการ banner ที่ slideshow จะแสดง) จาก backend เพื่อวาดตัวอย่างในหน้าโครงสร้าง
 * — ค่าตั้งค่าที่กำลังแก้ยังไม่ถูกบันทึก จึงส่งไปกับ request ทุกครั้ง; ผลลัพธ์ cache ตามค่าที่ส่ง (widget หลายตัวที่ตั้งค่าเหมือนกัน
 * ยิงครั้งเดียว) และล้าง cache ทุกครั้งที่เข้าหน้าโครงสร้างใหม่ (clearWidgetPreviewCache) เพื่อให้เห็นข้อมูล banner ล่าสุดเสมอ
 */
export interface PreviewItem {
    id: number;
    /** hash_name ของรูป (ใช้ประกอบ URL ผ่าน route admin.system.file.get.thumbnail.size) — null = ไม่มีรูป (เฉพาะ widget ที่ไม่บังคับรูป เช่น Slideset) */
    image: string | null;
    title: string;
    intro_text: string;
    /** มีลิงก์หรือไม่ (Slideshow) — ไม่ส่ง URL มาเลย (ตัวอย่างกดไม่ได้) */
    has_link?: boolean;
    /** วันที่เผยแพร่ Y-m-d และจำนวนเข้าชม (Slideset จาก article) */
    date?: string | null;
    views?: number;
}

/** จำนวนรายการสูงสุดที่ backend ส่งมาเป็นตัวอย่าง (ตรงกับ SlideshowWidget::PREVIEW_LIMIT) */
export const PREVIEW_LIMIT = 10;

const cache = new Map<string, Promise<PreviewItem[]>>();

export function clearWidgetPreviewCache(): void {
    cache.clear();
}

function fetchPreview(widgetType: string, setting: Record<string, unknown>): Promise<PreviewItem[]> {
    const key = JSON.stringify([widgetType, setting]);
    let pending = cache.get(key);

    if (!pending) {
        pending = axios
            .get<{ items: PreviewItem[] }>(route('admin.page.item.widget.preview'), { params: { widget_type: widgetType, setting } })
            .then((response) => response.data.items)
            .catch((error) => {
                cache.delete(key); // ไม่ cache ความล้มเหลว (ลองใหม่ได้เมื่อเปลี่ยนค่า/เข้าหน้าใหม่)
                throw error;
            });
        cache.set(key, pending);
    }

    return pending;
}

/**
 * @param source ฟังก์ชันคืนค่าที่มีผลต่อข้อมูลตัวอย่าง (null = ยังดึงไม่ได้ เช่น ยังไม่ได้เลือกหมวดหมู่) — เปลี่ยนเมื่อไรจะดึงใหม่ให้เอง
 */
export function useWidgetPreview(widgetType: string, source: () => Record<string, unknown> | null): {
    items: Ref<PreviewItem[]>;
    loading: Ref<boolean>;
    failed: Ref<boolean>;
} {
    const items = ref<PreviewItem[]>([]);
    const loading = ref(false);
    const failed = ref(false);
    let requestId = 0;

    watch(
        () => {
            const setting = source();

            return setting === null ? null : JSON.stringify(setting);
        },
        (serialized) => {
            const current = ++requestId;
            failed.value = false;

            if (serialized === null) {
                items.value = [];
                loading.value = false;

                return;
            }

            loading.value = true;
            fetchPreview(widgetType, JSON.parse(serialized))
                .then((result) => {
                    if (current === requestId) items.value = result;
                })
                .catch(() => {
                    if (current === requestId) {
                        items.value = [];
                        failed.value = true;
                    }
                })
                .finally(() => {
                    if (current === requestId) loading.value = false;
                });
        },
        { immediate: true },
    );

    // ผลของ request ที่ค้างอยู่หลัง component ถูกทำลายไม่ต้องเขียนกลับ
    onBeforeUnmount(() => {
        requestId++;
    });

    return { items, loading, failed };
}
