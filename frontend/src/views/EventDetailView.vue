<script setup>
import { computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { onMounted } from "vue";
import { imgForEvent } from "../utils/eventImage.js";
import { useEvents } from "../composables/useEvents.js";
import { organiserColour } from "../utils/colour.js";

const { events, isLoading, fetchEvents } = useEvents();
onMounted(() => fetchEvents());
const route = useRoute();
const router = useRouter();
const bannerAlt = computed(() => event.value?.title ?? "Event banner");

watch(isLoading, (loading) => {
  if (!loading && !event.value) {
    router.push({ name: "Home" });
  }
});

const event = computed(() =>
  events.value.find((e) => e.id === route.params.id),
);

const bannerImg = computed(() =>
  imgForEvent(event.value?.id, event.value?.img),
);

const organizerInitial = computed(() =>
  (events.value?.organizer ?? "E").charAt(0).toUpperCase(),
);

const organiserColor = computed(() =>
  organiserColour(events.value?.organizer ?? ""),
);

const formattedDate = computed(() => {
  if (!event.value) return "";

  return new Date(event.value.start_date).toLocaleDateString("nl-NL", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
});
</script>

<template>
  <div v-if="event" class="detail-page">
    <div class="banner">
      <img :src="bannerImg" :alt="bannerAlt" class="banner-image" />
      <div class="banner-overlay"></div>
    </div>

    <div class="detail-inner">
      <div class="detail-header">
        <h1 class="event-name">{{ event.title }}</h1>
        <p class="event-description">{{ event.description }}</p>
      </div>

      <div class="info-card">
        <p class="card-label">Event Organiser</p>
        <div class="organiser-info">
          <div
            class="organiser-logo"
            :style="{ backgroundColor: organiserColor }"
          >
            {{ organizerInitial }}
          </div>
          <div>
            <p class="organiser-name">{{ event.organizer }}</p>
            <p class="organiser-location">{{ event.location }}</p>
            <a :href="event.url" target="_blank" class="visit-link">
              Visit website &nearr;
            </a>
          </div>
        </div>
      </div>

      <div class="meta-grid">
        <div class="info-card">
          <p class="card-label">Date &amp; Time</p>
          <p class="info-value">{{ formattedDate }}</p>
        </div>
        <div class="info-card">
          <p class="card-label">Location</p>
          <p class="info-value">{{ event.location }}</p>
        </div>
        <div class="info-card full-width">
          <p class="card-label">Original Source</p>
          <a :href="event.url" target="_blank" class="source-link">
            View on Hub Website
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.detail-page {
  min-height: calc(100vh - 70px);
}
.banner {
  position: relative;
  height: 320px;
  overflow: hidden;
  background-color: #1b3a6b;
}

.banner-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.banner-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(
      180deg,
      rgba(11, 21, 39, 0.14) 0%,
      rgba(11, 21, 39, 0.38) 100%
    ),
    linear-gradient(90deg, rgba(27, 58, 107, 0.35) 0%, rgba(27, 58, 107, 0) 60%);
}

.detail-inner {
  max-width: 860px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.event-name {
  font-size: 1.8rem;
  font-weight: 800;
  color: #111827;
  margin-bottom: 0.6rem;
}
.event-description {
  color: #4b5563;
  line-height: 1.75;
  font-size: 0.95rem;
}
.info-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.2rem 1.4rem;
}
.card-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #6b7280;
  margin-bottom: 0.75rem;
}
.organiser-info {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}
.organiser-logo {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  font-weight: 700;
  flex-shrink: 0;
}
.organiser-name {
  font-weight: 700;
  font-size: 1rem;
  color: #111827;
}
.organiser-location {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.2rem;
}
.visit-link {
  color: #2563eb;
  font-size: 0.85rem;
  text-decoration: none;
  display: inline-block;
  margin-top: 0.35rem;
}
.visit-link:hover {
  text-decoration: underline;
}

.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.full-width {
  grid-column: 1 / -1;
}
.info-value {
  font-weight: 700;
  color: #111827;
  font-size: 0.95rem;
}
.info-sub {
  color: #6b7280;
  font-size: 0.85rem;
  margin-top: 0.2rem;
}
.source-link {
  color: #2563eb;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
}
.source-link:hover {
  text-decoration: underline;
}

@media (max-width: 600px) {
  .meta-grid {
    grid-template-columns: 1fr;
  }
  .full-width {
    grid-column: auto;
  }
  .event-name {
    font-size: 1.35rem;
  }
}
</style>
