<script setup>
import { ref } from "vue";
import Navbar from './components/Navbar.vue';
import Footer from './components/Footer.vue';
import FloatingInfoButton from "./components/FloatingInfoButton.vue";

const footerElement = ref(null)
</script>

<template>
  <Navbar />
  <main class="main-content">
    <router-view />
  </main>
  <Footer ref="footerElement" />
  <FloatingInfoButton :footerRef="footerElement" />
</template>
