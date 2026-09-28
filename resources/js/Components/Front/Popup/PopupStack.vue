<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PopupDialog from '@/Components/Front/Popup/PopupDialog.vue';
import type { FrontPopup } from '@/utils/front';

/**
 * กอง popup ของหน้าปัจจุบัน (prop `popups` จาก FrontController — กรองตามเมนูของหน้า + เรียงตามตั้งค่าแล้ว รายการแรกอยู่บนสุด)
 *
 * การปิด (เก็บฝั่งเบราว์เซอร์ ห่อ try/catch เพราะบางเบราว์เซอร์/โหมดส่วนตัวใช้ storage ไม่ได้):
 * - "ไม่แสดงวันนี้อีก" → localStorage `front.popup.dismiss.{id}` = วันที่วันนี้ (เวลาเครื่องผู้ชม) — วันถัดไปแสดงใหม่
 * - "ปิด" → sessionStorage `front.popup.closed.{id}` — ไม่เด้งซ้ำทุกครั้งที่เปลี่ยนหน้าในการเข้าชมครั้งเดียวกัน
 * มี modal อย่างน้อย 1 รายการ = พื้นหลังทึบ 1 ชั้น + ล็อกการเลื่อนหน้า; floating ไม่มีพื้นหลังและไม่ล็อกหน้า
 */
const DISMISS_PREFIX = 'front.popup.dismiss.';
const CLOSED_PREFIX = 'front.popup.closed.';
const BASE_Z = 51; // เหนือ aside (z-50) — skip link z-60 / lightbox z-70

const page = usePage();
const popups = computed(() => ((page.props as unknown as { popups?: FrontPopup[] }).popups ?? []).filter((popup) => popup.parts.length > 0));

// เริ่มจากว่างจนกว่าจะอ่าน storage บนเบราว์เซอร์ได้ — กัน popup ที่ปิดไว้แล้วกระพริบขึ้นมาก่อน
const hidden = ref<Set<number> | null>(null);

function today(): string {
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function readHidden(): Set<number> {
    const set = new Set<number>();

    for (const popup of popups.value) {
        try {
            if (window.localStorage.getItem(DISMISS_PREFIX + popup.id) === today() || window.sessionStorage.getItem(CLOSED_PREFIX + popup.id) === '1') {
                set.add(popup.id);
            }
        } catch {
            // storage ใช้ไม่ได้ — แสดงตามปกติ
        }
    }

    return set;
}

const visible = computed(() => (hidden.value === null ? [] : popups.value.filter((popup) => !hidden.value!.has(popup.id))));
const hasModal = computed(() => visible.value.some((popup) => popup.display_type === 'modal'));

function hide(popup: FrontPopup, storageKey: string, value: string, storage: 'local' | 'session'): void {
    try {
        (storage === 'local' ? window.localStorage : window.sessionStorage).setItem(storageKey, value);
    } catch {
        // เก็บไม่ได้ก็ยังปิดในหน้านี้
    }

    hidden.value = new Set([...(hidden.value ?? []), popup.id]);
}

const close = (popup: FrontPopup) => hide(popup, CLOSED_PREFIX + popup.id, '1', 'session');
const dismissToday = (popup: FrontPopup) => hide(popup, DISMISS_PREFIX + popup.id, today(), 'local');

// คืนโฟกัสเดิมเมื่อ modal ปิดหมด / ย้ายโฟกัสไปรายการบนสุดถัดไปที่เป็น modal
let returnFocus: HTMLElement | null = null;
const dialogs = new Map<number, InstanceType<typeof PopupDialog>>();

function setDialog(id: number, el: unknown): void {
    if (el) dialogs.set(id, el as InstanceType<typeof PopupDialog>);
    else dialogs.delete(id);
}

watch(hasModal, (value) => {
    document.body.style.overflow = value ? 'hidden' : '';

    if (!value) {
        returnFocus?.focus();
        returnFocus = null;
    }
});

watch(
    () => visible.value[0]?.id,
    () => {
        const top = visible.value[0];

        if (top?.display_type === 'modal') {
            void nextTick(() => dialogs.get(top.id)?.focus());
        }
    },
);

onMounted(() => {
    returnFocus = document.activeElement as HTMLElement | null;
    hidden.value = readHidden();
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
            <div v-if="hasModal" class="fixed inset-0 bg-black/50" :style="{ zIndex: BASE_Z }" aria-hidden="true" />
        </Transition>

        <!-- เรนเดอร์ตามลำดับ แต่ z-index ของรายการแรกสูงสุด → รายการแรกอยู่บนสุด -->
        <PopupDialog
            v-for="(popup, i) in visible"
            :key="popup.id"
            :ref="(el) => setDialog(popup.id, el)"
            :popup="popup"
            :active="i === 0"
            :z-index="BASE_Z + visible.length - i"
            class="print:hidden"
            @close="close(popup)"
            @dismiss-today="dismissToday(popup)"
        />
    </Teleport>
</template>
