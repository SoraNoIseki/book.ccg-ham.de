<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

// Full-screen camera scanner (zxing). Emits `scan` once with the decoded text, then the parent
// closes it. Book codes are EAN-13 (ISBN) or Code 128 (copy labels), so only those formats
// are tried, which is faster and gives fewer misreads than "all formats".
const emit = defineEmits<{ (e: 'scan', code: string): void; (e: 'close'): void }>();

const props = defineProps<{ hint?: string }>();

const videoRef = ref<HTMLVideoElement | null>(null);
const error = ref<string | null>(null);
const starting = ref(true);
const manual = ref('');
const torchSupported = ref(false);
const torchOn = ref(false);

const hints = new Map<DecodeHintType, unknown>();
hints.set(DecodeHintType.POSSIBLE_FORMATS, [
    BarcodeFormat.EAN_13, BarcodeFormat.EAN_8, BarcodeFormat.UPC_A, BarcodeFormat.CODE_128,
    BarcodeFormat.CODE_39, BarcodeFormat.ITF,
]);
hints.set(DecodeHintType.TRY_HARDER, true);

const reader = new BrowserMultiFormatReader(hints, { delayBetweenScanAttempts: 80 });
let controls: { stop: () => void } | null = null;
// zxing keeps decoding until stop() takes effect, so one code can fire twice.
let handled = false;
let stream: MediaStream | null = null;
const previousOverflow = document.body.style.overflow;

async function start(): Promise<void> {
    error.value = null;
    starting.value = true;
    handled = false;

    // Cameras are only available on HTTPS (or localhost).
    if (!navigator.mediaDevices?.getUserMedia) {
        error.value = '这个页面不能使用摄像头（需要 HTTPS 连接）。可以在下面手动输入条码。';
        starting.value = false;
        return;
    }

    try {
        controls = await reader.decodeFromConstraints(
            // Rear camera; a higher resolution makes thin EAN-13 bars readable at arm's length.
            { video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } },
            videoRef.value!,
            (result) => {
                if (result && !handled) {
                    handled = true;
                    navigator.vibrate?.(60);
                    stop();
                    emit('scan', result.getText());
                }
            },
        );

        stream = (videoRef.value?.srcObject as MediaStream) ?? null;
        const caps = stream?.getVideoTracks()[0]?.getCapabilities?.() as (MediaTrackCapabilities & { torch?: boolean }) | undefined;
        torchSupported.value = !!caps?.torch;
    } catch (e: any) {
        error.value = e?.name === 'NotAllowedError'
            ? '没有摄像头权限。请在浏览器设置里允许使用摄像头，或在下面手动输入条码。'
            : '摄像头无法启动。可以在下面手动输入条码。';
    } finally {
        starting.value = false;
    }
}

async function toggleTorch(): Promise<void> {
    const track = stream?.getVideoTracks()[0];
    if (!track) return;
    try {
        await track.applyConstraints({ advanced: [{ torch: !torchOn.value } as any] });
        torchOn.value = !torchOn.value;
    } catch {
        torchSupported.value = false;
    }
}

function stop(): void {
    controls?.stop();
    controls = null;
    stream = null;
}

function submitManual(): void {
    const code = manual.value.trim();
    if (!code) return;
    stop();
    emit('scan', code);
}

function close(): void {
    stop();
    emit('close');
}

onMounted(() => {
    document.body.style.overflow = 'hidden';
    start();
});
onBeforeUnmount(() => {
    stop();
    document.body.style.overflow = previousOverflow;
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[60] bg-black text-white flex flex-col" role="dialog" aria-modal="true" aria-label="扫描条形码">
            <div class="relative flex-1 min-h-0 overflow-hidden">
                <video ref="videoRef" class="absolute inset-0 w-full h-full object-cover" muted playsinline></video>

                <!-- Viewfinder: purely visual, zxing reads the whole frame -->
                <div v-if="!error" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="relative w-[78%] max-w-sm aspect-[3/2] rounded-2xl shadow-[0_0_0_9999px_rgba(0,0,0,0.45)]">
                        <span class="absolute top-0 left-0 w-9 h-9 border-t-4 border-l-4 border-primary-300 rounded-tl-2xl"></span>
                        <span class="absolute top-0 right-0 w-9 h-9 border-t-4 border-r-4 border-primary-300 rounded-tr-2xl"></span>
                        <span class="absolute bottom-0 left-0 w-9 h-9 border-b-4 border-l-4 border-primary-300 rounded-bl-2xl"></span>
                        <span class="absolute bottom-0 right-0 w-9 h-9 border-b-4 border-r-4 border-primary-300 rounded-br-2xl"></span>
                        <span class="absolute left-3 right-3 top-1/2 h-0.5 bg-primary-300/80 animate-pulse"></span>
                    </div>
                </div>

                <div v-if="starting" class="absolute inset-0 flex items-center justify-center text-sm text-white/80">
                    正在打开摄像头...
                </div>
                <div v-if="error" class="absolute inset-0 flex items-center justify-center p-8 text-center text-sm text-white/90">
                    {{ error }}
                </div>

                <!-- Top bar -->
                <div class="absolute top-0 inset-x-0 flex items-center justify-between p-4 pt-[max(1rem,env(safe-area-inset-top))] bg-gradient-to-b from-black/70 to-transparent">
                    <button type="button" @click="close" aria-label="关闭"
                        class="w-11 h-11 flex items-center justify-center rounded-full bg-white/15 backdrop-blur active:scale-95 transition outline-none focus-visible:ring-2 focus-visible:ring-white">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                    <p class="text-sm font-medium">{{ props.hint || '把条形码放进框里' }}</p>
                    <button v-if="torchSupported" type="button" @click="toggleTorch" :aria-pressed="torchOn" aria-label="手电筒"
                        class="w-11 h-11 flex items-center justify-center rounded-full backdrop-blur active:scale-95 transition outline-none focus-visible:ring-2 focus-visible:ring-white"
                        :class="torchOn ? 'bg-primary-400 text-black' : 'bg-white/15'">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></svg>
                    </button>
                    <span v-else class="w-11" aria-hidden="true"></span>
                </div>
            </div>

            <!-- Manual entry: a fallback for damaged labels or no camera -->
            <form @submit.prevent="submitManual"
                class="flex gap-2 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] bg-black">
                <input v-model="manual" type="text" inputmode="text" autocomplete="off" autocapitalize="characters"
                    placeholder="或者手动输入条码 / ISBN / 编号"
                    class="flex-1 min-w-0 h-12 px-4 rounded-xl bg-white/10 border-0 text-white placeholder-white/50 focus:ring-2 focus:ring-primary-300" />
                <button type="submit" :disabled="!manual.trim()"
                    class="h-12 px-5 rounded-xl font-semibold text-black bg-primary-300 disabled:opacity-40 active:scale-95 transition">
                    查找
                </button>
            </form>
        </div>
    </Teleport>
</template>
