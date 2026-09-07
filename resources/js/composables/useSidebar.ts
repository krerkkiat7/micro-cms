import { inject, onBeforeUnmount, onMounted, provide, ref, type InjectionKey, type Ref } from 'vue';

/**
 * สถานะของ sidebar หลังบ้าน แชร์ผ่าน provide/inject
 * - isExpanded    : desktop — เต็ม (290px) หรือ ย่อ (90px), จำค่าไว้ใน localStorage
 * - isMobileOpen  : mobile  — เปิด/ปิด drawer
 */
export interface SidebarContext {
    isExpanded: Ref<boolean>;
    isMobileOpen: Ref<boolean>;
    toggleSidebar: () => void;
    toggleMobileSidebar: () => void;
    closeMobileSidebar: () => void;
}

const SidebarKey: InjectionKey<SidebarContext> = Symbol('admin-sidebar');
const STORAGE_KEY = 'admin.sidebar.expanded';
const DESKTOP_BREAKPOINT = 1024; // Tailwind `lg`

function readStored(): boolean {
    try {
        const v = window.localStorage.getItem(STORAGE_KEY);
        return v === null ? true : v === '1';
    } catch {
        return true;
    }
}

export function provideSidebar(): SidebarContext {
    const isExpanded = ref(readStored());
    const isMobileOpen = ref(false);

    const persist = () => {
        try {
            window.localStorage.setItem(STORAGE_KEY, isExpanded.value ? '1' : '0');
        } catch {
            /* โหมด private / ปิด storage — ข้ามได้ */
        }
    };

    const toggleSidebar = () => {
        isExpanded.value = !isExpanded.value;
        persist();
    };
    const toggleMobileSidebar = () => {
        isMobileOpen.value = !isMobileOpen.value;
    };
    const closeMobileSidebar = () => {
        isMobileOpen.value = false;
    };

    const onResize = () => {
        if (window.innerWidth >= DESKTOP_BREAKPOINT) {
            isMobileOpen.value = false;
        }
    };

    onMounted(() => window.addEventListener('resize', onResize));
    onBeforeUnmount(() => window.removeEventListener('resize', onResize));

    const ctx: SidebarContext = {
        isExpanded,
        isMobileOpen,
        toggleSidebar,
        toggleMobileSidebar,
        closeMobileSidebar,
    };
    provide(SidebarKey, ctx);
    return ctx;
}

export function useSidebar(): SidebarContext {
    const ctx = inject(SidebarKey);
    if (!ctx) {
        throw new Error('useSidebar() ต้องอยู่ภายใต้ AdminLayout ที่เรียก provideSidebar()');
    }
    return ctx;
}
