<script setup>
import { ref, computed } from "vue";
import { events } from "../data/events.js";
import EventCard from "../components/EventCard.vue";

const searchQuery = ref("");
const selectedCity = ref("");
const selectedCategory = ref("");
const selectedDateRange = ref("");

const cities = computed(() =>
  [...new Set(events.map((e) => e.location))].sort(),
);
const categories = computed(() =>
  [...new Set(events.map((e) => e.category))].sort(),
);

const filteredEvents = computed(() => {
  const q = searchQuery.value.toLowerCase();
  return events.filter((event) => {
    4;
    const matchesSearch =
      !q ||
      event.title.toLowerCase().includes(q) ||
      event.organiser.toLowerCase().includes(q) ||
      event.location.toLowerCase().includes(q);
    const matchesCity =
      !selectedCity.value || event.location === selectedCity.value;
    const matchesCategory =
      !selectedCategory.value || event.category === selectedCategory.value;

    return matchesSearch && matchesCity && matchesCategory;
  });
});

function resetFilters() {
  searchQuery.value = "";
  selectedCity.value = "";
  selectedCategory.value = "";
}
</script>

<template>
  <div class="home">
    <div class="filter-bar">
      <div class="filter-inner">
        <div class="search-input-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M10 4a6 6 0 104.472 10.001l4.263 4.264a1 1 0 001.414-1.415l-4.263-4.263A6 6 0 0010 4zm0 2a4 4 0 110 8 4 4 0 010-8z"
              fill="currentColor"
            />
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search events..."
          />
        </div>
        <select v-model="selectedCity" class="filter-select">
          <option value="">All cities</option>
          <option v-for="city in cities" :key="city" :value="city">
            {{ city }}
          </option>
        </select>
        <select v-model="selectedCategory" class="filter-select">
          <option value="">All categories</option>
          <option
            v-for="category in categories"
            :key="category"
            :value="category"
          >
            {{ category }}
          </option>
        </select>
        <select v-model="selectedDate" class="filter-select">
          <option value="">All Dates</option>
        </select>
        <button class="reset-btn" @click="resetFilters">Reset</button>
      </div>
    </div>

    <div class="events-section">
      <div class="events-inner">
        <h2 class="section-title">All Upcoming Events</h2>
        <p v-if="filteredEvents.length === 0" class="no-results">
          No events match your search.
        </p>

        <div class="events-grid">
          <EventCard
            v-for="event in filteredEvents"
            :key="event.id"
            :event="event"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.filter-bar {
  padding: 1.8rem 1.5rem 1.4rem;
}
.filter-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  gap: 0.9rem;
  align-items: center;
  flex-wrap: wrap;
  padding: 1.15rem;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}
.search-input-wrap {
  flex: 1 1 360px;
  min-width: 260px;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0 0.85rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #ebedf0;
}
.search-icon {
  width: 18px;
  height: 18px;
  color: #9ca3af;
  flex-shrink: 0;
}
.search-input {
  width: 100%;
  padding: 0.72rem 0;
  border: none;
  background: transparent;
  font-size: 0.9rem;
  color: #1f2937;
  outline: none;
}
.search-input::placeholder {
  color: #9ca3af;
}
.search-input-wrap:focus-within,
.filter-select:focus,
.reset-btn:focus {
  border-color: #cdd5df;
  box-shadow: 0 0 0 2px rgba(27, 58, 107, 0.08);
}
.filter-select {
  padding: 0.7rem 2rem 0.7rem 0.9rem;
  min-width: 160px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  background: #ebedf0;
  appearance: none;
  background-image:
    linear-gradient(45deg, transparent 50%, #9ca3af 50%),
    linear-gradient(135deg, #9ca3af 50%, transparent 50%);
  background-position:
    calc(100% - 16px) calc(50% - 2px),
    calc(100% - 11px) calc(50% - 2px);
  background-size:
    5px 5px,
    5px 5px;
  background-repeat: no-repeat;
  cursor: pointer;
  outline: none;
  color: #374151;
}
.reset-btn {
  padding: 0.7rem 1.1rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f3f4f6;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  color: #374151;
  transition:
    border-color 0.2s,
    color 0.2s,
    background 0.2s;
  white-space: nowrap;
}
.reset-btn:hover {
  border-color: #cfd6df;
  background: #ebeef2;
}
.events-section {
  padding: 2rem 1.5rem;
}
.events-inner {
  max-width: 1200px;
  margin: 0 auto;
}
.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 1.5rem;
}
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}
.no-results {
  color: #6b7280;
  text-align: center;
  padding: 3rem;
  font-size: 1rem;
  grid-column: 1 / -1;
}

@media (max-width: 640px) {
  .events-grid {
    grid-template-columns: 1fr;
  }
  .filter-inner {
    flex-direction: column;
    align-items: stretch;
  }
  .search-input-wrap,
  .filter-select,
  .reset-btn {
    width: 100%;
  }
}
</style>
