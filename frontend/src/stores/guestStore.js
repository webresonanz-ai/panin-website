import { defineStore } from "pinia";
import { ref, computed } from "vue";

export const useGuestStore = defineStore("guests", () => {
  // State
  const guests = ref([
    {
      id: 1,
      fullName: "Isabella Rossi",
      email: "isabella.rossi@email.com",
      phone: "+39 123 456 7890",
      suite: "Imperial Suite",
      checkIn: "2024-01-15",
      checkOut: "2024-01-20",
      status: "active",
      specialRequests: "Champagne upon arrival, Extra pillows",
      vipStatus: true,
    },
    {
      id: 2,
      fullName: "Alexander Chen",
      email: "alex.chen@email.com",
      phone: "+86 987 654 3210",
      suite: "Royal Penthouse",
      checkIn: "2024-01-16",
      checkOut: "2024-01-22",
      status: "pending",
      specialRequests: "Vegan meal plan, Airport transfer",
      vipStatus: true,
    },
    {
      id: 3,
      fullName: "Victoria Sterling",
      email: "vicky.sterling@email.com",
      phone: "+44 20 1234 5678",
      suite: "Diamond Suite",
      checkIn: "2024-01-17",
      checkOut: "2024-01-19",
      status: "active",
      specialRequests: "Spa appointments daily",
      vipStatus: false,
    },
  ]);

  const nextId = ref(4);

  // Getters
  const allGuests = computed(() => guests.value);

  const activeGuests = computed(() => guests.value.filter((g) => g.status === "active"));

  const pendingGuests = computed(() => guests.value.filter((g) => g.status === "pending"));

  const vipGuests = computed(() => guests.value.filter((g) => g.vipStatus));

  const totalGuests = computed(() => guests.value.length);

  const getGuestById = computed(() => {
    return (id) => guests.value.find((g) => g.id === id) || null;
  });

  // Actions
  function addGuest(guestData) {
    const newGuest = {
      id: nextId.value++,
      ...guestData,
      status: "active",
      createdAt: new Date().toISOString(),
    };
    guests.value.push(newGuest);
    return newGuest;
  }

  function updateGuest(id, updatedData) {
    const index = guests.value.findIndex((g) => g.id === id);
    if (index !== -1) {
      guests.value[index] = { ...guests.value[index], ...updatedData };
      return true;
    }
    return false;
  }

  function deleteGuest(id) {
    const index = guests.value.findIndex((g) => g.id === id);
    if (index !== -1) {
      guests.value.splice(index, 1);
      return true;
    }
    return false;
  }

  function updateGuestStatus(id, status) {
    return updateGuest(id, { status });
  }

  return {
    guests,
    allGuests,
    activeGuests,
    pendingGuests,
    vipGuests,
    totalGuests,
    getGuestById,
    addGuest,
    updateGuest,
    deleteGuest,
    updateGuestStatus,
  };
});
