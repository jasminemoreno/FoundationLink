<template>
    <div class="stat-card" :class="layout" :style="borderStyle">
      <div class="stat-icon" :style="{ background: bg, color: col }">
        <span v-html="icon"></span>
      </div>
  
      <div class="stat-body">
        <div class="stat-value">{{ value }}</div>
        <div class="stat-label">{{ label }}</div>
        <div v-if="sub" class="stat-sub">{{ sub }}</div>
      </div>
  
      <div v-if="change !== null" class="stat-change" :class="up ? 'up' : 'down'">
        {{ up ? '▲' : '▼' }} {{ change }}
      </div>
  
      <div v-if="progress !== null" class="stat-bar">
        <div class="stat-bar-fill" :style="{ width: progress + '%', background: col }"></div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    icon: { type: String, default: '' },        // raw svg string, same as before
    value: { type: [String, Number], required: true },
    label: { type: String, required: true },
    sub: { type: String, default: '' },
    bg: { type: String, default: '#eff6ff' },
    col: { type: String, default: '#3b82f6' },
    progress: { type: Number, default: null },   // 0–100, dashboard-style progress bar
    borderColor: { type: String, default: '' },  // dashboard-style top border accent
    change: { type: String, default: null },     // reports-style "+X this period" badge
    up: { type: Boolean, default: true },
    // 'vertical'   = Dashboard style (icon top, big value, optional border+progress)
    // 'horizontal' = Users / Payment Methods / Reports style (icon left, value/label right)
    layout: { type: String, default: 'vertical' },
  })
  
  const borderStyle = computed(() =>
    props.borderColor ? { borderTopColor: props.borderColor } : {}
  )
  </script>
  
  <style scoped>
  .stat-card {
    background: white;
    border-radius: 14px;
    box-shadow: 0 1px 4px rgba(15,45,82,0.06);
  }
  
  /* ── Vertical (Dashboard) ── */
  .stat-card.vertical {
    padding: 22px;
    border-top: 4px solid transparent;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .stat-card.vertical:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(15,45,82,0.1); }
  .stat-card.vertical .stat-icon { margin-bottom: 14px; }
  .stat-card.vertical .stat-value { font-size: 1.9rem; font-weight: 800; color: #0F2D52; line-height: 1; margin-bottom: 4px; }
  .stat-card.vertical .stat-label { font-size: 0.82rem; color: #64748b; font-weight: 500; margin-bottom: 2px; }
  .stat-card.vertical .stat-sub { font-size: 0.75rem; color: #94a3b8; margin-bottom: 12px; }
  .stat-card.vertical .stat-bar { height: 5px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
  .stat-card.vertical .stat-bar-fill { height: 100%; border-radius: 99px; transition: width 1s ease; }
  .stat-card.vertical .stat-change { display: none; } /* dashboard cards don't show a change badge */
  
  /* ── Horizontal (Users / Payment Methods / Reports) ── */
  .stat-card.horizontal {
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
  }
  .stat-card.horizontal .stat-body { flex: 1; min-width: 0; }
  .stat-card.horizontal .stat-value { font-size: 1.2rem; font-weight: 800; color: #0F2D52; }
  .stat-card.horizontal .stat-label { font-size: 0.78rem; color: #94a3b8; }
  .stat-card.horizontal .stat-sub { font-size: 0.72rem; color: #94a3b8; }
  .stat-card.horizontal .stat-bar { width: 100%; height: 5px; background: #f1f5f9; border-radius: 99px; overflow: hidden; order: 4; margin-top: 4px; }
  .stat-card.horizontal .stat-bar-fill { height: 100%; border-radius: 99px; }
  
  /* Shared */
  .stat-icon { width: 42px; height: 42px; min-width: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
  
  .stat-change { font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 20px; white-space: nowrap; }
  .stat-change.up   { background: #f0fdf4; color: #059669; }
  .stat-change.down { background: #fef2f2; color: #dc2626; }
  </style>