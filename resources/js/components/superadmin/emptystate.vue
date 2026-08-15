<template>
    <div class="empty-state">
      <Spinner v-if="loading" :size="32" :color="spinnerColor" />
      <div v-else-if="icon" class="empty-icon">{{ icon }}</div>
      <p>{{ loading ? loadingMessage : message }}</p>
      <slot name="action" />
    </div>
  </template>
  
  <script setup>
  import Spinner from './Spinner.vue'
  
  defineProps({
    loading: { type: Boolean, default: false },
    message: { type: String, default: 'Nothing here yet.' },
    loadingMessage: { type: String, default: 'Loading…' },
    icon: { type: String, default: '' }, // optional emoji, e.g. '💳'
    spinnerColor: { type: String, default: '#0F2D52' },
  })
  </script>
  
  <style scoped>
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
    font-size: 0.88rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }
  .empty-icon { font-size: 2.5rem; }
  .empty-state p { margin: 0; }
  </style>