<script setup>
import { ref } from "vue";

const backendMessage = ref("");
const isLoading = ref(false);

async function testBackend() {
  isLoading.value = true;
  backendMessage.value = "Checking backend...";

  try {
    const response = await fetch("http://localhost:8000", { method: "GET" });
    const data = await response.json();
    const message = data.message || "No message field in response.";
    backendMessage.value = `Backend response: ${message}`;
  } catch {
    backendMessage.value = "Backend request failed. Check if backend container is running on port 8000.";
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <main class="page">
    <section class="card">
      <h1>Vue Test Page</h1>
      <p>If you see this page, the Vue frontend is running inside Docker.</p>

      <button type="button" :disabled="isLoading" @click="testBackend">
        {{ isLoading ? "Checking backend..." : "Test Backend" }}
      </button>

      <p>{{ backendMessage }}</p>
    </section>
  </main>
</template>
