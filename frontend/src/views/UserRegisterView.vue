<template>
  <div class="page-shell auth-shell">
    <section class="dashboard-layout">
      <div class="page-grid">
        <section class="page-hero animate-fade-in">
          <div class="eyebrow">Create Staff Account</div>
          <h1 class="hero-title">Set up a simple account to access the dashboard.</h1>
          <p class="hero-subtitle">
            Create your user once, then manage guest data, check-ins, and event operations from the
            protected panel.
          </p>

          <div class="luxury-card p-4 mt-4 auth-note">
            <h3 class="panel-title mb-2">What you get</h3>
            <p class="panel-subtitle mb-0">
              Instant sign-in after registration, so the flow stays quick and functional.
            </p>
          </div>
        </section>
      </div>

      <aside class="page-grid">
        <section class="luxury-card p-4 p-lg-5 animate-fade-in animate-delayed">
          <div class="eyebrow">Sign Up</div>
          <h2 class="section-title mb-2">New user registration</h2>
          <p class="section-copy mb-4">
            Use your name, email, and password to create a staff account.
          </p>
          <p class="text-luxury-soft small mb-4">
            New self-registered accounts are created with the <strong>user</strong> role.
          </p>

          <form class="form-layout mt-0" @submit.prevent="handleSubmit">
            <div>
              <label class="form-label text-luxury-soft">Full Name</label>
              <input v-model="form.name" type="text" class="form-control luxury-input" required />
            </div>

            <div>
              <label class="form-label text-luxury-soft">Email</label>
              <input v-model="form.email" type="email" class="form-control luxury-input" required />
            </div>

            <div>
              <label class="form-label text-luxury-soft">Password</label>
              <input
                v-model="form.password"
                type="password"
                minlength="8"
                class="form-control luxury-input"
                required
              />
            </div>

            <div>
              <label class="form-label text-luxury-soft">Confirm Password</label>
              <input
                v-model="form.passwordConfirmation"
                type="password"
                minlength="8"
                class="form-control luxury-input"
                required
              />
            </div>

            <div v-if="errorMessage" class="alert alert-danger mb-0">{{ errorMessage }}</div>

            <button type="submit" class="btn luxury-btn w-100 mt-3" :disabled="authStore.loading">
              <i class="bi bi-person-plus"></i>
              {{ authStore.loading ? "Creating Account..." : "Create Account" }}
            </button>
          </form>

          <p class="text-luxury-soft small mt-4 mb-0 text-center">
            Already have an account?
            <router-link :to="{ name: 'login' }" class="auth-link">Sign in</router-link>
          </p>
        </section>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const router = useRouter();
const authStore = useAuthStore();
const errorMessage = ref("");

const form = reactive({
  name: "",
  email: "",
  password: "",
  passwordConfirmation: "",
});

const handleSubmit = async () => {
  errorMessage.value = "";

  if (form.password !== form.passwordConfirmation) {
    errorMessage.value = "Password confirmation does not match.";
    return;
  }

  try {
    await authStore.register(form);
    router.push(authStore.defaultRoute);
  } catch (error) {
    errorMessage.value = error.message || "Unable to create account.";
  }
};
</script>

<style scoped>
.auth-shell {
  padding-top: 9rem;
}

.auth-note {
  max-width: 32rem;
}

.auth-link {
  color: var(--luxury-accent);
  font-weight: 700;
  text-decoration: none;
}

.auth-link:hover {
  text-decoration: underline;
}
</style>
