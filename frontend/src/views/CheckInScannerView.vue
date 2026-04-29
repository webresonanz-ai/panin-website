<template>
  <div class="page-shell scanner-page">
    <section class="page-hero animate-fade-in scanner-hero">
      <div class="row g-4 align-items-center">
        <div class="col-xl-7">
          <div class="eyebrow">Arrival Studio</div>
          <h1 class="hero-title">Scan the guest QR code and clear arrival in one smooth motion.</h1>
          <p class="hero-subtitle">
            Open the camera, frame the invitation QR, and let the desk surface confirm check-in with
            immediate guest context.
          </p>

          <div class="scanner-hero__actions">
            <button
              type="button"
              class="btn luxury-btn"
              :disabled="cameraStarting || scanningActive"
              @click="startCamera"
            >
              <i class="bi bi-camera-video-fill"></i>
              {{
                scanningActive
                  ? "Camera Live"
                  : cameraStarting
                    ? "Starting Camera"
                    : "Start Scanner"
              }}
            </button>
            <button
              type="button"
              class="btn luxury-btn-ghost"
              :disabled="!scanningActive"
              @click="stopCamera"
            >
              <i class="bi bi-stop-circle"></i>
              Pause Scanner
            </button>
          </div>
        </div>

        <div class="col-xl-5">
          <div class="metric-chip scanner-metric">
            <small>Arrival Throughput</small>
            <h3>{{ guestStore.stats.checkedInGuests }}/{{ guestStore.totalGuests }}</h3>
            <p>guests have already been admitted through the desk check-in flow.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="scanner-layout mt-4">
      <div class="page-grid">
        <article class="luxury-card scanner-stage animate-fade-in animate-delayed">
          <div class="scanner-stage__header">
            <div>
              <h3 class="panel-title">Live Camera</h3>
              <p class="panel-subtitle">
                {{
                  detectorSupported
                    ? "Native QR scanning is available on this device."
                    : "Camera preview is available, but this browser needs manual code entry."
                }}
              </p>
            </div>
            <span
              :class="
                scanningActive
                  ? 'status-pill status-pill-active'
                  : 'status-pill status-pill-pending'
              "
            >
              <span class="timeline-dot"></span>
              {{ scanningActive ? "Scanning" : "Idle" }}
            </span>
          </div>

          <div class="scanner-stage__viewport">
            <video ref="videoRef" class="scanner-video" autoplay muted playsinline></video>
            <div class="scanner-frame">
              <span></span>
              <span></span>
              <span></span>
              <span></span>
            </div>
            <div class="scanner-line"></div>
            <div v-if="!scanningActive" class="scanner-placeholder">
              <div class="scanner-placeholder__icon">
                <i class="bi bi-qr-code-scan"></i>
              </div>
              <h3>Scanner standing by</h3>
              <p>Allow camera access, then place the QR code inside the frame.</p>
            </div>
          </div>

          <div class="scanner-stage__footer">
            <div class="subtle-note">
              {{ statusMessage }}
            </div>
            <div v-if="lastScanLabel" class="scanner-last">
              <span class="text-luxury-faint">Latest scan</span>
              <strong>{{ lastScanLabel }}</strong>
            </div>
          </div>
        </article>

        <article class="luxury-card p-4 p-lg-5 animate-fade-in animate-delayed-2">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
              <h3 class="panel-title">Manual Check-In Fallback</h3>
              <p class="panel-subtitle">
                Paste the QR payload or type the registration number when a browser blocks native
                scanning.
              </p>
            </div>
            <span class="badge-luxury">
              <i class="bi bi-keyboard"></i>
              Assisted
            </span>
          </div>

          <form class="page-grid" @submit.prevent="submitManualCheckIn">
            <label class="text-luxury-soft fw-semibold" for="qr-code-input"
              >QR payload or registration number</label
            >
            <div class="input-group">
              <span class="input-group-text border-accent">
                <i class="bi bi-upc-scan"></i>
              </span>
              <input
                id="qr-code-input"
                v-model.trim="manualCode"
                type="text"
                class="form-control luxury-input"
                placeholder="Example: PANIN_12_1714381200_A4F1"
                :disabled="submitting"
              />
            </div>
            <button
              type="submit"
              class="btn luxury-btn align-self-start"
              :disabled="!manualCode || submitting"
            >
              <i class="bi bi-check2-circle"></i>
              {{ submitting ? "Processing" : "Check In Guest" }}
            </button>
          </form>
        </article>
      </div>

      <aside class="page-grid">
        <section
          class="luxury-card p-4 animate-fade-in animate-delayed-2 result-card"
          :class="resultToneClass"
        >
          <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
              <h3 class="panel-title">Check-In Result</h3>
              <p class="panel-subtitle">A live confirmation card appears here after each scan.</p>
            </div>
            <span v-if="resultBadge" class="status-pill" :class="resultBadge.className">
              <span class="timeline-dot"></span>
              {{ resultBadge.label }}
            </span>
          </div>

          <div v-if="result" class="page-grid">
            <article class="info-row">
              <div class="guest-avatar-small">{{ result.guest.fullName.charAt(0) }}</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white">{{ result.guest.fullName }}</div>
                <div class="text-luxury-faint small">
                  {{ result.guest.company || "Independent Guest" }}
                </div>
              </div>
            </article>

            <article class="info-row">
              <div>
                <div class="text-luxury-faint small text-uppercase">Registration Number</div>
                <div class="fw-bold text-white mt-1">{{ result.registrationNumber }}</div>
              </div>
            </article>

            <article class="info-row">
              <div>
                <div class="text-luxury-faint small text-uppercase">Arrival State</div>
                <div class="fw-bold text-white mt-1">
                  {{
                    result.status === "checked_in"
                      ? "Guest admitted successfully"
                      : "Guest was already admitted"
                  }}
                </div>
                <div class="text-luxury-soft small">
                  {{
                    result.guest.checkedInAt
                      ? formatDateTime(result.guest.checkedInAt)
                      : "Awaiting timestamp"
                  }}
                </div>
              </div>
            </article>

            <article class="info-row">
              <div>
                <div class="text-luxury-faint small text-uppercase">Stay Window</div>
                <div class="fw-bold text-white mt-1">
                  {{ formatDate(result.guest.checkIn) }} to {{ formatDate(result.guest.checkOut) }}
                </div>
                <div class="text-luxury-soft small">
                  {{ result.guest.seatNumber || "Seat assignment pending" }}
                </div>
              </div>
            </article>
          </div>

          <div v-else class="scanner-empty">
            <div class="scanner-placeholder__icon mx-auto mb-3">
              <i class="bi bi-person-badge"></i>
            </div>
            <h4 class="section-title fs-2 mb-2">No scan yet</h4>
            <p class="section-copy">
              The guest confirmation panel will populate as soon as the first QR code is processed.
            </p>
          </div>
        </section>

        <section class="luxury-card p-4 animate-fade-in animate-delayed-3">
          <h3 class="panel-title mb-2">Desk Notes</h3>
          <p class="panel-subtitle mb-4">
            A smoother scan flow tends to come from a few practical guardrails.
          </p>

          <div class="info-list">
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Use the rear camera</div>
                <div class="text-luxury-faint small">
                  It usually locks onto printed QR codes faster than the selfie camera.
                </div>
              </div>
            </article>
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Keep codes within the frame</div>
                <div class="text-luxury-faint small">
                  The detector reads best when the full QR code sits inside the guide corners.
                </div>
              </div>
            </article>
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Fallback stays available</div>
                <div class="text-luxury-faint small">
                  If native scanning is unsupported, staff can still paste the code and continue
                  service.
                </div>
              </div>
            </article>
          </div>
        </section>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useGuestStore } from "@/stores/guestStore";

const guestStore = useGuestStore();
const videoRef = ref(null);
const manualCode = ref("");
const submitting = ref(false);
const scanningActive = ref(false);
const cameraStarting = ref(false);
const result = ref(null);
const statusMessage = ref("Start the scanner when the guest reaches the desk.");
const lastScanLabel = ref("");

const detectorSupported = typeof window !== "undefined" && "BarcodeDetector" in window;
let mediaStream = null;
let frameHandle = 0;
let detector = null;
let scanLocked = false;

const resultBadge = computed(() => {
  if (!result.value) {
    return null;
  }

  if (result.value.status === "checked_in") {
    return { label: "Confirmed", className: "status-pill-active" };
  }

  return { label: "Already In", className: "status-pill-pending" };
});

const resultToneClass = computed(() => {
  if (!result.value) {
    return "";
  }

  return result.value.status === "checked_in" ? "result-card--success" : "result-card--warning";
});

const formatDate = (date) =>
  new Date(date).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });

const formatDateTime = (date) =>
  new Date(date).toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });

async function startCamera() {
  if (cameraStarting.value || scanningActive.value) {
    return;
  }

  cameraStarting.value = true;
  statusMessage.value = "Requesting camera access...";

  try {
    if (!navigator.mediaDevices?.getUserMedia) {
      throw new Error("Camera APIs are unavailable.");
    }

    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: "user" },
      },
      audio: false,
    });

    if (!videoRef.value) {
      return;
    }

    videoRef.value.srcObject = mediaStream;
    await videoRef.value.play();

    scanningActive.value = true;

    if (detectorSupported) {
      detector = detector || new window.BarcodeDetector({ formats: ["qr_code"] });
      statusMessage.value = "Scanner live. Hold the invitation QR steady inside the frame.";
      scheduleScan();
    } else {
      statusMessage.value =
        "Camera is live, but this browser needs manual code entry for QR processing.";
    }
  } catch {
    statusMessage.value = "Camera access was blocked. Use the manual check-in field below.";
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

  statusMessage.value =
    "Scanner paused. Restart the camera when you are ready for the next arrival.";
}

function scheduleScan() {
  cancelAnimationFrame(frameHandle);
  frameHandle = requestAnimationFrame(scanFrame);
}

async function scanFrame() {
  if (!scanningActive.value) {
    return;
  }

  if (!videoRef.value || !detector || scanLocked) {
    scheduleScan();
    return;
  }

  try {
    const barcodes = await detector.detect(videoRef.value);
    const code = barcodes[0]?.rawValue?.trim();

    if (code) {
      scanLocked = true;
      await processCheckIn(code);
      window.setTimeout(() => {
        scanLocked = false;
      }, 1800);
    }
  } catch {
    statusMessage.value = "QR detection is active, but no readable code is in frame yet.";
  } finally {
    if (scanningActive.value) {
      scheduleScan();
    }
  }
}

async function submitManualCheckIn() {
  if (!manualCode.value || submitting.value) {
    return;
  }

  await processCheckIn(manualCode.value);
}

async function processCheckIn(code) {
  submitting.value = true;
  lastScanLabel.value = code;
  statusMessage.value = "Processing guest check-in...";

  try {
    const payload = await guestStore.checkInGuest(code);
    result.value = payload;
    manualCode.value = "";
    statusMessage.value =
      payload.status === "checked_in"
        ? "Guest check-in completed successfully."
        : "This QR code was valid, but the guest had already been checked in.";
  } catch (error) {
    result.value = null;
    statusMessage.value = error.message || "The scanned code could not be processed.";
  } finally {
    submitting.value = false;
  }
}

onBeforeUnmount(() => {
  stopCamera();
});

onMounted(() => {
  guestStore.ensureLoaded().catch(() => {});
});
</script>

<style scoped>
.scanner-page {
  padding-bottom: 3rem;
}

.scanner-hero {
  overflow: visible;
}

.scanner-hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.9rem;
  margin-top: 1.75rem;
}

.scanner-metric {
  min-height: 100%;
}

.scanner-layout {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: minmax(0, 1.55fr) minmax(320px, 0.95fr);
}

.scanner-stage {
  overflow: hidden;
  padding: 1.4rem;
}

.scanner-stage__header,
.scanner-stage__footer {
  align-items: center;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.scanner-stage__header {
  margin-bottom: 1rem;
}

.scanner-stage__footer {
  margin-top: 1rem;
}

.scanner-stage__viewport {
  aspect-ratio: 16 / 10;
  background:
    radial-gradient(circle at top, rgba(104, 167, 255, 0.18), transparent 42%),
    linear-gradient(180deg, rgba(4, 14, 29, 0.96), rgba(5, 11, 22, 0.98));
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  overflow: hidden;
  position: relative;
}

.scanner-video,
.scanner-placeholder {
  height: 100%;
  inset: 0;
  position: absolute;
  width: 100%;
}

.scanner-video {
  object-fit: cover;
  transform: scaleX(-1);
}

.scanner-placeholder {
  align-items: center;
  backdrop-filter: blur(8px);
  background: rgba(4, 12, 25, 0.8);
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.scanner-placeholder h3 {
  color: var(--luxury-white);
  font-size: 1.2rem;
  font-weight: 800;
  margin: 0 0 0.45rem;
}

.scanner-placeholder p,
.scanner-empty p {
  color: rgba(246, 247, 251, 0.68);
  margin: 0;
}

.scanner-placeholder__icon {
  align-items: center;
  background: linear-gradient(135deg, rgba(104, 167, 255, 0.18), rgba(217, 72, 98, 0.18));
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 24px;
  color: var(--luxury-white);
  display: inline-flex;
  font-size: 2rem;
  height: 72px;
  justify-content: center;
  width: 72px;
}

.scanner-frame {
  inset: 14%;
  pointer-events: none;
  position: absolute;
}

.scanner-frame span {
  border-color: rgba(255, 255, 255, 0.82);
  border-style: solid;
  height: 46px;
  position: absolute;
  width: 46px;
}

.scanner-frame span:nth-child(1) {
  border-width: 4px 0 0 4px;
  border-top-left-radius: 18px;
  left: 0;
  top: 0;
}

.scanner-frame span:nth-child(2) {
  border-width: 4px 4px 0 0;
  border-top-right-radius: 18px;
  right: 0;
  top: 0;
}

.scanner-frame span:nth-child(3) {
  border-width: 0 0 4px 4px;
  border-bottom-left-radius: 18px;
  bottom: 0;
  left: 0;
}

.scanner-frame span:nth-child(4) {
  border-width: 0 4px 4px 0;
  border-bottom-right-radius: 18px;
  bottom: 0;
  right: 0;
}

.scanner-line {
  animation: scanPulse 2.4s linear infinite;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.95), transparent);
  height: 2px;
  inset-inline: 18%;
  position: absolute;
  top: 22%;
}

.scanner-last {
  display: grid;
  gap: 0.1rem;
  text-align: right;
}

.scanner-empty {
  padding: 1rem 0;
  text-align: center;
}

.result-card {
  min-height: 430px;
}

.result-card--success {
  border-color: rgba(72, 203, 137, 0.22);
}

.result-card--warning {
  border-color: rgba(217, 72, 98, 0.24);
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

@media (max-width: 991.98px) {
  .scanner-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .scanner-stage__header,
  .scanner-stage__footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .scanner-stage__viewport {
    aspect-ratio: 4 / 5;
  }
}
</style>
