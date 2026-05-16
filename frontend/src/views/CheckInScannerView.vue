<template>
  <div class="scanner-page">
    <header class="scanner-header">
      <h1>QR Check-In Scanner</h1>
      <p class="scanner-subtitle">Position the guest QR code within the frame to check them in</p>
    </header>

    <main class="scanner-main">
      <div class="luxury-card scanner-stage">
        <div class="scanner-viewport-wrapper">
          <video ref="videoRef" class="scanner-video" autoplay muted playsinline></video>
          <div class="scanner-frame" :class="{ 'scanner-frame--success': scanSuccess }"></div>
          <div class="scanner-line" :class="{ 'scanner-line--success': scanSuccess }"></div>
          <div v-if="!scanningActive" class="scanner-placeholder">
            <i class="bi bi-qr-code-scan scanner-placeholder-icon"></i>
            <p class="scanner-placeholder-text">
              {{ cameraStarting ? "Starting scanner..." : "Camera paused." }}
            </p>
          </div>
        </div>

        <div class="scanner-controls">
          <button
            type="button"
            class="btn luxury-btn"
            :disabled="cameraStarting || scanningActive"
            @click="startCamera"
          >
            <i class="bi bi-camera-video-fill"></i>
            {{ scanningActive ? "Camera Live" : cameraStarting ? "Starting..." : "Start Scanner" }}
          </button>
          <button
            type="button"
            class="btn luxury-btn-ghost"
            :disabled="!scanningActive"
            @click="stopCamera"
          >
            <i class="bi bi-stop-circle"></i>
            Stop
          </button>
        </div>

        <div class="scanner-status">{{ statusMessage }}</div>
      </div>
    </main>

    <transition name="success-popup">
      <div v-if="showResult && lastResult" class="scanner-success-popup" @click.self="closeResult">
        <div class="success-popup-card luxury-card">
          <div class="success-popup-glow"></div>
          <div class="success-popup-confetti" aria-hidden="true">
            <span
              v-for="piece in confettiPieces"
              :key="piece.id"
              class="confetti-piece"
              :style="{
                left: `${piece.left}%`,
                animationDelay: `${piece.delay}s`,
                animationDuration: `${piece.duration}s`,
                background: piece.color,
                transform: `rotate(${piece.rotate}deg)`,
              }"
            ></span>
          </div>

          <div class="success-popup-content">
            <div class="success-icon-wrapper">
              <i class="bi bi-check-circle-fill success-icon"></i>
            </div>

            <h2 class="success-title">Check-In Complete!</h2>
            <p class="success-subtitle">Guest successfully admitted</p>

            <div class="success-guest-info">
              <div class="guest-avatar">{{ lastResult.guest?.fullName?.charAt(0) || "?" }}</div>
              <div class="guest-details">
                <div class="guest-name">{{ lastResult.guest?.fullName || "Guest" }}</div>
                <div class="guest-company">
                  {{ lastResult.guest?.gaSoPosition || "Independent Guest" }}
                </div>
                <div class="guest-seat">
                  <span class="guest-seat-label">Seat</span>
                  <span class="guest-seat-value">
                    {{ lastResult.guest?.seatNumber || "Unassigned" }}
                  </span>
                </div>
              </div>
            </div>

            <div class="success-actions">
              <button type="button" class="btn luxury-btn" @click="closeResult">
                Continue Scanning ({{ resultCountdown }})
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import jsQR from "jsqr";
import { useGuestStore } from "@/stores/guestStore";

const confettiPieces = [
  { id: 1, left: 10, delay: 0, duration: 2.2, rotate: 15, color: "#4cf2ff" },
  { id: 2, left: 20, delay: 0.1, duration: 2.4, rotate: -20, color: "#1878ff" },
  { id: 3, left: 30, delay: 0.2, duration: 2.1, rotate: 25, color: "#ff8e2b" },
  { id: 4, left: 40, delay: 0.05, duration: 2.3, rotate: -10, color: "#ffffff" },
  { id: 5, left: 50, delay: 0.15, duration: 2.5, rotate: 30, color: "#ff4a63" },
  { id: 6, left: 60, delay: 0.25, duration: 2.0, rotate: -25, color: "#ff2b8f" },
  { id: 7, left: 70, delay: 0.08, duration: 2.6, rotate: 18, color: "#35d7ff" },
  { id: 8, left: 80, delay: 0.18, duration: 2.2, rotate: -15, color: "#ff8e2b" },
  { id: 9, left: 90, delay: 0.28, duration: 2.4, rotate: 22, color: "#ffffff" },
];

const guestStore = useGuestStore();
const videoRef = ref(null);
const scanningActive = ref(false);
const cameraStarting = ref(false);
const showResult = ref(false);
const scanSuccess = ref(false);
const lastResult = ref(null);
const resultCountdown = ref(5);
const statusMessage = ref("Starting scanner...");

let mediaStream = null;
let frameHandle = 0;
let detector = null;
let scanLocked = false;
let successCountdownTimer = 0;
let successAutoCloseTimer = 0;
let fallbackCanvas = null;
let fallbackContext = null;

const detectorSupported = typeof window !== "undefined" && "BarcodeDetector" in window;

async function startCamera() {
  if (cameraStarting.value || scanningActive.value) return;

  cameraStarting.value = true;
  statusMessage.value = "Requesting camera access...";

  try {
    if (!navigator.mediaDevices?.getUserMedia) throw new Error("Camera APIs unavailable");

    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: "user" } },
      audio: false,
    });

    if (!videoRef.value) return;

    videoRef.value.srcObject = mediaStream;
    await videoRef.value.play();
    scanningActive.value = true;

    if (detectorSupported) {
      detector = new window.BarcodeDetector({ formats: ["qr_code"] });
      statusMessage.value = "Scanner active. Hold QR code within the frame.";
    } else {
      detector = null;
      statusMessage.value = "Scanner active. Using browser-compatible QR detection.";
    }

    scheduleScan();
  } catch {
    statusMessage.value = "Camera access denied. Please allow camera permissions.";
  } finally {
    cameraStarting.value = false;
  }
}

function stopCamera() {
  cancelAnimationFrame(frameHandle);
  frameHandle = 0;
  scanningActive.value = false;

  if (videoRef.value) {
    videoRef.value.pause();
    videoRef.value.srcObject = null;
  }

  if (mediaStream) {
    mediaStream.getTracks().forEach((track) => track.stop());
    mediaStream = null;
  }

  statusMessage.value = "Scanner stopped. Click Start to resume.";
}

function pauseScanner() {
  cancelAnimationFrame(frameHandle);
  frameHandle = 0;
  scanningActive.value = false;
}

function clearResultTimers() {
  clearInterval(successCountdownTimer);
  clearTimeout(successAutoCloseTimer);
  successCountdownTimer = 0;
  successAutoCloseTimer = 0;
}

function startResultCountdown() {
  clearResultTimers();
  resultCountdown.value = 5;

  successCountdownTimer = window.setInterval(() => {
    if (resultCountdown.value > 1) {
      resultCountdown.value -= 1;
    }
  }, 1000);

  successAutoCloseTimer = window.setTimeout(() => {
    closeResult();
  }, 5000);
}

async function resumeScanner() {
  if (showResult.value) return;

  if (mediaStream && videoRef.value) {
    videoRef.value.srcObject = mediaStream;
    await videoRef.value.play();
    scanningActive.value = true;
    statusMessage.value = "Scanner active. Hold QR code within the frame.";
    if (detectorSupported) scheduleScan();
    return;
  }

  await startCamera();
}

function scheduleScan() {
  cancelAnimationFrame(frameHandle);
  frameHandle = requestAnimationFrame(scanFrame);
}

function ensureFallbackCanvas(width, height) {
  if (!fallbackCanvas) fallbackCanvas = document.createElement("canvas");
  if (!fallbackContext)
    fallbackContext = fallbackCanvas.getContext("2d", { willReadFrequently: true });
  if (!fallbackContext) return null;

  if (fallbackCanvas.width !== width) fallbackCanvas.width = width;
  if (fallbackCanvas.height !== height) fallbackCanvas.height = height;

  return fallbackContext;
}

async function scanFrame() {
  if (!scanningActive.value) return;
  if (!videoRef.value || scanLocked) {
    scheduleScan();
    return;
  }

  try {
    if (videoRef.value.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
      scheduleScan();
      return;
    }

    let code = "";

    if (detector) {
      const barcodes = await detector.detect(videoRef.value);
      code = barcodes[0]?.rawValue?.trim() || "";
    } else {
      const width = videoRef.value.videoWidth;
      const height = videoRef.value.videoHeight;
      const context = ensureFallbackCanvas(width, height);

      if (!context || !width || !height) {
        scheduleScan();
        return;
      }

      context.drawImage(videoRef.value, 0, 0, width, height);
      const imageData = context.getImageData(0, 0, width, height);
      const result = jsQR(imageData.data, imageData.width, imageData.height);
      code = result?.data?.trim() || "";
    }

    if (code) {
      scanLocked = true;
      await handleCheckIn(code);
      setTimeout(() => (scanLocked = false), 1800);
    }
  } catch {
    statusMessage.value = "Position QR code within the frame.";
  } finally {
    if (scanningActive.value) scheduleScan();
  }
}

async function handleCheckIn(code) {
  scanSuccess.value = true;
  setTimeout(() => (scanSuccess.value = false), 1200);
  pauseScanner();
  statusMessage.value = "Processing...";

  try {
    const payload = await guestStore.checkInGuest(code);
    lastResult.value = payload;
    showResult.value = true;
    statusMessage.value = "Guest check-in completed successfully.";
  } catch (error) {
    await resumeScanner();
    if (error.status === 401 || error.status === 403) {
      statusMessage.value = "Scanner is open, but check-in requires an admin or manager account.";
    } else {
      statusMessage.value = error.message || "Failed to process QR code.";
    }
  }
}

async function closeResult() {
  clearResultTimers();
  showResult.value = false;
  lastResult.value = null;
  resultCountdown.value = 5;
  await resumeScanner();
}

onMounted(() => {
  startCamera();
});

onUnmounted(() => {
  clearResultTimers();
  fallbackContext = null;
  fallbackCanvas = null;
  stopCamera();
});
</script>

<style scoped>
.scanner-page {
  min-height: 100vh;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.scanner-header {
  text-align: center;
  margin-bottom: 2rem;
}

.scanner-header h1 {
  color: var(--luxury-white);
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
}

.scanner-subtitle {
  color: rgba(246, 247, 251, 0.68);
  font-size: 1rem;
}

.scanner-main {
  width: 100%;
  max-width: 580px;
}

.scanner-stage {
  padding: 1.5rem;
  overflow: hidden;
}

.scanner-viewport-wrapper {
  aspect-ratio: 1 / 1;
  background:
    radial-gradient(circle at 50% 100%, rgba(255, 255, 255, 0.16), transparent 28%),
    radial-gradient(circle at 20% 18%, rgba(255, 74, 99, 0.24), transparent 34%),
    radial-gradient(circle at 80% 18%, rgba(57, 214, 255, 0.22), transparent 34%),
    linear-gradient(180deg, rgba(4, 12, 30, 0.96), rgba(5, 10, 24, 0.98));
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  overflow: hidden;
  position: relative;
  margin-bottom: 1.5rem;
}

.scanner-video {
  height: 100%;
  inset: 0;
  position: absolute;
  width: 100%;
  object-fit: cover;
  transform: scaleX(-1);
}

.scanner-frame {
  border-radius: 28px;
  inset: 14%;
  pointer-events: none;
  position: absolute;
  transition:
    box-shadow 0.25s ease,
    transform 0.25s ease;
}

.scanner-frame::before {
  content: "";
  position: absolute;
  inset: 0;
  border: 4px solid rgba(255, 255, 255, 0.82);
  border-radius: 28px;
}

.scanner-frame--success {
  box-shadow:
    0 0 0 1px rgba(76, 242, 255, 0.45),
    0 0 18px rgba(76, 242, 255, 0.32);
  transform: scale(1.01);
}

.scanner-line {
  animation: scanPulse 2.4s linear infinite;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.95), transparent);
  height: 2px;
  inset-inline: 18%;
  position: absolute;
  top: 22%;
  transition:
    background 0.25s ease,
    box-shadow 0.25s ease,
    height 0.25s ease;
}

.scanner-line--success {
  background: linear-gradient(90deg, transparent, rgba(76, 242, 255, 0.94), transparent);
  box-shadow: 0 0 24px rgba(76, 242, 255, 0.85);
  height: 4px;
}

.scanner-placeholder {
  align-items: center;
  backdrop-filter: blur(8px);
  background: rgba(4, 12, 25, 0.8);
  display: flex;
  flex-direction: column;
  justify-content: center;
  inset: 0;
  position: absolute;
  text-align: center;
}

.scanner-placeholder-icon {
  font-size: 3rem;
  color: var(--luxury-white);
  margin-bottom: 1rem;
  opacity: 0.6;
}

.scanner-placeholder-text {
  color: rgba(246, 247, 251, 0.68);
  margin: 0;
}

.scanner-controls {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-bottom: 1rem;
}

.scanner-status {
  text-align: center;
  color: rgba(246, 247, 251, 0.68);
  font-size: 0.9rem;
}

.scanner-success-popup {
  align-items: center;
  backdrop-filter: blur(12px);
  background: rgba(3, 7, 19, 0.78);
  display: flex;
  inset: 0;
  justify-content: center;
  min-height: 100dvh;
  padding: 0;
  position: fixed;
  z-index: 1200;
}

.success-popup-card {
  align-items: center;
  display: flex;
  justify-content: center;
  max-width: none;
  min-height: 100dvh;
  padding: 2.5rem 1.5rem;
  width: 100%;
  position: relative;
  overflow: hidden;
  text-align: center;
}

.success-popup-glow {
  background:
    radial-gradient(circle at top left, rgba(255, 74, 99, 0.18), transparent 34%),
    radial-gradient(circle at top right, rgba(57, 214, 255, 0.22), transparent 36%),
    radial-gradient(circle at bottom center, rgba(255, 255, 255, 0.12), transparent 30%);
  inset: 0;
  pointer-events: none;
  position: absolute;
}

.success-popup-confetti {
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  position: absolute;
}

.confetti-piece {
  animation-name: confettiFall;
  animation-iteration-count: 1;
  animation-timing-function: cubic-bezier(0.18, 0.8, 0.3, 1);
  border-radius: 999px;
  box-shadow: 0 0 16px rgba(255, 255, 255, 0.18);
  height: 16px;
  opacity: 0;
  position: absolute;
  top: -10%;
  width: 10px;
}

.success-popup-content {
  max-width: 100vw;
  position: relative;
  width: 100%;
  z-index: 1;
}

.success-icon-wrapper {
  margin-bottom: 1.5rem;
}

.success-icon {
  font-size: 4rem;
  color: #fcd069;
  filter: drop-shadow(0 0 20px rgba(76, 242, 255, 0.5));
  animation: iconPop 0.6s ease-out;
}

@keyframes iconPop {
  0% {
    opacity: 0;
    transform: scale(0.5);
  }
  70% {
    transform: scale(1.1);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.success-title {
  color: var(--luxury-white);
  font-size: 1.75rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
}

.success-subtitle {
  color: #f0f0ec;
  font-size: 1rem;
  margin-bottom: 1.5rem;
}

.success-guest-info {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.guest-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(255, 74, 99, 0.24), rgba(57, 214, 255, 0.28));
  display: none;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--luxury-white);
}

.guest-details {
  text-align: center;
}

.guest-name {
  color: #fcd069;
  font-weight: 600;
  font-size: 3rem;
  font-family: "Cinzel", "Times New Roman", serif;
}

.guest-company {
  color: #fcd069;
  font-size: 2.2rem;
  font-weight: 600;
}

.guest-seat {
  align-items: center;
  display: flex;
  flex-direction: column;
  margin-top: 1.2rem;
}

.guest-seat-label {
  color: #f0f0ec;
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.guest-seat-value {
  color: #f0f0ec;
  font-size: 12rem;
  font-weight: 300;
  letter-spacing: 0.06em;
  line-height: 1;
  margin-top: -0.75rem;
  text-shadow: 0 0 18px rgba(76, 242, 255, 0.28);
}

.success-actions {
  margin-top: 1rem;
}

.success-popup-enter-active,
.success-popup-leave-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.success-popup-enter-from,
.success-popup-leave-to {
  opacity: 0;
}

.success-popup-enter-from .success-popup-card,
.success-popup-leave-to .success-popup-card {
  opacity: 0;
  transform: translateY(24px) scale(0.95);
}

@media (max-width: 767.98px) {
  .scanner-popup {
    align-items: flex-end;
    padding: 1rem;
  }

  .scanner-popup__card {
    max-width: none;
    width: 100%;
  }
}

@media (max-width: 1024px) and (orientation: portrait) {
  .scanner-page {
    justify-content: center;
    padding-block: 1.5rem;
  }

  .scanner-header {
    margin-bottom: 1.25rem;
  }

  .scanner-header h1 {
    font-size: clamp(1.8rem, 4vw, 2.3rem);
  }

  .scanner-main {
    max-width: min(580px, 100%);
  }
}

@keyframes scanPulse {
  0% {
    opacity: 0.2;
    top: 22%;
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0.2;
    top: 78%;
  }
}

@keyframes confettiFall {
  0% {
    opacity: 0;
    top: -12%;
    transform: translate3d(0, 0, 0) scale(0.8) rotate(0deg);
  }
  10% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    top: 112%;
    transform: translate3d(24px, 0, 0) scale(1) rotate(320deg);
  }
}
</style>
