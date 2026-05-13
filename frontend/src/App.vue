<template>
  <div id="app">
    <div class="app-background" aria-hidden="true">
      <video class="app-background__video" autoplay muted loop playsinline preload="auto">
        <source :src="auroraVideo" type="video/mp4" />
      </video>
    </div>
    <AppHeader v-if="!route.meta.hideHeader" />
    <main class="app-shell">
      <router-view v-slot="{ Component }">
        <transition name="page-shift" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { useRoute } from "vue-router";
import AppHeader from "@/components/layout/AppHeader.vue";
import auroraVideo from "@/assets/videos/bg_aurora.mp4";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "bootstrap-icons/font/bootstrap-icons.css";
import "@/assets/styles/custom.scss";

const route = useRoute();
</script>

<style>
.app-background {
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  position: fixed;
  z-index: 0;
}

.app-background__video {
  height: 100%;
  left: 50%;
  min-height: 100%;
  min-width: 100%;
  object-fit: cover;
  opacity: 0.92;
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
}

.app-shell {
  position: relative;
  z-index: 1;
}

.page-shift-enter-active,
.page-shift-leave-active {
  transition:
    opacity 320ms ease,
    transform 320ms ease,
    filter 320ms ease;
}

.page-shift-enter-from,
.page-shift-leave-to {
  filter: blur(10px);
  opacity: 0;
  transform: translateY(18px);
}

::-webkit-scrollbar {
  width: 10px;
}

::-webkit-scrollbar-track {
  background: rgba(6, 12, 28, 0.95);
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #ff7e31, #ff4a63 40%, #35d7ff 100%);
  border: 2px solid rgba(6, 12, 28, 0.95);
  border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #ff9847, #ff6288 44%, #6ce8ff 100%);
}
</style>
