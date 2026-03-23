<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import {
  categoryColors,
  fallbackCategoryColor,
} from "../data/categoryColors.js";

const props = defineProps({
  event: {
    type: Object,
    rewuired: true,
  },
});

const router = useRouter();

function darkenHex(hex, amount = 0.15) {
  const cleanHex = hex.replace("#", "");
  if (cleanHex.length !== 6) return hex;

  const r = parseInt(cleanHex.slice(0, 2), 16);
  const g = parseInt(cleanHex.slice(2, 4), 16);
  const b = parseInt(cleanHex.slice(4, 6), 16);

  const darken = (channel) =>
    Math.max(0, Math.min(255, Math.round(channel * (1 - amount))));

  const toHex = (channel) => darken(channel).toString(16).padStart(2, "0");
  return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
}

const categoryColour = computed(
  () => categoryColors[props.event.category] || fallbackCategoryColor,
);
const badgeColour = computed(() => darkenHex(categoryColour.value));

// Format the date to weekday, day and month
const formattedDate = computed(() => {
  const d = new Date(props.event.date);
  const weekday = d.toLocaleDateString("nl-NL", { weekday: "long" });
  const day = d.getDate();
  const month = d.toLocaleDateString("nl-NL", { month: "long" });

  return `${weekday} ${day} ${month}`;
});

function goToDetail() {
  router.push({ name: "EventDetail", params: { id: props.event.id } });
}
</script>

<template>
  <div class="event-card">
    <div class="card-image" :style="{ backgroundColor: categoryColour }">
      <span class="category-badge" :style="{ backgroundColor: badgeColour }">
        {{ event.category }}
      </span>
    </div>

    <div class="card-body">
      <div class="organiser-row">
        <div class="organiser-avatar" :style="{ backgroundColor: event.color }">
          {{ event.organiser.charAt(0) }}
        </div>
        <span class="organiser-name">{{ event.organiser }}</span>
      </div>

      <h3 class="card-title">{{ event.title }}</h3>

      <div class="card-meta">
        <span class="meta-item">
          {{ formattedDate }} . {{ event.startTime }} -
          {{ event.endTime }}
        </span>
        <span class="meta-item">{{ event.location }}</span>
      </div>

      <div class="card-actions">
        <button class="view-btn" @click="goToDetail">See More</button>
        <a
          :href="event.originalSource"
          target="_blank"
          rel="noopener noreferrer"
          class="external-link"
          title="Open original source"
          >&nearr;</a
        >
      </div>
    </div>
  </div>
</template>

<style scoped>
.event-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  transition:
    transform 0.2s,
    box-shadow 0.2s;
}

.event-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.card-image {
  position: relative;
  height: 170px;
  overflow: hidden;
}

.category-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  color: white;
  font-size: 11px;
  padding: 2px 10px;
  border-radius: 20px;
  font-weight: 700;
  text-transform: capitalize;
}
.card-body {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  flex: 1;
}
.organiser-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.organiser-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}
.organiser-name {
  font-size: 0.8rem;
  color: #6b7280;
}
.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #111827;
  line-height: 1.35;
}
.card-meta {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.meta-item {
  font-size: 0.8rem;
  color: #6b7280;
}
.card-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 0.4rem;
}
.view-btn {
  flex: 1;
  background-color: #1b3a6b;
  color: white;
  border: none;
  padding: 0.65rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.view-btn:hover {
  background-color: #14305a;
}
.external-link {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  color: #374151;
  transition:
    border-color 0.2s,
    color 0.2s;
  text-decoration: none;
}
.external-link:hover {
  border-color: #1b3a6b;
  color: #1b3a6b;
}
</style>
