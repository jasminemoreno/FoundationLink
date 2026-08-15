<template>
    <div class="fa-empty-state" :class="{ compact }">
      <Spinner v-if="loading" :size="32" />
      <div v-else-if="icon" class="fa-empty-icon">{{ icon }}</div>
      <p v-if="message">{{ message }}</p>
      <slot />
    </div>
  </template>
  
  <script setup>
  // Covers both patterns seen across every page:
  //   loading:  <EmptyState loading message="Loading campaigns..." />
  //   empty:    <EmptyState icon="📋" message="No campaigns found." />
  // `compact` shrinks padding (32px) for use inside panels (e.g. Dashboard's
  // Recent Donations / Campaign Progress cards); default padding is 60px for
  // full-page empty states.
  
  import Spinner from './spinner.vue'
  
  defineProps({
    loading: { type: Boolean, default: false },
    icon:    { type: String,  default: '' },
    message: { type: String,  default: '' },
    compact: { type: Boolean, default: false },
  })
  </script>
  
  <style scoped>
  .fa-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px;
    color: #94a3b8;
    gap: 10px;
    text-align: center;
    font-size: 0.85rem;
  }
  .fa-empty-state.compact { padding: 32px; }
  .fa-empty-icon { font-size: 2.5rem; }
  </style>