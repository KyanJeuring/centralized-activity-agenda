<script setup>
import { ref } from "vue";

const footerRoot = ref(null);
const year = new Date().getFullYear();

defineExpose({ $el: footerRoot });
</script>

<template>
  <footer class="footer" ref="footerRoot">
    <div class="footer-inner">
      <span class="footer-title">Centralized Activity Agenda</span>
      <router-link :to="{ name: 'Information' }" class="footer-cta">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          fill="currentColor"
          class="bi bi-calendar-plus"
          viewBox="0 0 16 16"
        >
          <path
            d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7"
          />
          <path
            d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"
          />
        </svg>
        Want to add your events?
      </router-link>
    </div>
    <span class="footer-sub">Upcoming events in the North of Netherlands</span>
    <p class="footer-copy">&copy; {{ year }} IT-HUB</p>
  </footer>
</template>

<style scoped>
.footer {
  background-color: #1b3a6b;
  color: white;
  padding: 1.25rem 1.5rem;
  width: 100%;
}

.footer-inner {
  max-width: 2400px;
  margin: 0 auto;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.footer-brand {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.footer-title {
  font-size: 0.95rem;
  font-weight: 700;
}

.footer-sub {
  font-size: 0.75rem;
  opacity: 0.6;
}

.footer-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background-color: white;
  color: #1b3a6b;
  font-weight: 700;
  font-size: 0.875rem;
  padding: 0.55rem 1.1rem;
  border-radius: 999px;
  text-decoration: none;
  transition:
    background 0.2s,
    transform 0.15s;
  white-space: nowrap;
}

.footer-cta:hover {
  background-color: #e8edf5;
  transform: translateY(-1px);
}

.footer-copy {
  font-size: 0.78rem;
  opacity: 0.55;
  padding: 15px 0 0 0;
}

@media (max-width: 600px) {
  .fooetr-inner {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
