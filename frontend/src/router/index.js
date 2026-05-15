import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { pinia } from "@/stores/pinia";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/login",
      name: "login",
      component: () => import("@/views/LoginView.vue"),
      meta: { title: "Staff Login", guestOnly: true },
    },
    {
      path: "/guests",
      name: "guests",
      component: () => import("@/views/HomeView.vue"),
      meta: { title: "Dashboard", requiresAuth: true, requiresGuestManager: true },
    },
    {
      path: "/register",
      name: "register",
      component: () => import("@/views/UserRegisterView.vue"),
      meta: { title: "Create Account", guestOnly: true },
    },
    {
      path: "/guest-registration",
      name: "guest-register",
      component: () => import("@/views/RegisterView.vue"),
      meta: { title: "Register Guest", requiresAuth: true, requiresGuestManager: true },
    },
    {
      path: "/",
      name: "home",
      component: () => import("@/views/GuestsView.vue"),
      meta: { title: "All Guests", requiresAuth: false, requiresGuestManager: false },
    },
    {
      path: "/guest/:id",
      name: "guest-detail",
      component: () => import("@/views/GuestDetailView.vue"),
      meta: { title: "Guest Details", requiresAuth: true, requiresGuestManager: true },
    },
    {
      path: "/check-in-scanner",
      name: "check-in-scanner",
      component: () => import("@/views/CheckInScannerView.vue"),
      meta: { title: "QR Check-In", hideHeader: true },
    },
  ],
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore(pinia);

  document.title = `${to.meta.title} | Panin`;

  if (!authStore.authChecked) {
    await authStore.bootstrap();
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: "login", query: { redirect: to.fullPath } };
  }

  if (to.meta.requiresGuestManager && !authStore.canManageGuests) {
    return { name: "guests" };
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return authStore.canManageGuests ? { name: "home" } : { name: "guests" };
  }

  return true;
});

export default router;
