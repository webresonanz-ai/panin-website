<template>
  <div class="page-shell">
    <section class="page-hero animate-fade-in">
      <div class="row g-4 align-items-end">
        <div class="col-lg-8">
          <div class="eyebrow">Guest Portfolio</div>
          <h1 class="hero-title">Every stay, suite, and service signal in one refined manifest.</h1>
          <p class="hero-subtitle">
            Browse the full roster, prioritize arrivals, and act quickly without losing the premium tone.
          </p>
        </div>
        <div class="col-lg-4 d-flex justify-content-lg-end">
          <router-link to="/register" class="btn luxury-btn">
            <i class="bi bi-plus-circle"></i>
            Add Guest
          </router-link>
        </div>
      </div>
    </section>

    <section class="mt-4 animate-fade-in animate-delayed">
      <GuestTable @edit="handleEdit" @delete="handleDelete" />
    </section>
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useGuestStore } from "@/stores/guestStore";
import GuestTable from "@/components/guests/GuestTable.vue";

const router = useRouter();
const guestStore = useGuestStore();

onMounted(() => {
  guestStore.ensureLoaded();
});

const handleEdit = (guest) => {
  router.push({ name: "register", query: { edit: guest.id } });
};

const handleDelete = async (id) => {
  await guestStore.deleteGuest(id);
};
</script>
