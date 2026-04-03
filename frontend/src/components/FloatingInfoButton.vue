<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";

const baseBottom = 24;
const footerGap = 24;
const isVisible = ref(true);
const bottomOffset = ref(baseBottom);

let observer = null;
let animationFrameId = 0;

function updateButtonOffset() {
  const footerElement = document.querySelector("footer");

  if (!footerElement) {
    bottomOffset.value = baseBottom;
    return;
  }

  const footerRect = footerElement.getBoundingClientRect();
  const visibleFooterHeight = Math.max(0, window.innerHeight - footerRect.top);
  bottomOffset.value = Math.max(baseBottom, visibleFooterHeight + footerGap);
}

function scheduleUpdate() {
  if (animationFrameId) return;

  animationFrameId = window.requestAnimationFrame(() => {
    animationFrameId = 0;
    updateButtonOffset();
  });
}

onMounted(() => {
  updateButtonOffset();
  window.addEventListener("scroll", scheduleUpdate, { passive: true });
  window.addEventListener("resize", scheduleUpdate);

  observer = new MutationObserver(() => scheduleUpdate());
  observer.observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
  });
});

onUnmounted(() => {
  window.removeEventListener("scroll", scheduleUpdate);
  window.removeEventListener("resize", scheduleUpdate);
  observer?.disconnect();
  if (animationFrameId) {
    window.cancelAnimationFrame(animationFrameId);
  }
});

const fabStyle = computed(() => ({
  bottom: `${bottomOffset.value}px`,
}));
</script>

<template>
  <Transition name="fab-fade">
    <router-link
      v-show="isVisible"
      :to="{ name: 'Information' }"
      class="fab"
      :style="fabStyle"
      title="Add your events to our platform"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        fill="currentColor"
        viewBox="0 0 16 16"
        aria-hidden="true"
      >
        <path
          d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"
        />
        <path
          d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"
        />
      </svg>
      <span class="fab-label">Want to add your events?</span>
    </router-link>
  </Transition>
</template>

<style scoped>
.fab {
  position: fixed;
  right: 2rem;
  z-index: 200;

  display: inline-flex;
  align-items: center;
  gap: 0.5rem;

  background-color: #1b3a6b;
  color: white;
  font-weight: 700;
  font-size: 0.875rem;
  padding: 0.7rem 1.2rem;
  border-radius: 999px;
  box-shadow: 0 4px 14px rgba(27, 58, 107, 0.45);
  text-decoration: none;

  transition:
    background 0.2s,
    transform 0.15s,
    box-shadow 0.2s;
}

.fab:hover {
  background-color: #14305a;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(27, 58, 107, 0.55);
}

.fab-fade-enter-active,
.fab-fade-leave-active {
  transition:
    opacity 0.25s ease,
    transform 0.25s ease;
}

.fab-fade-enter-from,
.fab-fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@media (max-width: 600px) {
  .fab-label {
    display: none;
  }
  .fab {
    padding: 0.8rem;
    border-radius: 50%;
  }
}
</style>
