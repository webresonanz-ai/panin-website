<template>
  <section class="luxury-card table-shell p-4 p-lg-4">
    <div class="table-toolbar mb-4">
      <div>
        <h3 class="panel-title">Guest Manifest</h3>
        <p class="panel-subtitle">Search, review, and act on each stay from a single view.</p>
      </div>

      <div class="table-toolbar__controls">
        <div class="search-shell">
          <i class="bi bi-search"></i>
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search name, company, position, or seat"
          />
        </div>

        <div class="status-filter" role="tablist" aria-label="Filter guest status">
          <button
            v-for="option in filterOptions"
            :key="option.value"
            type="button"
            class="status-filter__button"
            :class="{ 'status-filter__button--active': statusFilter === option.value }"
            @click="statusFilter = option.value"
          >
            {{ option.label }}
          </button>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-luxury align-middle">
        <thead>
          <tr>
            <th>Guest</th>
            <th>Seat</th>
            <th>Checked-In</th>
            <th v-if="authStore.canManageGuests" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="guest in filteredGuests" :key="guest.id" class="animate-fade-in">
            <td>
              <div class="d-flex align-items-center gap-3">
                <div class="guest-avatar">
                  {{ guest.fullName.charAt(0) }}
                </div>
                <div>
                  <div class="fw-bold">{{ guest.fullName }}</div>
                  <div class="text-luxury-faint small">
                    {{ guest.gaSoPosition || "Guest profile" }}
                  </div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge-luxury">{{ guest.seatNumber || "Unassigned" }}</span>
            </td>
            <td>
              <div class="stay-window">
                <div>
                  {{ guest.checkedInAt ? formatDateTime(guest.checkedInAt) : "Not checked in" }}
                </div>
                <div v-if="guest.checkInMethod" class="text-luxury-faint small">
                  via {{ guest.checkInMethod }}
                </div>
              </div>
            </td>
            <td v-if="authStore.canManageGuests">
              <div class="d-flex justify-content-end gap-2">
                <button
                  type="button"
                  class="action-icon"
                  aria-label="Check in guest"
                  @click="handleCheckIn(guest)"
                >
                  <i class="bi bi-check2-circle"></i>
                </button>
                <button type="button" class="action-icon" aria-label="Edit guest" @click="handleEdit(guest)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button
                  type="button"
                  class="action-icon action-danger"
                  aria-label="Delete guest"
                  @click="confirmDelete(guest)"
                >
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="filteredGuests.length === 0" class="empty-state text-center py-5">
      <div class="empty-state__icon mx-auto mb-3">
        <i class="bi bi-journal-x"></i>
      </div>
      <h4 class="section-title fs-2 mb-2">No guests match this view</h4>
      <p class="section-copy">Try a different search term or switch the active status filter.</p>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { useGuestStore } from "@/stores/guestStore";

const emit = defineEmits(["edit", "delete"]);

const router = useRouter();
const authStore = useAuthStore();
const guestStore = useGuestStore();
const searchQuery = ref("");
const statusFilter = ref("all");

const filterOptions = [
  { value: "all", label: "All" },
  { value: "checkedIn", label: "Checked In" },
  { value: "awaitingCheckIn", label: "Awaiting Check-In" },
];

const filteredGuests = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();

  return guestStore.allGuests.filter((guest) => {
    const matchesQuery =
      !query ||
      guest.fullName.toLowerCase().includes(query) ||
      (guest.gaSoPosition || "").toLowerCase().includes(query) ||
      (guest.seatNumber || "").toLowerCase().includes(query);

    if (statusFilter.value === "awaitingCheckIn") {
      return matchesQuery && guest.checkedInAt === null;
    } else if (statusFilter.value === "checkedIn") {
      return matchesQuery && guest.checkedInAt !== null;
    } else {
      return matchesQuery;
    }
  });
});

watch([searchQuery, statusFilter], () => {
  guestStore
    .fetchGuests({
      search: searchQuery.value,
      status: statusFilter.value,
    })
    .catch(() => {});
});

const formatDate = (date) =>
  new Date(date).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });

const formatDateTime = (date) =>
  new Date(date).toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });

const requireLogin = () => {
  router.push({ name: "login", query: { redirect: "/guest-registration" } });
};

const handleCheckIn = (guest) => {
  if (!authStore.isAuthenticated) {
    requireLogin();
    return;
  }

  guestStore
    .checkInGuest(guest.registrationNumber)
    .then(() => {
      guest.checkedInAt = new Date().toISOString();
      guest.checkInMethod = "Dashboard";
    })
    .catch(() => {
      alert("Failed to check in guest. Please try again.");
    });
};

const handleEdit = (guest) => {
  if (!authStore.isAuthenticated) {
    requireLogin();
    return;
  }

  emit("edit", guest);
};

const confirmDelete = (guest) => {
  if (!authStore.isAuthenticated) {
    requireLogin();
    return;
  }

  if (confirm(`Are you sure you want to remove ${guest.fullName}?`)) {
    emit("delete", guest.id);
  }
};
</script>

<style scoped>
.table-toolbar {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.table-toolbar__controls {
  display: flex;
  flex-wrap: wrap;
  gap: 0.9rem;
  justify-content: flex-end;
}

.search-shell {
  align-items: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  color: rgba(246, 247, 251, 0.5);
  display: flex;
  gap: 0.75rem;
  min-width: min(100%, 320px);
  padding: 0.85rem 1rem;
}

.search-input {
  background: transparent;
  border: 0;
  color: var(--luxury-white);
  flex: 1;
  outline: 0;
}

.search-input::placeholder {
  color: rgba(246, 247, 251, 0.42);
}

.status-filter {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  display: inline-flex;
  gap: 0.25rem;
  padding: 0.35rem;
}

.status-filter__button {
  background: transparent;
  border: 0;
  border-radius: 999px;
  color: rgba(246, 247, 251, 0.58);
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.65rem 0.95rem;
  text-transform: uppercase;
  transition: all 180ms ease;
}

.status-filter__button--active {
  background: linear-gradient(135deg, rgba(104, 167, 255, 0.18), rgba(217, 72, 98, 0.18));
  color: var(--luxury-white);
}

.stay-window {
  display: grid;
  gap: 0.15rem;
}

.table-luxury .fw-bold,
.table-luxury strong {
  color: rgba(255, 255, 255, 0.96);
}

.table-luxury .text-luxury-faint,
.table-luxury .small {
  color: rgba(219, 231, 255, 0.82);
}

.table-luxury td {
  color: rgba(246, 247, 251, 0.92);
}

.guest-avatar {
  flex-shrink: 0;
}

.badge-luxury {
  white-space: nowrap;
}

.service-badge {
  gap: 0.35rem;
}

.empty-state__icon {
  align-items: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  display: inline-flex;
  font-size: 2rem;
  height: 72px;
  justify-content: center;
  width: 72px;
}

@media (max-width: 991.98px) {
  .table-toolbar {
    flex-direction: column;
  }

  .table-toolbar__controls {
    justify-content: flex-start;
    width: 100%;
  }
}

@media (max-width: 767.98px) {
  .table-luxury {
    font-size: 0.875rem;
  }

  .table-luxury th {
    font-size: 0.72rem;
  }

  .table-luxury .fw-bold {
    font-size: 0.9rem;
  }

  .table-luxury .small,
  .badge-luxury {
    font-size: 0.68rem;
  }

  .search-shell {
    min-width: 100%;
  }

  .status-filter {
    flex-wrap: wrap;
  }
}
</style>
