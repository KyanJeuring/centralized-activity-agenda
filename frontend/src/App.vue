<script setup>
import { onMounted, onUnmounted } from "vue";
import Navbar from "./components/Navbar.vue";
import Footer from "./components/Footer.vue";
import FloatingInfoButton from "./components/FloatingInfoButton.vue";
import { useEvents } from "./composables/useEvents.js";

const { fetchEvents, startAutoRefresh } = useEvents();
let stopAutoRefresh = null;

onMounted(() => {
  fetchEvents();
  stopAutoRefresh = startAutoRefresh();
});

onUnmounted(() => {
  stopAutoRefresh?.();
});
</script>

<template>
  <Navbar />
  <main class="main-content">
    <router-view />
  </main>
  <Footer />
  <FloatingInfoButton />
</template>
