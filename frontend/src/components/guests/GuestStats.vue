<template>
  <div class="metric-grid">
    <article
      v-for="(stat, index) in stats"
      :key="stat.title"
      class="luxury-card metric-card animate-fade-in"
      :class="delayClasses[index]"
    >
      <div class="metric-card__icon">
        <i :class="stat.icon"></i>
      </div>
      <small>{{ stat.kicker }}</small>
      <h3>{{ stat.value }}</h3>
      <p>{{ stat.title }}</p>
      <span class="metric-card__note">{{ stat.note }}</span>
    </article>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useGuestStore } from "@/stores/guestStore";

const guestStore = useGuestStore();
const delayClasses = ["", "animate-delayed", "animate-delayed-2", "animate-delayed-3"];

const stats = computed(() => [
  {
    icon: "bi bi-people-fill",
    kicker: "Portfolio",
    title: "Guests in Residence",
    value: guestStore.stats.totalGuests,
    note: "Live roster across premium inventory",
  },
  {
    icon: "bi bi-check2-circle",
    kicker: "Flow",
    title: "Checked In",
    value: guestStore.stats.checkedInGuests,
    note: "Guests already cleared through the QR arrival desk",
  },
  {
    icon: "bi bi-hourglass-split",
    kicker: "Attention",
    title: "Awaiting Arrival",
    value: guestStore.stats.awaitingCheckInGuests,
    note: "Guests still waiting to be scanned on arrival",
  },
  {
    icon: "bi bi-stars",
    kicker: "Service Tier",
    title: "VIP Experience",
    value: guestStore.stats.vipGuests,
    note: "Enhanced service journeys in progress",
  },
]);
</script>

<style scoped>
.metric-card {
  min-height: 200px;
  overflow: hidden;
  padding: 1.35rem;
}

.metric-card__icon {
  align-items: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  color: var(--luxury-white);
  display: inline-flex;
  font-size: 1.15rem;
  height: 52px;
  justify-content: center;
  margin-bottom: 1rem;
  width: 52px;
}

.metric-card small {
  color: rgba(246, 247, 251, 0.52);
  display: block;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.metric-card h3 {
  color: var(--luxury-white);
  font-size: clamp(2rem, 3vw, 2.7rem);
  font-weight: 800;
  margin: 0.45rem 0 0.35rem;
}

.metric-card p {
  color: rgba(246, 247, 251, 0.84);
  font-size: 1rem;
  font-weight: 700;
  margin: 0;
}

.metric-card__note {
  color: rgba(246, 247, 251, 0.52);
  display: block;
  line-height: 1.6;
  margin-top: 0.6rem;
}
</style>
