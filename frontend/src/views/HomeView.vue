<template>
  <div class="page-shell">
    <section class="page-hero animate-fade-in">
      <div class="row g-4 align-items-end">
        <div class="col-xl-8">
          <div class="eyebrow">Luxury Operations Dashboard</div>
          <h1 class="hero-title">Elevated guest management with a sharper hospitality rhythm.</h1>
          <p class="hero-subtitle">
            Track arrivals, VIP moments, and stay readiness through a polished command surface
            built around blue, ruby, and ivory harmony.
          </p>

          <div class="d-flex flex-wrap gap-2 mt-4">
            <router-link to="/register" class="btn luxury-btn">
              <i class="bi bi-plus-circle"></i>
              New Registration
            </router-link>
            <router-link to="/guests" class="btn luxury-btn luxury-btn-secondary">
              <i class="bi bi-people"></i>
              Review Manifest
            </router-link>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="metric-chip">
            <small>Service Snapshot</small>
            <h3>{{ guestStore.activeGuests.length }}/{{ guestStore.totalGuests }}</h3>
            <p>stays are currently active and ready for front-desk action.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="mt-4">
      <GuestStats />
    </section>

    <section class="dashboard-layout mt-4">
      <div class="page-grid">
        <GuestTable @edit="handleEdit" @delete="handleDelete" />

        <section class="luxury-card p-4 animate-fade-in animate-delayed">
          <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
            <div>
              <h3 class="panel-title">Arrival Watchlist</h3>
              <p class="panel-subtitle">High-touch stays that need eyes on timing and preferences.</p>
            </div>
            <span class="badge-luxury">{{ recentCheckIns.length }} Live</span>
          </div>

          <div class="info-list">
            <article v-for="guest in recentCheckIns" :key="guest.id" class="info-row">
              <div class="guest-avatar-small">{{ guest.fullName.charAt(0) }}</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white">{{ guest.fullName }}</div>
                <div class="text-luxury-faint small">{{ guest.suite }} | {{ guest.email }}</div>
              </div>
              <span :class="statusClass(guest.status)">
                <span class="timeline-dot"></span>
                {{ guest.status }}
              </span>
            </article>
          </div>
        </section>
      </div>

      <aside class="page-grid">
        <section class="luxury-card p-4 animate-fade-in animate-delayed-2">
          <h3 class="panel-title mb-2">VIP Lounge</h3>
          <p class="panel-subtitle mb-4">Priority guests receiving enhanced service orchestration.</p>

          <div class="info-list">
            <article v-for="guest in guestStore.vipGuests" :key="guest.id" class="info-row">
              <div class="guest-avatar-small">{{ guest.fullName.charAt(0) }}</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white d-flex align-items-center gap-2">
                  <span>{{ guest.fullName }}</span>
                  <i class="bi bi-stars"></i>
                </div>
                <div class="text-luxury-faint small">{{ guest.specialRequests || "No special requests noted" }}</div>
              </div>
            </article>
          </div>
        </section>

        <section class="luxury-card p-4 animate-fade-in animate-delayed-3">
          <h3 class="panel-title mb-2">Operations Notes</h3>
          <p class="panel-subtitle mb-4">A quick pulse on the desk before the next arrival wave.</p>

          <div class="info-list">
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Arrival pacing</div>
                <div class="text-luxury-faint small">Pending guests are clustered around premium suites.</div>
              </div>
            </article>
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Service composition</div>
                <div class="text-luxury-faint small">VIP ratio remains strong for concierge-led upsell opportunities.</div>
              </div>
            </article>
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Guest sentiment</div>
                <div class="text-luxury-faint small">Special requests are concentrated on dining, transfers, and spa needs.</div>
              </div>
            </article>
          </div>
        </section>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useGuestStore } from "@/stores/guestStore";
import GuestStats from "@/components/guests/GuestStats.vue";
import GuestTable from "@/components/guests/GuestTable.vue";

const router = useRouter();
const guestStore = useGuestStore();

onMounted(() => {
  guestStore.ensureLoaded();
});

const recentCheckIns = computed(() => guestStore.allGuests.slice(0, 3));

const handleEdit = (guest) => {
  router.push({ name: "register", query: { edit: guest.id } });
};

const handleDelete = async (id) => {
  await guestStore.deleteGuest(id);
};

const statusClass = (status) =>
  status === "active" ? "status-pill status-pill-active" : "status-pill status-pill-pending";
</script>
