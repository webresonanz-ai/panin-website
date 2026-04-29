<template>
  <section class="luxury-card p-4 p-lg-5 animate-fade-in">
    <div class="form-header">
      <div>
        <div class="eyebrow">Guest Journey</div>
        <h3 class="section-title mb-2">
          {{ isEditing ? "Refine Reservation Details" : "Create a New Signature Stay" }}
        </h3>
        <p class="section-copy">
          Capture the essentials once and keep the check-in handoff crisp for the team.
        </p>
      </div>
      <div class="form-header__badge">
        <i class="bi bi-shield-check"></i>
        Concierge Ready
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="form-layout">
      <div class="form-section">
        <h4 class="panel-title">Guest Profile</h4>
        <div class="row g-3 mt-1">
          <div class="col-md-12">
            <label class="form-label text-luxury-soft">Full Name *</label>
            <div class="input-group">
              <span class="input-group-text border-accent">
                <i class="bi bi-person"></i>
              </span>
              <input
                v-model="form.fullName"
                type="text"
                class="form-control luxury-input"
                required
                placeholder="Isabella Rossi"
              />
            </div>
          </div>

        </div>
      </div>

      <div class="form-section">
        <h4 class="panel-title">Stay Details</h4>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label text-luxury-soft">Check In *</label>
            <input v-model="form.checkIn" type="date" class="form-control luxury-input" required />
          </div>

          <div class="col-md-6">
            <label class="form-label text-luxury-soft">Check Out *</label>
            <input v-model="form.checkOut" type="date" class="form-control luxury-input" required />
          </div>

          <div class="col-12">
            <label class="form-label text-luxury-soft">Special Requests</label>
            <textarea
              v-model="form.specialRequests"
              class="form-control luxury-input"
              rows="4"
              placeholder="Airport transfer, dining notes, spa itinerary, pillow preference..."
            ></textarea>
          </div>
        </div>
      </div>

      <div class="form-foot">
        <label class="vip-toggle" for="vipStatus">
          <input id="vipStatus" v-model="form.vipStatus" type="checkbox" />
          <span class="vip-toggle__control"></span>
          <span class="vip-toggle__copy">
            <strong>VIP Handling</strong>
            <small>Mark this stay for premium service attention.</small>
          </span>
        </label>

        <div class="d-flex flex-wrap gap-2 justify-content-end">
          <button type="button" class="btn luxury-btn-ghost" @click="$emit('cancel')">Cancel</button>
          <button type="submit" class="btn luxury-btn">
            <i class="bi bi-check2-circle"></i>
            {{ isEditing ? "Update Guest" : "Register Guest" }}
          </button>
        </div>
      </div>
    </form>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue";

const props = defineProps({
  guestData: {
    type: Object,
    default: null,
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["submit", "cancel"]);

const defaultForm = () => ({
  fullName: "",
  checkIn: "",
  checkOut: "",
  specialRequests: "",
  vipStatus: false,
});

const form = ref(defaultForm());

onMounted(() => {
  if (props.guestData) {
    form.value = { ...props.guestData };
  }
});

const handleSubmit = () => {
  emit("submit", { ...form.value });

  if (!props.isEditing) {
    form.value = defaultForm();
  }
};
</script>

<style scoped>
.form-layout {
  display: grid;
  gap: 1.5rem;
  margin-top: 1.75rem;
}

.form-header {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.form-header__badge {
  align-items: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  color: rgba(246, 247, 251, 0.8);
  display: inline-flex;
  gap: 0.5rem;
  padding: 0.8rem 1rem;
  white-space: nowrap;
}

.form-section {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 22px;
  padding: 1.25rem;
}

.form-label {
  font-size: 0.88rem;
  font-weight: 700;
  margin-bottom: 0.55rem;
}

.form-foot {
  align-items: center;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.vip-toggle {
  align-items: center;
  cursor: pointer;
  display: inline-flex;
  gap: 0.9rem;
}

.vip-toggle input {
  display: none;
}

.vip-toggle__control {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  height: 28px;
  position: relative;
  transition: background 180ms ease;
  width: 52px;
}

.vip-toggle__control::after {
  background: #fff;
  border-radius: 50%;
  content: "";
  height: 20px;
  left: 4px;
  position: absolute;
  top: 3px;
  transition: transform 180ms ease;
  width: 20px;
}

.vip-toggle input:checked + .vip-toggle__control {
  background: linear-gradient(135deg, rgba(104, 167, 255, 0.9), rgba(217, 72, 98, 0.9));
}

.vip-toggle input:checked + .vip-toggle__control::after {
  transform: translateX(23px);
}

.vip-toggle__copy {
  display: grid;
}

.vip-toggle__copy strong {
  color: var(--luxury-white);
  font-size: 0.95rem;
}

.vip-toggle__copy small {
  color: rgba(246, 247, 251, 0.54);
}

@media (max-width: 767.98px) {
  .form-header,
  .form-foot {
    align-items: stretch;
    flex-direction: column;
  }

  .form-header__badge,
  .vip-toggle {
    width: 100%;
  }
}
</style>
