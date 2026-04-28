import { defineStore } from "pinia";
import { computed, ref } from "vue";
import { api } from "@/lib/api";

const TOKEN_KEY = "luxury-dashboard-token";
const USER_KEY = "luxury-dashboard-user";

export const useAuthStore = defineStore("auth", () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || "");
  const user = ref(JSON.parse(localStorage.getItem(USER_KEY) || "null"));
  const loading = ref(false);
  const authChecked = ref(false);

  const isAuthenticated = computed(() => Boolean(token.value && user.value));

  function persistSession(sessionToken, sessionUser) {
    token.value = sessionToken;
    user.value = sessionUser;
    localStorage.setItem(TOKEN_KEY, sessionToken);
    localStorage.setItem(USER_KEY, JSON.stringify(sessionUser));
  }

  function clearSession() {
    token.value = "";
    user.value = null;
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  }

  async function login(credentials) {
    loading.value = true;

    try {
      const response = await api.post("/api/auth/login", credentials);
      persistSession(response.data.token, response.data.user);
      authChecked.value = true;
      return response.data.user;
    } finally {
      loading.value = false;
    }
  }

  async function bootstrap() {
    if (!token.value) {
      authChecked.value = true;
      return;
    }

    try {
      const response = await api.get("/api/auth/me");
      persistSession(token.value, response.data.user);
    } catch {
      clearSession();
    } finally {
      authChecked.value = true;
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post("/api/auth/logout", {});
      }
    } catch {
      // Ignore logout API failures so the client can still recover locally.
    } finally {
      clearSession();
      authChecked.value = true;
    }
  }

  return {
    token,
    user,
    loading,
    authChecked,
    isAuthenticated,
    login,
    logout,
    bootstrap,
    clearSession,
  };
});
