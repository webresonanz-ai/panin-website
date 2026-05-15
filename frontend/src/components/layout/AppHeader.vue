<template>
  <nav class="navbar navbar-expand-lg fixed-top app-nav">
    <div class="container-fluid px-3 px-lg-4">
      <div class="app-nav__shell w-100">
        <router-link
          class="navbar-brand app-brand"
          :to="authStore.canManageGuests ? '/guests' : '/'"
          @click="closeMenu"
        >
          <span class="app-brand__crest">
            <i class="bi bi-buildings"></i>
          </span>
          <span class="app-brand__copy">
            <span class="app-brand__eyebrow">Annual Awarding Dinner 2026</span>
            <span class="app-brand__name">PaninDai-ichiLife</span>
          </span>
        </router-link>

        <button
          class="navbar-toggler app-nav__toggle"
          type="button"
          aria-controls="navbarNav"
          :aria-expanded="String(isMenuOpen)"
          aria-label="Toggle navigation"
          @click="toggleMenu"
        >
          <i :class="isMenuOpen ? 'bi bi-x-lg' : 'bi bi-list'"></i>
        </button>

        <div
          id="navbarNav"
          class="navbar-collapse app-nav__panel"
          :class="{ 'app-nav__panel--open': isMenuOpen }"
        >
          <ul class="navbar-nav app-nav__menu ms-auto align-items-lg-center gap-lg-2">
            <li class="nav-item" v-for="item in navItems" :key="item.to">
              <router-link class="nav-link app-link" :to="item.to" @click="closeMenu">
                <i :class="item.icon"></i>
                <span class="app-link__label">{{ item.label }}</span>
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
                @click="closeMenu"
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
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const isMenuOpen = ref(false);

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

const closeMenu = () => {
  isMenuOpen.value = false;
};

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value;
};

watch(
  () => route.fullPath,
  () => {
    closeMenu();
  },
);

const handleLogout = async () => {
  await authStore.logout();
  closeMenu();
  router.push({ name: "login" });
};
</script>

<style scoped>
.app-nav {
  padding: 0.9rem var(--page-padding) 0;
}

.app-nav__shell {
  align-items: center;
  background:
    linear-gradient(180deg, rgba(7, 15, 37, 0.84), rgba(5, 10, 29, 0.8)),
    radial-gradient(circle at 18% 0%, rgba(255, 88, 82, 0.16), transparent 30%),
    radial-gradient(circle at 82% 0%, rgba(57, 214, 255, 0.16), transparent 32%);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 28px;
  box-shadow:
    0 26px 60px rgba(1, 8, 25, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.04);
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 0.8rem 0.9rem;
  position: relative;
}

.app-brand {
  align-items: center;
  color: var(--luxury-white);
  display: inline-flex;
  gap: 0.9rem;
  min-width: 0;
  text-decoration: none;
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
  border-radius: 20px;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.08),
    0 16px 30px rgba(2, 8, 24, 0.22);
  color: var(--luxury-white);
  display: inline-flex;
  flex-shrink: 0;
  font-size: 1.2rem;
  height: 52px;
  justify-content: center;
  width: 52px;
}

.app-brand__copy {
  display: grid;
  gap: 0.16rem;
  min-width: 0;
}

.app-brand__eyebrow,
.app-brand__name,
.app-brand__meta {
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
  font-family: "Cinzel", serif;
  font-size: clamp(1.25rem, 1.6vw, 1.8rem);
  font-weight: 700;
  letter-spacing: 0.02em;
  line-height: 1.05;
}

.app-brand__meta {
  color: rgba(219, 231, 255, 0.66);
  font-size: 0.76rem;
  letter-spacing: 0.03em;
}

.app-nav__toggle {
  align-items: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  color: var(--luxury-white);
  display: none;
  height: 48px;
  justify-content: center;
  box-shadow: none;
  padding: 0;
  transition:
    background 180ms ease,
    border-color 180ms ease,
    transform 180ms ease;
  width: 48px;
}

.app-nav__toggle:hover,
.app-nav__toggle:focus-visible {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.app-nav__toggle:focus {
  box-shadow: none;
}

.app-nav__panel {
  align-items: center;
  display: flex;
  flex: 1;
  justify-content: flex-end;
  min-width: 0;
}

.app-nav__menu {
  align-items: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  display: inline-flex;
  gap: 0.35rem;
  padding: 0.35rem;
}

.app-link {
  align-items: center;
  border: 1px solid transparent;
  border-radius: 999px;
  color: rgba(246, 247, 251, 0.72) !important;
  display: inline-flex;
  font-size: 0.89rem;
  font-weight: 700;
  gap: 0.55rem;
  letter-spacing: 0.04em;
  padding: 0.75rem 1rem !important;
  white-space: nowrap;
  transition:
    color 180ms ease,
    background 180ms ease,
    border-color 180ms ease,
    transform 180ms ease;
}

.app-link__label {
  line-height: 1;
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
  font-size: 0.86rem;
  gap: 0.55rem;
  padding: 0.75rem 1rem;
  white-space: nowrap;
}

.app-logout {
  white-space: nowrap;
}

@media (max-width: 1199.98px) {
  .app-nav__menu {
    gap: 0.2rem;
  }

  .app-link {
    font-size: 0.84rem;
    padding-inline: 0.8rem !important;
  }

  .app-presence {
    font-size: 0.8rem;
    padding-inline: 0.85rem;
  }
}

@media (max-width: 991.98px) {
  .app-nav {
    padding-inline: 0.6rem;
  }

  .app-nav__shell {
    align-items: stretch;
    border-radius: 24px;
    flex-wrap: wrap;
    gap: 0.85rem;
    padding: 0.85rem;
  }

  .app-nav__toggle {
    display: inline-flex;
    margin-left: auto;
  }

  .app-brand {
    flex: 1;
  }

  .app-nav__panel {
    display: none;
    flex: 0 0 100%;
  }

  .app-nav__panel--open {
    display: block;
  }

  .app-nav__menu {
    align-items: stretch !important;
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02)),
      rgba(4, 10, 27, 0.56);
    border-radius: 22px;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin-top: 0.15rem;
    padding: 0.75rem;
    width: 100%;
  }

  .app-link {
    border-radius: 18px;
    justify-content: flex-start;
    padding: 0.95rem 1rem !important;
    width: 100%;
  }

  .app-presence,
  .app-logout,
  .btn.luxury-btn {
    justify-content: center;
    width: 100%;
  }

  .app-presence {
    border-radius: 18px;
    margin-top: 0.25rem;
    padding: 0.95rem 1rem;
  }

  .app-logout,
  .btn.luxury-btn {
    border-radius: 18px;
    padding-block: 0.9rem;
  }
}

@media (max-width: 575.98px) {
  .app-nav {
    padding-top: 0.65rem;
  }

  .app-nav__shell {
    border-radius: 22px;
    padding: 0.75rem;
  }

  .app-brand {
    gap: 0.7rem;
  }

  .app-brand__crest {
    border-radius: 18px;
    font-size: 1.05rem;
    height: 46px;
    width: 46px;
  }

  .app-brand__eyebrow {
    font-size: 0.62rem;
    letter-spacing: 0.12em;
  }

  .app-brand__name {
    font-size: 1.05rem;
  }

  .app-brand__meta {
    font-size: 0.69rem;
  }

  .app-nav__toggle {
    border-radius: 14px;
    height: 44px;
    width: 44px;
  }

  .app-nav__menu {
    border-radius: 20px;
    padding: 0.65rem;
  }
}
</style>
