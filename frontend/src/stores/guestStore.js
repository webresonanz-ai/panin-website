import { defineStore } from "pinia";
import { computed, ref } from "vue";
import { api } from "@/lib/api";

export const useGuestStore = defineStore("guests", () => {
  const guests = ref([]);
  const stats = ref({
    totalGuests: 0,
    checkedInGuests: 0,
    awaitingCheckInGuests: 0,
  });
  const loading = ref(false);
  const loaded = ref(false);
  const error = ref("");

  const allGuests = computed(() => guests.value);
  const totalGuests = computed(() => guests.value.length);

  const getGuestById = computed(() => {
    return (id) => guests.value.find((guest) => guest.id === id) || null;
  });

  function setGuestCollection(payload) {
    guests.value = payload.guests || [];
    stats.value = payload.stats || {
      totalGuests: guests.value.length,
      checkedInGuests: guests.value.filter((guest) => guest.isCheckedIn).length,
      awaitingCheckInGuests: guests.value.filter((guest) => !guest.isCheckedIn).length,
    };
    loaded.value = true;
  }

  async function fetchGuests(filters = {}) {
    loading.value = true;
    error.value = "";

    try {
      const params = new URLSearchParams();

      if (filters.search) {
        params.set("search", filters.search);
      }

      if (filters.status && filters.status !== "all") {
        params.set("status", filters.status);
      }

      const query = params.toString() ? `?${params.toString()}` : "";
      const response = await api.get(`/api/guests${query}`);
      setGuestCollection(response.data);
      return guests.value;
    } catch (requestError) {
      error.value = requestError.message || "Unable to load guests.";
      throw requestError;
    } finally {
      loading.value = false;
    }
  }

  async function ensureLoaded() {
    if (!loaded.value) {
      await fetchGuests();
    }
  }

  async function addGuest(guestData) {
    const response = await api.post("/api/guests", guestData);
    await fetchGuests();
    return response.data.guest;
  }

  async function updateGuest(id, updatedData) {
    const response = await api.put(`/api/guests/${id}`, updatedData);
    await fetchGuests();
    return response.data.guest;
  }

  async function deleteGuest(id) {
    await api.delete(`/api/guests/${id}`);
    await fetchGuests();
  }

  async function fetchGuest(id) {
    const response = await api.get(`/api/guests/${id}`);
    return response.data.guest;
  }

  async function checkInGuest(qrCode) {
    const response = await api.post("/api/guests/check-in", { qrCode });
    await fetchGuests().catch(() => {});
    return response.data;
  }

  function reset() {
    guests.value = [];
    error.value = "";
    loaded.value = false;
    stats.value = {
      totalGuests: 0,
      checkedInGuests: 0,
      awaitingCheckInGuests: 0,
    };
  }

  return {
    guests,
    stats,
    loading,
    loaded,
    error,
    allGuests,
    totalGuests,
    getGuestById,
    fetchGuests,
    fetchGuest,
    checkInGuest,
    ensureLoaded,
    addGuest,
    updateGuest,
    deleteGuest,
    reset,
  };
});
