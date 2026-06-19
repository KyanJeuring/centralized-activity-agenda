<script setup>
import { ref, reactive } from "vue";

const form = reactive({
  name: "",
  email: "",
  organisation: "",
  message: "",
  intent: "",
});

const errors = reactive({});
const submitted = ref(false);
const isSubmitting = ref(false);

function validate() {
  // clear old errors first
  delete errors.name;
  delete errors.email;
  delete errors.organisation;
  delete errors.message;

  // ----- checks -----
  if (!form.name.trim()) errors.name = "Name is required.";

  if (!form.email.trim()) errors.email = "Email is required";
  else if (!form.email.includes("@") || !form.email.includes("."))
    errors.email = "Please enter a valid email address.";

  if (!form.message.trim()) errors.message = "Please write a short message.";

  if (!form.intent) errors.intent = "Please select an intent"
}

async function handleSubmit() {
  validate()
  if (Object.keys(errors).length > 0) return

  isSubmitting.value = true

  try {
    const API_BASE =  import.meta.env.VITE_API_BASE ||"http://localhost:8000/api/v1"
    const response = await fetch(`${API_BASE}/contact`, {
      method: "POST",
      headers: {  "Content-Type": "application/json" },
      body: JSON.stringify(form),
    })

    if (!response.ok) throw new Error(`Error: ${response.status}`)

    submitted.value = true
    form.name = ""
    form.email = ""
    form.organisation = ""
    form.message = ""
    form.intent = ""
  } catch (err) {
    errors.submit = "Something went wrong. Please try again."
    console.error("[contact form]", err)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="info-page">
    <section class="hero-section">
      <div class="hero-inner">
        <h1 class="hero-title">Add your events</h1>
        <p class="hero-sub">
          We gather tech, business and social events across the Northern
          Netherlands and here's how you can get your events listed on our
          website.
        </p>
      </div>
    </section>

    <div class="page-inner">
      <section class="content-card">
        <div class="method-badge api-badge">Recommended method</div>
        <h2 class="card-heading">Method #1: API Integration</h2>
        <p class="card-body-text">
          The API integration gives us structured, reliable data directly from
          your system as it is more accurate, easier to maintain and less likely
          to break when your website changes.
        </p>
        <ul class="feature-list">
          <li>Most reliable and accurate event information</li>
          <li>Easy to automate</li>
          <li>Best long-term solution</li>
        </ul>
      </section>

      <section class="content-card">
        <div class="method-badge scraping-badge">Alternative</div>
        <h2 class="card-heading">Method #2: Web Scraping</h2>
        <p class="card-body-text">
          Web scraping means that we read your website and extracts event
          information from the page content. It requires no technical setup on
          your end, but it is more fragile and if your website's layout or HTML
          structure changes, the scraper may gather the wrong information.
        </p>
        <ul class="feature-list">
          <li>No technical setup required on your side</li>
          <li>More fragile as it is affected by website changes</li>
          <li>More likely to show unreliable event information</li>
        </ul>
      </section>

      <section class="content-card">
        <h2 class="card-heading">Contact Us</h2>
        <p class="card-body-text">
          Tell us how you'd like to add your events and we'll reach out with the
          next steps.
        </p>

        <div v-if="submitted" class="success-message">
          <span class="success-icon">✓</span>
          <div>
            <p class="success-title">Message sent!</p>
            <p class="success-sub">
              We'll get back to you as soon as possible.
            </p>
          </div>
        </div>

        <!-- @submit.prevent intercepts the browser's native form submit and calls the handleSubmit() function instead.
         Without .prevent the page would reload. -->
        <form
          v-else
          @submit.prevent="handleSubmit"
          class="contact-form"
          novalidate
        >
          <!-- Name -->
          <div class="form-group">
            <label for="name">Your name <span class="required">*</span></label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              :class="{ 'input-error': errors.name }"
              placeholder="Your name"
            />
            <p v-if="errors.name" class="error-msg">{{ errors.name }}</p>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="email"
              >Email address <span class="required">*</span></label
            >
            <input
              id="email"
              v-model="form.email"
              type="email"
              :class="{ 'input-error': errors.email }"
              placeholder="email@example.com"
            />
            <p v-if="errors.email" class="error-msg">{{ errors.email }}</p>
          </div>

          <!-- Organisation URL -->
          <div class="form-group">
            <label for="organisation">Organisation's URL</label>
            <input
              id="organisation"
              v-model="form.organisation"
              type="url"
              placeholder="https://www.yourclub.com/events"
            />
          </div>

          <!-- Intent -->
          <div class="form-group">
            <label for="intent">Intent <span class="required">*</span></label>
            <select
              id="intent"
              v-model="form.intent"
              :class="{ 'input-error': errors.intent }"
            >
              <option value="" disabled>Select an option...</option>
              <option value="register">Register for API</option>
              <option value="scraping">Register for Scraping</option>
              <option value="switch">Request to switch</option>
            </select>
            <span v-if="errors.intent" class="error-msg">{{
              errors.intent
            }}</span>
          </div>

          <!-- Message -->
          <div class="form-group">
            <label for="message">Message <span class="required">*</span></label>
            <textarea
              id="message"
              v-model="form.message"
              rows="5"
              :class="{ 'input-error': errors.message }"
              placeholder="Tell us which method you prefer and how we can help..."
            ></textarea>
            <p v-if="errors.message" class="error-msg">{{ errors.message }}</p>
          </div>

          <!-- Submit Button -->
          <span v-if="errors.submit" class="error-msg">{{
            errors.submit
          }}</span>
          <button type="submit" class="submit-btn" :disabled="isSubmitting">
            {{ isSubmitting ? "Sending..." : "Send message" }}
          </button>
        </form>
      </section>
    </div>
  </div>
</template>

<style scoped>
.info-page {
  min-height: calc(100vh - 70px);
  background-color: #f3f4f6;
}

.hero-section {
  padding: 3rem 1.5rem;
}

.hero-inner {
  max-width: 800px;
  margin: 0 auto;
}

.hero-title {
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
}

.hero-sub {
  font-size: 1rem;
  opacity: 0.8;
  line-height: 1.7;
}

.page-inner {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1.5rem 3rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.content-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 1.75rem;
  position: relative;
}

.method-badge {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 3px 10px;
  border-radius: 999px;
  margin-bottom: 0.85rem;
}

.api-badge {
  background: #dcfce7;
  color: #15803d;
}

.scraping-badge {
  background: #fef9c3;
  color: #92400e;
}

.card-heading {
  font-size: 1.3rem;
  font-weight: 800;
  color: #111827;
  margin-bottom: 0.75rem;
}

.card-body-text {
  color: #4b5563;
  line-height: 1.75;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}

.feature-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding: 0;
}

.feature-list li {
  font-size: 0.9rem;
  color: #374151;
}

.contact-form {
  margin-top: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.required {
  color: #ef4444;
  margin-left: 2px;
}

.form-group input,
.form-group textarea {
  padding: 0.7rem 0.9rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 9px;
  font-size: 0.9rem;
  color: #111827;
  outline: none;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
  font-family: inherit;
  resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #1b3a6b;
  box-shadow: 0 0 0 3px rgba(27, 58, 107, 0.08);
}

.form-group select {
  padding: 0.7rem 0.9rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 9px;
  font-size: 0.9rem;
  color: #111827;
  outline: none;
  background: white;
  cursor: pointer;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
  font-family: inherit;
  appearance: none;
  background-image:
    linear-gradient(45deg, transparent 50%, #9ca3af 50%),
    linear-gradient(135deg, #9ca3af 50%, transparent 50%);
  background-position:
    calc(100% - 16px) calc(50% - 2px),
    calc(100% - 11px) calc(50% - 2px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
}

.form-group select:focus {
  border-color: #1B3A6B;
  box-shadow: 0 0 0 3px rgba(27, 58, 107, 0.08);
}

.input-error {
  border-color: #ef4444 !important;
}

.error-msg {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.1rem;
}

.submit-btn {
  align-self: flex-start;
  background-color: #1b3a6b;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 9px;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  transition:
    background 0.2s,
    opacity 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background-color: #14305a;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.success-message {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.2rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  margin-top: 1rem;
}

.success-icon {
  background: #16a34a;
  color: white;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  flex-shrink: 0;
}

.success-title {
  font-weight: 700;
  color: #15803d;
  font-size: 0.95rem;
}

.success-sub {
  color: #4b5563;
  font-size: 0.85rem;
  margin-top: 0.15rem;
}

@media (max-width: 600px) {
  .hero-title {
    font-size: 1.5rem;
  }
}
</style>
