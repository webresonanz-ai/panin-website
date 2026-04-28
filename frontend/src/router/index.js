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
      path: "/",
      name: "home",
      component: () => import("@/views/HomeView.vue"),
      meta: { title: "Dashboard" },
    },
    {
      path: "/register",
      name: "register",
      component: () => import("@/views/RegisterView.vue"),
      meta: { title: "Register Guest", requiresAuth: true },
    },
    {
      path: "/guests",
      name: "guests",
      component: () => import("@/views/GuestsView.vue"),
      meta: { title: "All Guests" },
    },
    {
      path: "/guest/:id",
      name: "guest-detail",
      component: () => import("@/views/GuestDetailView.vue"),
      meta: { title: "Guest Details" },
    },
  ],
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore(pinia);

  document.title = `${to.meta.title} | Luxury Hotel`;

  if (!authStore.authChecked) {
    await authStore.bootstrap();
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: "login", query: { redirect: to.fullPath } };
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return { name: "home" };
  }

  return true;
});

export default router;
