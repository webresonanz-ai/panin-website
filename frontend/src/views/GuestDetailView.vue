<template>
  <div class="page-shell">
    <div v-if="guest" class="page-grid">
      <section class="page-hero animate-fade-in">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
          <div>
            <div class="eyebrow">Guest Dossier</div>
            <h1 class="hero-title">{{ guest.fullName }}</h1>
            <p class="hero-subtitle">{{ guest.email }} | {{ guest.phone || "Phone not provided" }}</p>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button @click="$router.back()" class="btn luxury-btn-ghost">
              <i class="bi bi-arrow-left"></i>
              Back
            </button>
            <router-link
              v-if="authStore.isAuthenticated"
              :to="{ name: 'register', query: { edit: guest.id } }"
              class="btn luxury-btn"
            >
              <i class="bi bi-pencil-square"></i>
              Edit
            </router-link>
            <router-link
              v-else
              :to="{ name: 'login', query: { redirect: `/register?edit=${guest.id}` } }"
              class="btn luxury-btn"
            >
              <i class="bi bi-shield-lock"></i>
              Login to Edit
            </router-link>
            <button v-if="authStore.isAuthenticated" @click="handleDelete" class="btn luxury-btn luxury-btn-secondary">
              <i class="bi bi-trash3"></i>
              Delete
            </button>
          </div>
        </div>
      </section>

      <section class="dashboard-layout">
        <div class="luxury-card p-4 p-lg-5 animate-fade-in animate-delayed">
          <div class="row g-4 align-items-start">
            <div class="col-lg-4 text-center text-lg-start">
              <div class="guest-avatar-large mx-lg-0 mx-auto mb-4">
                {{ guest.fullName.charAt(0) }}
              </div>
              <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                <span :class="statusClass(guest.status)">
                  <span class="timeline-dot"></span>
                  {{ guest.status }}
                </span>
                <span v-if="guest.vipStatus" class="badge-luxury">
                  <i class="bi bi-stars"></i>
                  VIP Handling
                </span>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="row g-3">
                <div class="col-md-6">
                  <article class="info-row h-100 align-items-start">
                    <div>
                      <div class="text-luxury-faint small text-uppercase">Suite</div>
                      <div class="fw-bold text-white mt-1">{{ guest.suite }}</div>
                    </div>
                  </article>
                </div>
                <div class="col-md-6">
                  <article class="info-row h-100 align-items-start">
                    <div>
                      <div class="text-luxury-faint small text-uppercase">Stay Window</div>
                      <div class="fw-bold text-white mt-1">{{ formatDate(guest.checkIn) }}</div>
                      <div class="text-luxury-soft small">through {{ formatDate(guest.checkOut) }}</div>
                    </div>
                  </article>
                </div>
                <div class="col-md-6">
                  <article class="info-row h-100 align-items-start">
                    <div>
                      <div class="text-luxury-faint small text-uppercase">Guest Contact</div>
                      <div class="fw-bold text-white mt-1">{{ guest.email }}</div>
                      <div class="text-luxury-soft small">{{ guest.phone || "No phone listed" }}</div>
                    </div>
                  </article>
                </div>
                <div class="col-md-6">
                  <article class="info-row h-100 align-items-start">
                    <div>
                      <div class="text-luxury-faint small text-uppercase">Service Profile</div>
                      <div class="fw-bold text-white mt-1">
                        {{ guest.vipStatus ? "Concierge Priority" : "Signature Stay" }}
                      </div>
                      <div class="text-luxury-soft small">{{ guest.status === "active" ? "Currently in-house" : "Awaiting arrival" }}</div>
                    </div>
                  </article>
                </div>
                <div class="col-12">
                  <article class="info-row align-items-start">
                    <div>
                      <div class="text-luxury-faint small text-uppercase">Special Requests</div>
                      <div class="fw-bold text-white mt-1">
                        {{ guest.specialRequests || "No special requests recorded yet." }}
                      </div>
                    </div>
                  </article>
                </div>
              </div>
            </div>
          </div>
        </div>

        <aside class="page-grid">
          <section class="luxury-card p-4 animate-fade-in animate-delayed-2">
            <h3 class="panel-title mb-2">Stay Framing</h3>
            <p class="panel-subtitle mb-4">Quick operational framing for the desk team.</p>

            <div class="info-list">
              <article class="info-row">
                <span class="timeline-dot"></span>
                <div>
                  <div class="fw-bold text-white">Arrival state</div>
                  <div class="text-luxury-faint small">
                    {{ guest.status === "active" ? "Guest is currently checked in and active." : "Guest is pending and should be reviewed before arrival." }}
                  </div>
                </div>
              </article>
              <article class="info-row">
                <span class="timeline-dot"></span>
                <div>
                  <div class="fw-bold text-white">Service tier</div>
                  <div class="text-luxury-faint small">
                    {{ guest.vipStatus ? "VIP handling is enabled for a higher-touch journey." : "Standard signature hospitality applies." }}
                  </div>
                </div>
              </article>
            </div>
          </section>
        </aside>
      </section>
    </div>

    <div v-else class="luxury-card p-5 text-center animate-fade-in">
      <div class="empty-state__icon mx-auto mb-3">
        <i class="bi bi-exclamation-diamond"></i>
      </div>
      <h2 class="section-title mb-2">Guest Not Found</h2>
      <p class="section-copy mb-4">The dossier you requested is no longer available in the manifest.</p>
      <router-link to="/guests" class="btn luxury-btn">Return to Guest List</router-link>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { useGuestStore } from "@/stores/guestStore";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const guestStore = useGuestStore();
const remoteGuest = ref(null);

onMounted(async () => {
  await guestStore.ensureLoaded();
  remoteGuest.value = await guestStore.fetchGuest(Number(route.params.id)).catch(() => null);
});

const guest = computed(() => remoteGuest.value || guestStore.getGuestById(Number(route.params.id)));

const formatDate = (date) =>
  new Date(date).toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

const statusClass = (status) =>
  status === "active" ? "status-pill status-pill-active" : "status-pill status-pill-pending";

const handleDelete = () => {
  if (guest.value && confirm(`Are you sure you want to delete ${guest.value.fullName}?`)) {
    guestStore.deleteGuest(guest.value.id).then(() => {
      router.push("/guests");
    });
  }
};
</script>

<style scoped>
.empty-state__icon {
  align-items: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  display: inline-flex;
  font-size: 2rem;
  height: 72px;
  justify-content: center;
  width: 72px;
}
</style>
