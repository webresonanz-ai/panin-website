<template>
  <div class="page-shell">
    <section class="dashboard-layout">
      <div class="page-grid">
        <section class="page-hero animate-fade-in">
          <div class="eyebrow">Reservation Atelier</div>
          <h1 class="hero-title">
            {{ isEditing ? "Refine the guest brief before arrival." : "Design the next guest arrival with clarity." }}
          </h1>
          <p class="hero-subtitle">
            {{ isEditing
              ? "Update the reservation profile, preferences, and stay timing without losing continuity."
              : "A single place to register a new stay with the pace and confidence of a modern concierge desk." }}
          </p>
        </section>

        <GuestForm
          :guestData="guestToEdit"
          :isEditing="isEditing"
          @submit="handleSubmit"
          @cancel="handleCancel"
        />
      </div>

      <aside class="page-grid">
        <section class="luxury-card p-4 animate-fade-in animate-delayed">
          <h3 class="panel-title mb-2">Registration Standards</h3>
          <p class="panel-subtitle mb-4">Keep the handoff elegant and operationally clean.</p>

          <div class="info-list">
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Confirm stay window</div>
                <div class="text-luxury-faint small">Check-in and check-out dates should align with the booked suite tier.</div>
              </div>
            </article>
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Capture service signals</div>
                <div class="text-luxury-faint small">Transfers, spa plans, and dining notes help the team personalize the stay.</div>
              </div>
            </article>
            <article class="info-row">
              <span class="timeline-dot"></span>
              <div>
                <div class="fw-bold text-white">Apply VIP handling deliberately</div>
                <div class="text-luxury-faint small">Reserve the badge for guests needing concierge priority and elevated follow-through.</div>
              </div>
            </article>
          </div>
        </section>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useGuestStore } from "@/stores/guestStore";
import GuestForm from "@/components/guests/GuestForm.vue";

const route = useRoute();
const router = useRouter();
const guestStore = useGuestStore();

const isEditing = ref(false);
const guestToEdit = ref(null);

onMounted(() => {
  const editId = route.query.edit;
  if (editId) {
    const guest = guestStore.getGuestById(Number(editId));
    if (guest) {
      guestToEdit.value = guest;
      isEditing.value = true;
    }
  }
});

const handleSubmit = (formData) => {
  if (isEditing.value && guestToEdit.value) {
    guestStore.updateGuest(guestToEdit.value.id, formData);
  } else {
    guestStore.addGuest(formData);
  }

  router.push({ name: "guests" });
};

const handleCancel = () => {
  router.push({ name: "home" });
};
</script>
