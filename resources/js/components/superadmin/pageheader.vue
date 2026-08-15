<template>
    <div class="page-header">
      <div class="left">
        <h2>{{ title }}</h2>
        <p v-if="subtitle || $slots.default">
          <slot>{{ subtitle }}</slot>
        </p>
      </div>
      <div class="right" v-if="$slots.actions">
        <slot name="actions" />
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' }, // or use the default slot for dynamic content (e.g. "{{ n }} total campaigns")
  })
  </script>
  
  <style scoped>
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 14px;
  }
  .left h2 { font-size: 1.15rem; color: #0F2D52; margin: 0 0 4px; }
  .left p  { font-size: 0.83rem; color: #94a3b8; margin: 0; }
  .right   { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  </style>