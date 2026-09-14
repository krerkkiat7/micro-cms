<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import { AlertCircle, CheckCircle2, UploadCloud, X } from 'lucide-vue-next';
import { formatFileSize } from '@/utils/formatFileSize';

// ต้องตรงกับ config('filemanagement.allowed') ฝั่ง backend — backend เป็นตัวตัดสินสุดท้ายเสมอ
// (รายการนี้ใช้แค่กรองตัวเลือกไฟล์ในกล่อง browse + เช็กเบื้องต้นฝั่ง client เพื่อ feedback ที่เร็วขึ้น)
const ALL_EXTENSIONS = [
    'jpg', 'jpeg', 'png', 'gif', 'webp',
    'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'mp3', 'mp4',
];
const MAX_SIZE_BYTES = 5 * 1024 * 1024;

/** เวลาที่ค้างแถวไว้หลังอัพโหลดสำเร็จ ก่อนจะ fade หายไปเอง (มิลลิวินาที) */
const SUCCESS_DISMISS_DELAY = 5000;

const props = withDefaults(
    defineProps<{
        folderId: number | null;
        /** จำกัดนามสกุลที่อัพโหลดได้ (เช่น dialog เลือกไฟล์ที่ระบุ accept เป็นรูปภาพ) — ไม่ระบุ = อัพโหลดได้ทุกนามสกุลที่ระบบรองรับ */
        accept?: string[];
    }>(),
    {
        accept: undefined,
    },
);

const allowedExtensions = computed(() => (props.accept?.length ? props.accept : ALL_EXTENSIONS));

const emit = defineEmits<{
    uploaded: [];
}>();

interface UploadJob {
    id: string;
    fileName: string;
    fileSize: number;
    progress: number;
    status: 'uploading' | 'success' | 'error';
    error?: string;
}

const jobs = ref<UploadJob[]>([]);
const isDragging = ref(false);
const inputEl = ref<HTMLInputElement | null>(null);

function extensionOf(name: string): string {
    const parts = name.split('.');

    return parts.length > 1 ? parts.pop()!.toLowerCase() : '';
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    if (event.dataTransfer?.files) {
        addFiles(event.dataTransfer.files);
    }
}

function onPick(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        addFiles(target.files);
    }
    target.value = ''; // เลือกไฟล์ชื่อเดิมซ้ำได้อีกครั้ง
}

async function addFiles(fileList: FileList) {
    const files = Array.from(fileList);
    const results = await Promise.all(files.map(uploadOne));

    if (results.some(Boolean)) {
        emit('uploaded');
    }
}

/** อัพโหลดไฟล์เดียว — คืน true ถ้าสำเร็จ (ใช้ตัดสินใจว่าต้อง reload รายการไฟล์หรือไม่) */
async function uploadOne(file: File): Promise<boolean> {
    const jobId = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    // ต้องเป็น reactive() ก่อน push ลง array — ถ้า push object ธรรมดาแล้วมาแก้ property ทีหลัง
    // (job.status = 'success' ใน callback async) การแก้จะไปแก้ที่ raw object ไม่ผ่าน proxy ของ array
    // ทำให้ Vue ไม่รู้ว่าต้อง re-render (เห็นเป็นไอคอนหมุนค้างทั้งที่ response กลับมาแล้ว)
    const job = reactive<UploadJob>({
        id: jobId,
        fileName: file.name,
        fileSize: file.size,
        progress: 0,
        status: 'uploading',
    });
    jobs.value.push(job);

    const ext = extensionOf(file.name);
    if (!allowedExtensions.value.includes(ext)) {
        job.status = 'error';
        job.error = 'ไม่รองรับไฟล์นามสกุลนี้';

        return false;
    }
    if (file.size > MAX_SIZE_BYTES) {
        job.status = 'error';
        job.error = 'ขนาดไฟล์เกิน 5 MB';

        return false;
    }

    const form = new FormData();
    form.append('file', file);
    if (props.folderId !== null) {
        form.append('folder_id', String(props.folderId));
    }

    try {
        await axios.post(route('admin.system.file.upload'), form, {
            onUploadProgress: (evt) => {
                if (evt.total) {
                    job.progress = Math.round((evt.loaded / evt.total) * 100);
                }
            },
        });
        job.status = 'success';
        job.progress = 100;
        setTimeout(() => dismiss(job.id), SUCCESS_DISMISS_DELAY);

        return true;
    } catch (e: unknown) {
        const response = (e as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }).response;
        job.status = 'error';
        job.error = response?.data?.errors?.file?.[0] ?? response?.data?.message ?? 'อัพโหลดไม่สำเร็จ';

        return false;
    }
}

function dismiss(jobId: string) {
    jobs.value = jobs.value.filter((j) => j.id !== jobId);
}

/**
 * ยุบแถวลง (fade + พับความสูง) ก่อนลบออกจริง — ตั้งความสูงปัจจุบันเป็นค่าคงที่ก่อน แล้วค่อย transition
 * ไปที่ 0 เพราะ CSS transition ปกติทำกับ height: auto ไม่ได้ (ใช้ :css="false" ให้ควบคุมเองเต็มที่)
 */
function onLeave(el: Element, done: () => void) {
    const element = el as HTMLElement;
    const height = element.offsetHeight;

    element.style.height = `${height}px`;
    element.style.overflow = 'hidden';
    void element.offsetHeight; // บังคับ reflow ก่อนเปลี่ยนค่าเป้าหมาย ไม่งั้น transition จะไม่เล่น

    element.style.transition = 'height 300ms ease, opacity 250ms ease, margin 300ms ease';
    element.style.opacity = '0';
    element.style.height = '0px';
    element.style.marginTop = '0px';
    element.style.marginBottom = '0px';

    element.addEventListener('transitionend', function handler() {
        element.removeEventListener('transitionend', handler);
        done();
    });
}
</script>

<template>
    <div>
        <div
            class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-8 text-center transition-colors"
            :class="isDragging ? 'border-brand-500 bg-brand-50' : 'border-gray-300 bg-gray-50'"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <UploadCloud class="size-8 text-gray-400" />
            <p class="mt-2 text-sm text-gray-600">
                ลากไฟล์มาวางที่นี่ หรือ
                <button
                    type="button"
                    class="font-medium text-brand-600 hover:underline"
                    @click="inputEl?.click()"
                >
                    เลือกไฟล์
                </button>
            </p>
            <p class="mt-1 text-xs text-gray-400">
                รองรับ {{ allowedExtensions.join(', ') }} — ไม่เกิน 5 MB ต่อไฟล์ (เลือกได้หลายไฟล์พร้อมกัน)
            </p>
            <input
                ref="inputEl"
                type="file"
                multiple
                class="hidden"
                :accept="allowedExtensions.map((e) => '.' + e).join(',')"
                @change="onPick"
            />
        </div>

        <TransitionGroup
            v-if="jobs.length"
            tag="div"
            class="mt-3 space-y-2"
            move-class="transition-transform duration-300"
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            @leave="onLeave"
        >
            <div
                v-for="job in jobs"
                :key="job.id"
                class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"
            >
                <CheckCircle2 v-if="job.status === 'success'" class="size-4 shrink-0 text-green-500" />
                <AlertCircle v-else-if="job.status === 'error'" class="size-4 shrink-0 text-red-500" />
                <div v-else class="size-4 shrink-0 animate-spin rounded-full border-2 border-gray-300 border-t-brand-500" />

                <div class="min-w-0 flex-1">
                    <p class="truncate text-gray-700">{{ job.fileName }} <span class="text-gray-400">({{ formatFileSize(job.fileSize) }})</span></p>
                    <div v-if="job.status === 'uploading'" class="mt-1 h-1.5 w-full rounded-full bg-gray-100">
                        <div class="h-1.5 rounded-full bg-brand-500 transition-all" :style="{ width: job.progress + '%' }" />
                    </div>
                    <p v-else-if="job.status === 'error'" class="mt-0.5 text-xs text-red-600">{{ job.error }}</p>
                </div>

                <button
                    v-if="job.status !== 'uploading'"
                    type="button"
                    class="shrink-0 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    title="ปิด"
                    @click="dismiss(job.id)"
                >
                    <X class="size-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
