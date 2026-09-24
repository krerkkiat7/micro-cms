import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * keep-alive ของ log_back_access / log_front_access — ระหว่างที่เปิดหน้าค้างไว้ ยิง ping ไป bump `last_visited`
 * เพื่อให้รู้ว่าผู้ใช้อยู่หน้านั้นนานเท่าไร
 *
 * - อ่าน token จาก shared prop `accessLog.token` (null = หน้านี้ไม่ได้บันทึก log → ไม่ทำอะไร)
 * - ยิงตอน tab ถูกซ่อน / ปิดหน้า (ได้เวลาสุดท้ายจริง) + interval หยาบ ๆ ระหว่างเปิดค้าง
 * - เรียกครั้งเดียวใน layout (AdminLayout / Front layout — โปรเจกต์ไม่ใช้ persistent layout — remount ทุกครั้งที่เปลี่ยนหน้า)
 * - endpoint ping ถูกยกเว้น CSRF (หลังบ้าน: auth + scope user_id, หน้าบ้าน: token + session_id) — sendBeacon ตั้ง header ไม่ได้
 *
 * @param routeName route ของ endpoint ping (ค่าเริ่มต้น = หลังบ้าน; หน้าบ้านใช้ `front.access.ping`)
 */
const PING_INTERVAL_MS = 45_000;

export function useAccessHeartbeat(routeName: string = 'admin.system.backlog.access.ping'): void {
    const page = usePage();
    const token = page.props.accessLog?.token ?? null;

    if (!token) return;

    const url = route(routeName);
    let timer: ReturnType<typeof setInterval> | null = null;

    const body = (): FormData => {
        const data = new FormData();
        data.append('token', token);
        return data;
    };

    const ping = (): void => {
        try {
            if (navigator.sendBeacon?.(url, body())) return;
        } catch {
            /* ตกไปใช้ fetch ด้านล่าง */
        }
        try {
            void fetch(url, {
                method: 'POST',
                body: body(),
                keepalive: true,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).catch(() => {});
        } catch {
            /* เงียบไว้ — heartbeat พลาดได้ */
        }
    };

    const onVisibilityChange = (): void => {
        if (document.visibilityState === 'hidden') ping();
    };

    onMounted(() => {
        document.addEventListener('visibilitychange', onVisibilityChange);
        window.addEventListener('pagehide', ping);
        timer = setInterval(() => {
            if (document.visibilityState === 'visible') ping();
        }, PING_INTERVAL_MS);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('visibilitychange', onVisibilityChange);
        window.removeEventListener('pagehide', ping);
        if (timer) clearInterval(timer);
        ping(); // ยิงครั้งสุดท้ายตอนออกจากหน้า (Inertia navigate)
    });
}
