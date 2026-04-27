import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
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
      meta: { title: "Register Guest" },
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

router.beforeEach((to, from, next) => {
  document.title = `${to.meta.title} | Luxury Hotel`;
  next();
});

export default router;
