<template>
  <div class="register-wrapper">

    <!-- BACKGROUND LAYER (LEFT CONTENT FLOATING) -->
    <div class="bg-layer">

      <div class="logo-wrap">
        <img src="@/assets/img/logo.png" class="logo-img" />

        <div class="logo-text">
          <div class="logo-foundation">
            Foundation<span class="logo-link">Link</span>
          </div>
          <div class="logo-tagline">Bridging Hearts, Building Hope</div>
        </div>
      </div>

      <div class="hero-text">
        <div class="hero-line1">Empower Change.</div>
        <div class="hero-line2">Transform Lives</div>
      </div>

      <div class="features">
        <slot name="features"></slot>
      </div>

    </div>

    <!-- RIGHT FORM AREA -->
    <div class="right-panel">

      <div class="form-card">

        <div v-if="showStepper" class="stepper-container">
          <slot name="stepper"></slot>
        </div>

        <slot></slot>

      </div>

    </div>

  </div>
</template>

<script setup>
defineProps({
  showStepper: { type: Boolean, default: true }
})
</script>
  
  <style scoped>
 *,
*::before,
*::after {
  box-sizing: border-box;
}

.register-wrapper {
  display: flex;
  min-height: 100vh;
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f3f6fb;
  position: relative;
}

/* =========================
   BACKGROUND CONTENT (LEFT FLOATING)
========================= */

.bg-layer {
  position: absolute;
  left: 18%;
  top: 50%;
  transform: translateY(-50%);
  width: 40%;
  opacity: 0.95;
  pointer-events: none;
}

/* LOGO */
.logo-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 2rem;
}

.logo-img {
  width: 150px;
  height: 150px;
}

.logo-foundation {
  font-size: 1.3rem;
  font-weight: 800;
  color: #0d47a1;
}

.logo-link {
  color: #1b6fbf;
}

.logo-tagline {
  font-size: 0.75rem;
  color: #666;
}

/* HERO */
.hero-text {
  margin-bottom: 2rem;
}

.hero-line1 {
  font-size: 2rem;
  font-weight: 800;
  color: #1a1a2e;
}

.hero-line2 {
  font-size: 2rem;
  font-weight: 800;
  color: #1b6fbf;
}

/* FEATURES */
.features {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* =========================
   RIGHT PANEL (FORM)
========================= */

.right-panel {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
  margin-left: 35%;
}

.form-card {
  width: 100%;
  max-width: 520px;
  background: white;
  border-radius: 18px;
  padding: 2.5rem;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* =========================
   STEPPER
========================= */

.stepper-container {
  margin-bottom: 2rem;
}

/* =========================
   FEATURE SLOT STYLE
========================= */

:deep(.feature-item) {
  text-align: left;
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

:deep(.feature-icon) {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: rgba(13, 71, 161, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0d47a1;
}

:deep(.feature-title) {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1a1a2e;
}

:deep(.feature-desc) {
  font-size: 0.75rem;
  color: #666;
}

/* =========================
   RESPONSIVE FIX
========================= */

@media (max-width: 768px) {
  .register-wrapper {
    flex-direction: column;
  }

  .bg-layer {
    position: relative;
    left: 0;
    top: 0;
    transform: none;
    width: 100%;
    padding: 2rem;
  }

  .right-panel {
    margin-left: 0;
    padding: 1rem;
  }

  .form-card {
    padding: 2rem;
  }

  }
  </style>