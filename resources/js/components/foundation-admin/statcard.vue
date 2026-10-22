<template>
    <div class="fa-stat-card">
      <div class="fa-stat-icon" :style="{ background: bg, color: col, width: iconSize + 'px', height: iconSize + 'px' }">
        <span v-html="icon" />
      </div>
      <div>
        <div class="fa-stat-val">{{ value }}</div>
        <div class="fa-stat-label">{{ label }}</div>
      </div>
    </div>
  </template>
  
  <script setup>
  // iconSize covers the small variance across pages (Dashboard used 44px,
  // Campaigns/Donations/Donors 42px, Reports 40px) without needing 3 near-copies.
  defineProps({
    icon:     { type: String, default: '' },
    value:    { type: [String, Number], required: true },
    label:    { type: String, required: true },
    bg:       { type: String, default: '#e8f0fe' },
    col:      { type: String, default: '#2563eb' },
    iconSize: { type: Number, default: 42 },
  })
  </script>
  
  <style scoped>
  .fa-stat-card {
    background: white;
    border-radius: 14px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(30,58,138,0.06);
  }
  .fa-stat-icon {
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .fa-stat-val   { font-size: 1.2rem; font-weight: 800; color: var(--fa-heading); }
  .fa-stat-label { font-size: 0.78rem; color: var(--fa-muted); margin-top: 2px; }
  </style>