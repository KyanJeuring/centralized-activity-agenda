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

// Format the date to weekday, day and month
const formattedDate = computed(() => {
  const d = new Date(props.event.start_date);
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
          {{ event.organizer.charAt(0) }}
        </div>
        <span class="organiser-name">{{ event.organizer }}</span>
      </div>

      <h3 class="card-name">{{ event.name }}</h3>

      <div class="card-meta">
        <span class="meta-item">
          {{ formattedDate }}
        </span>
        <span class="meta-item">{{ event.location }}</span>
      </div>

      <div class="card-actions">
        <button class="view-btn" @click="goToDetail">See More</button>
        <a
          :href="event.url"
          target="_blank"
          rel="noopener noreferrer"
          class="external-link"
          title="Open original source"
          ><svg
            xmlns="http://www.w3.org/2000/svg"
            width="16"
            height="16"
            fill="currentColor"
            class="bi bi-arrow-up-right-square"
            viewBox="0 0 16 16"
          >
            <path
              fill-rule="evenodd"
              d="M15 2a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1zM0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm5.854 8.803a.5.5 0 1 1-.708-.707L9.243 6H6.475a.5.5 0 1 1 0-1h3.975a.5.5 0 0 1 .5.5v3.975a.5.5 0 1 1-1 0V6.707z"
            /></svg
        ></a>
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
  height: 200px;
  overflow: hidden;
  object-fit: cover;
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
