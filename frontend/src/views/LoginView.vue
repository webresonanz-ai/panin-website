<template>
  <div class="page-shell login-shell">
    <section class="dashboard-layout">
      <div class="page-grid">
        <section class="page-hero animate-fade-in">
          <div class="eyebrow">Staff Access</div>
          <h1 class="hero-title">Secure the concierge workflow before the next arrival wave.</h1>
          <p class="hero-subtitle">
            Sign in to manage reservations, service signals, and guest records through the protected desk dashboard.
          </p>

          <div class="luxury-card p-4 mt-4 login-note">
            <h3 class="panel-title mb-2">Starter Credentials</h3>
            <p class="panel-subtitle mb-1">Use the seeded admin account while you wire your own users.</p>
            <div class="text-luxury-soft small">Email: admin@luxuryhotel.test</div>
            <div class="text-luxury-soft small">Password: password123</div>
          </div>
        </section>
      </div>

      <aside class="page-grid">
        <section class="luxury-card p-4 p-lg-5 animate-fade-in animate-delayed">
          <div class="eyebrow">Protected Login</div>
          <h2 class="section-title mb-2">Front desk authentication</h2>
          <p class="section-copy mb-4">Only authenticated staff can access the guest manifest and registration tools.</p>

          <form class="form-layout mt-0" @submit.prevent="handleSubmit">
            <div>
              <label class="form-label text-luxury-soft">Email</label>
              <input v-model="form.email" type="email" class="form-control luxury-input" required />
            </div>

            <div>
              <label class="form-label text-luxury-soft">Password</label>
              <input v-model="form.password" type="password" class="form-control luxury-input" required />
            </div>

            <div v-if="errorMessage" class="alert alert-danger mb-0">{{ errorMessage }}</div>

            <button type="submit" class="btn luxury-btn w-100" :disabled="authStore.loading">
              <i class="bi bi-shield-lock"></i>
              {{ authStore.loading ? "Signing In..." : "Sign In" }}
            </button>
          </form>
        </section>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const errorMessage = ref("");

const form = reactive({
  email: "admin@luxuryhotel.test",
  password: "password123",
});

const handleSubmit = async () => {
  errorMessage.value = "";

  try {
    await authStore.login(form);
    const redirectTarget = typeof route.query.redirect === "string" ? route.query.redirect : "/";
    router.push(redirectTarget);
  } catch (error) {
    errorMessage.value = error.message || "Unable to sign in.";
  }
};
</script>

<style scoped>
.login-shell {
  padding-top: 9rem;
}

.login-note {
  max-width: 32rem;
}
</style>
