<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import { Check, LocateFixed, X } from 'lucide-vue-next';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import type { Map as LeafletMap, Marker } from 'leaflet';

/**
 * dialog เลือกพิกัด (latitude / longitude) จากแผนที่ — ใช้ Leaflet + แผนที่ OpenStreetMap (ไม่ต้องใช้ API key)
 * คลิกบนแผนที่ = วางหมุด, ลากหมุดเพื่อปรับตำแหน่งได้; กด "ใช้พิกัดนี้" ค่อยส่งค่ากลับ (ยกเลิก = ไม่เปลี่ยนค่าเดิม)
 * โหลด leaflet แบบ dynamic import ตอนเปิด dialog ครั้งแรกเท่านั้น (ไม่เพิ่มขนาด bundle ของหน้าตั้งค่า)
 */
const props = defineProps<{
    show: boolean;
    latitude: string;
    longitude: string;
}>();

const emit = defineEmits<{
    close: [];
    select: [value: { latitude: string; longitude: string }];
}>();

// จุดเริ่มต้นเมื่อยังไม่มีพิกัด — กรุงเทพฯ
const DEFAULT_CENTER: [number, number] = [13.7563, 100.5018];

const container = ref<HTMLElement | null>(null);
const map = shallowRef<LeafletMap | null>(null);
const marker = shallowRef<Marker | null>(null);
const picked = ref<{ lat: number; lng: number } | null>(null);
const loading = ref(false);
const locating = ref(false);

function round(value: number): string {
    return value.toFixed(6);
}

function currentValue(): [number, number] | null {
    const lat = Number.parseFloat(props.latitude);
    const lng = Number.parseFloat(props.longitude);

    return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 ? [lat, lng] : null;
}

async function open() {
    loading.value = true;
    const L = (await import('leaflet')).default;
    await import('leaflet/dist/leaflet.css');
    await nextTick();

    if (!container.value) {
        loading.value = false;
        return;
    }

    const start = currentValue();
    picked.value = start ? { lat: start[0], lng: start[1] } : null;

    const instance = L.map(container.value).setView(start ?? DEFAULT_CENTER, start ? 16 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    }).addTo(instance);

    // หมุดเป็น SVG ในตัว (ไอคอนรูปภาพตั้งต้นของ leaflet หา path ไม่เจอเมื่อผ่าน bundler)
    const icon = L.divIcon({
        className: '',
        html: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 36" width="30" height="45"><path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 24 12 24s12-15 12-24C24 5.4 18.6 0 12 0z" fill="#dc2626"/><circle cx="12" cy="12" r="5" fill="#fff"/></svg>',
        iconSize: [30, 45],
        iconAnchor: [15, 45],
    });

    const place = (lat: number, lng: number) => {
        picked.value = { lat, lng };

        if (marker.value) {
            marker.value.setLatLng([lat, lng]);
            return;
        }

        marker.value = L.marker([lat, lng], { icon, draggable: true }).addTo(instance);
        marker.value.on('dragend', () => {
            const position = marker.value!.getLatLng();
            picked.value = { lat: position.lat, lng: position.lng };
        });
    };

    if (start) {
        place(start[0], start[1]);
    }

    instance.on('click', (e) => place(e.latlng.lat, e.latlng.lng));
    map.value = instance;
    loading.value = false;

    // dialog เพิ่ง render — ให้ leaflet คำนวณขนาดใหม่หลัง transition
    setTimeout(() => instance.invalidateSize(), 250);
}

function destroy() {
    map.value?.remove();
    map.value = null;
    marker.value = null;
}

function locate() {
    if (!navigator.geolocation || !map.value) {
        return;
    }

    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            locating.value = false;
            map.value?.setView([position.coords.latitude, position.coords.longitude], 17);
            map.value?.fire('click', { latlng: { lat: position.coords.latitude, lng: position.coords.longitude } });
        },
        () => {
            locating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
}

function confirm() {
    if (picked.value) {
        emit('select', { latitude: round(picked.value.lat), longitude: round(picked.value.lng) });
    }
}

watch(
    () => props.show,
    (show) => {
        if (show) {
            void open();
        } else {
            destroy();
        }
    },
);

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.show) {
        emit('close');
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    destroy();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-500/75" @click="emit('close')" />

                <div
                    class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-xl"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="map_picker_title"
                >
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3.5">
                        <h2 id="map_picker_title" class="text-base font-semibold text-gray-800">เลือกพิกัดจากแผนที่</h2>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                            aria-label="ปิด"
                            @click="emit('close')"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="space-y-3 overflow-y-auto px-5 py-4">
                        <p class="text-sm text-gray-500">คลิกบนแผนที่เพื่อวางหมุด หรือลากหมุดเพื่อปรับตำแหน่ง</p>

                        <div class="relative">
                            <div ref="container" class="h-[55vh] min-h-72 w-full rounded-lg border border-gray-200 bg-gray-100" />
                            <div v-if="loading" class="absolute inset-0 flex items-center justify-center text-sm text-gray-500">
                                กำลังโหลดแผนที่...
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                            <div class="text-gray-700">
                                <template v-if="picked">
                                    ละติจูด <span class="font-mono font-medium">{{ round(picked.lat) }}</span>, ลองจิจูด
                                    <span class="font-mono font-medium">{{ round(picked.lng) }}</span>
                                </template>
                                <span v-else class="text-gray-400">ยังไม่ได้เลือกตำแหน่ง</span>
                            </div>
                            <SecondaryButton type="button" :disabled="locating || loading" @click="locate">
                                <LocateFixed class="mr-1.5 size-4" /> ตำแหน่งปัจจุบันของฉัน
                            </SecondaryButton>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-3">
                        <PrimaryButton type="button" :disabled="!picked" @click="confirm">
                            <Check class="mr-1.5 size-4" /> ใช้พิกัดนี้
                        </PrimaryButton>
                        <SecondaryButton type="button" @click="emit('close')">ยกเลิก</SecondaryButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
