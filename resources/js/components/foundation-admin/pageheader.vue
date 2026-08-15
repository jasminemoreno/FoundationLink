<template>
    <div class="fa-page-header">
      <div class="fa-ph-left">
        <h2>{{ title }}</h2>
        <p v-if="subtitle">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.default" class="fa-ph-right">
        <slot />
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
  })
  </script>
  
  <style scoped>
  .fa-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 14px;
  }
  .fa-ph-left h2 { font-size: 1.15rem; color: #0F2D52; margin: 0 0 4px; }
  .fa-ph-left p  { font-size: 0.83rem; color: #94a3b8; margin: 0; }
  .fa-ph-right   { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  </style>