import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { translate } from '@/utils/front';
import type { FrontLayoutProps } from '@/utils/front';

/**
 * ข้อมูล layout หน้าบ้าน (prop `front`) + ตัวแปลข้อความส่วนติดต่อผู้ใช้ — ใช้ใน component หน้าบ้านที่ไม่ได้รับ prop ตรง
 */
export function useFront(): {
    front: ComputedRef<FrontLayoutProps>;
    t: (key: string, replace?: Record<string, string | number>) => string;
} {
    const page = usePage();
    const front = computed(() => (page.props as unknown as { front: FrontLayoutProps }).front);

    return {
        front,
        t: (key, replace = {}) => translate(front.value?.t ?? {}, key, replace),
    };
}
