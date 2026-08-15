<template>
    <div class="donor-stat-card">
      <div class="dsc-icon" :style="{ background: bg, color: col }">
        <span v-html="icon" />
      </div>
      <div class="dsc-body">
        <div class="dsc-val">{{ value }}</div>
        <div class="dsc-label">{{ label }}</div>
      </div>
    </div>
  </template>
  
  <script setup>
  // Note: this is separate from the superadmin statcard.vue (different theme,
  // no progress bar / border-accent variants — donor pages never used those).
  
  defineProps({
    icon:  { type: String, default: '' }, // raw svg string
    value: { type: [String, Number], required: true },
    label: { type: String, required: true },
    bg:    { type: String, default: '#f0fdf4' },
    col:   { type: String, default: '#1a8a52' },
  })
  </script>
  
  <style scoped>
  .donor-stat-card {
    background: white; border-radius: 18px; padding: 20px;
    display: flex; align-items: center; gap: 15px;
    box-shadow: 0 3px 12px rgba(0,80,40,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .donor-stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(0,80,40,0.14); }
  .dsc-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .dsc-val   { font-size: 1.3rem; font-weight: 900; color: #0F2D52; }
  .dsc-label { font-size: 0.78rem; color: #94a3b8; font-weight: 600; margin-top: 1px; }
  </style>