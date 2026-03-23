<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();

const isDetailPage = computed(() => route.name === "EventDetail"); // re-evaluates reactively whenever the URL changes
</script>

<template>
  <nav class="navbar">
    <div class="navbar-inner">
      <router-link to="/" class="navbar-brand">
        <span class="navbar-title">Centralised Activity Agenda</span>
        <div class="navbar-subtitle">
          Tech, Business &amp; Social Events across Groningen, Friesland &amp;
          Drenthe</div>
      </router-link>

      <div class="navbar-actions">
        <router-link v-if="isDetailPage" to="/" class="back-link">
         &larr; Back to Events
        </router-link>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.navbar {
  background-color: #1B3A6B;
  color: white;
  padding: 0 2rem;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.navbar-inner {
  max-width: 1280px;
  margin: 0 auto;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 0;
}

.navbar-brand {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.15rem;
  text-decoration: none;
  color: white;
}

.navbar-title {
  font-size: 1.35rem;
  font-weight: 700;
}

.navbar-subtitle {
  font-size: 0.78rem;
  opacity: 0.7;
}

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.back-link {
  color: white;
  font-size: 0.875rem;
  opacity: 0.85;
  text-decoration: none;
  transition: opacity 0.2s;
}

.back-link:hover {
  opacity: 1;
}

.admin-btn {
  background: transparent;
  border: 1.5px solid #ffea00;
  color: white;
  padding: 0.4rem 0.9rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.82rem;
  transition: background 0.2s;
}

.admin-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

@media (max-width: 600px) {
  .navbar-subtitle {
    display: none;
  }
  .navbar-title {
    font-size: 1.05rem;
  }
}
</style>
