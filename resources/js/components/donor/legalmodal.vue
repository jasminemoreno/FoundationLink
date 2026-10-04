<template>
    <Teleport to="body">
      <transition name="legal-fade">
        <div v-if="modelValue" class="legal-overlay" @click.self="close">
          <div class="legal-modal">
            <div class="legal-header">
              <div class="legal-heading">
                <h2>{{ title }}</h2>
                <p class="legal-updated">Last updated: {{ lastUpdated }}</p>
              </div>
              <button class="legal-close" type="button" @click="close" aria-label="Close">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
  
            <div class="legal-body">
              <section v-for="(section, i) in sections" :key="i" class="legal-section">
                <h3>{{ section.heading }}</h3>
                <p>
                  {{ section.body }}
                  <a v-if="section.link" :href="section.link.href">{{ section.link.text }}</a>
                </p>
              </section>
            </div>
  
            <div class="legal-footer">
              <button class="legal-ok" type="button" @click="close">Close</button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  defineProps({
    modelValue: { type: Boolean, default: false },
    title:      { type: String, required: true },
    sections:   { type: Array, required: true },
  })
  
  const emit = defineEmits(['update:modelValue'])
  
  function close() {
    emit('update:modelValue', false)
  }
  
  const lastUpdated = computed(() =>
    new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
  )
  </script>
  
  <style scoped>
  .legal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5, 48, 28, 0.45);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 1000;
  }
  
  .legal-modal {
    width: min(560px, 100%);
    max-height: 82vh;
    background: #fff;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(0, 80, 40, 0.25);
    font-family: 'Nunito', sans-serif;
  }
  
  .legal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 24px;
    border-bottom: 1.5px solid #ddeee6;
    flex-shrink: 0;
  }
  .legal-heading h2 {
    font-size: 1.15rem;
    font-weight: 900;
    color: #0e5c36;
    margin-bottom: 4px;
  }
  .legal-updated {
    font-size: 0.78rem;
    color: #5b7568;
  }
  .legal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: #f0faf5;
    border-radius: 8px;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
  }
  .legal-close:hover { background: #e4f7ed; color: #1a8a52; }
  
  .legal-body {
    padding: 20px 24px;
    overflow-y: auto;
  }
  .legal-section { margin-bottom: 18px; }
  .legal-section:last-child { margin-bottom: 0; }
  .legal-section h3 {
    font-size: 0.88rem;
    font-weight: 800;
    color: #0F2D52;
    margin-bottom: 6px;
  }
  .legal-section p {
    font-size: 0.85rem;
    line-height: 1.65;
    color: #475569;
  }
  .legal-section a {
    color: #1a8a52;
    font-weight: 700;
    text-decoration: none;
  }
  .legal-section a:hover { text-decoration: underline; }
  
  .legal-footer {
    padding: 16px 24px;
    border-top: 1.5px solid #ddeee6;
    flex-shrink: 0;
  }
  .legal-ok {
    width: 100%;
    height: 44px;
    background: #1a8a52;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 800;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    transition: background 0.2s;
  }
  .legal-ok:hover { background: #157042; }
  
  .legal-fade-enter-active, .legal-fade-leave-active { transition: opacity 0.2s ease; }
  .legal-fade-enter-from, .legal-fade-leave-to { opacity: 0; }
  </style>