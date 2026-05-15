<template>
  <nav class="navbar navbar-expand-lg fixed-top app-nav">
    <div class="container-fluid px-3 px-lg-4">
      <div class="app-nav__shell w-100">
        <router-link
          class="navbar-brand app-brand"
          :to="authStore.canManageGuests ? '/' : '/guests'"
        >
          <span class="app-brand__crest">
            <i class="bi bi-buildings"></i>
          </span>
          <span>
            <span class="app-brand__eyebrow">Annual Awarding Dinner 2026</span>
            <span class="app-brand__name">PaninDai-ichiLife</span>
          </span>
        </router-link>

        <button
          class="navbar-toggler app-nav__toggle"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
          aria-controls="navbarNav"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <i class="bi bi-list"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <li class="nav-item" v-for="item in navItems" :key="item.to">
              <router-link class="nav-link app-link" :to="item.to">
                <i :class="item.icon"></i>
                <span>{{ item.label }}</span>
              </router-link>
            </li>
            <li v-if="authStore.isAuthenticated" class="nav-item ms-lg-2">
              <div class="app-presence">
                <span class="timeline-dot"></span>
                <span>{{ authStore.user?.name || "Front Desk Online" }}</span>
              </div>
            </li>
            <li v-if="authStore.isAuthenticated" class="nav-item ms-lg-2 mt-3 mt-lg-0">
              <button type="button" class="btn luxury-btn-ghost app-logout" @click="handleLogout">
                <i class="bi bi-box-arrow-right"></i>
                Sign Out
              </button>
            </li>
            <li v-else class="nav-item ms-lg-2 mt-3 mt-lg-0">
              <router-link
                class="btn luxury-btn"
                :to="{ name: 'login', query: { redirect: '/guest-registration' } }"
              >
                <i class="bi bi-shield-lock"></i>
                Staff Login
              </router-link>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const router = useRouter();
const authStore = useAuthStore();

const navItems = computed(() =>
  authStore.canManageGuests
    ? [
        { to: "/", label: "Dashboard", icon: "bi bi-grid-1x2-fill" },
        { to: "/guests", label: "Guests", icon: "bi bi-people-fill" },
        { to: "/check-in-scanner", label: "Scan Check-In", icon: "bi bi-qr-code-scan" },
        { to: "/guest-registration", label: "Register Guest", icon: "bi bi-person-plus-fill" },
      ]
    : [{ to: "/guests", label: "Guests", icon: "bi bi-people-fill" }],
);

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: "login" });
};
</script>

<style scoped>
.app-nav {
  padding: 1rem var(--page-padding) 0;
}

.app-nav__shell {
  align-items: center;
  background:
    linear-gradient(180deg, rgba(7, 15, 37, 0.84), rgba(5, 10, 29, 0.8)),
    radial-gradient(circle at 18% 0%, rgba(255, 88, 82, 0.16), transparent 30%),
    radial-gradient(circle at 82% 0%, rgba(57, 214, 255, 0.16), transparent 32%);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 24px;
  box-shadow: 0 22px 48px rgba(1, 8, 25, 0.42);
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 0.9rem 1rem;
}

.app-brand {
  align-items: center;
  color: var(--luxury-white);
  display: inline-flex;
  gap: 0.9rem;
  min-width: 0;
}

.app-brand__crest {
  align-items: center;
  background: linear-gradient(
    135deg,
    rgba(255, 124, 53, 0.22),
    rgba(255, 74, 99, 0.2),
    rgba(57, 214, 255, 0.2)
  );
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 18px;
  color: var(--luxury-white);
  display: inline-flex;
  font-size: 1.2rem;
  height: 48px;
  justify-content: center;
  width: 48px;
}

.app-brand__eyebrow,
.app-brand__name {
  display: block;
}

.app-brand__eyebrow {
  color: rgba(246, 247, 251, 0.54);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.app-brand__name {
  font-size: 1.8rem;
  line-height: 1;
}

.app-nav__toggle {
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: none;
  color: var(--luxury-white);
}

.app-link {
  align-items: center;
  border: 1px solid transparent;
  border-radius: 999px;
  color: rgba(246, 247, 251, 0.72) !important;
  display: inline-flex;
  font-size: 0.92rem;
  font-weight: 700;
  gap: 0.55rem;
  letter-spacing: 0.04em;
  padding: 0.75rem 1rem !important;
  transition:
    color 180ms ease,
    background 180ms ease,
    border-color 180ms ease,
    transform 180ms ease;
}

.app-link:hover,
.app-link.router-link-exact-active {
  background: linear-gradient(
    135deg,
    rgba(255, 85, 73, 0.12),
    rgba(255, 74, 99, 0.1),
    rgba(57, 214, 255, 0.12)
  );
  border-color: rgba(255, 255, 255, 0.14);
  color: var(--luxury-white) !important;
  transform: translateY(-1px);
}

.app-presence {
  align-items: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  color: rgba(246, 247, 251, 0.8);
  display: inline-flex;
  gap: 0.55rem;
  padding: 0.75rem 1rem;
  white-space: nowrap;
}

.app-logout {
  white-space: nowrap;
}

@media (max-width: 991.98px) {
  .app-nav {
    padding-inline: 1rem;
  }

  .app-nav__shell {
    align-items: stretch;
    flex-wrap: wrap;
  }

  .app-presence {
    justify-content: center;
    width: 100%;
  }
}
</style>
