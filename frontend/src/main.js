import { createApp } from "vue";

import App from "./App.vue";
import router from "./router";
import { useAuthStore } from "@/stores/authStore";
import { pinia } from "@/stores/pinia";

const app = createApp(App);

app.use(pinia);
app.use(router);

document.addEventListener("contextmenu", function (e) {
  e.preventDefault();
});

const authStore = useAuthStore(pinia);

authStore.bootstrap().finally(() => {
  app.mount("#app");
});
