<template>
    <div class="donor-page-head">
      <div class="ph-left">
        <span v-if="kicker" class="ph-kicker">{{ kicker }}</span>
        <h1 class="ph-title">{{ title }}</h1>
        <p v-if="subtitle" class="ph-subtitle">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.default" class="ph-right">
        <slot />
      </div>
    </div>
  </template>
  
  <script setup>
  // Usage:
  // <PageHead kicker="Your Impact" title="My Donations" subtitle="Track all your donations">
  //   <!-- optional: stats, buttons, etc. rendered on the right -->
  // </PageHead>
  
  defineProps({
    kicker:   { type: String, default: '' },
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
  })
  </script>
  
  <style scoped>
  .donor-page-head {
    display: flex; justify-content: space-between; align-items: flex-end;
    margin-bottom: 24px; gap: 20px; flex-wrap: wrap;
  }
  .ph-kicker {
    display: inline-block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.1em; color: #1a8a52; background: #e6f7ee; padding: 4px 12px;
    border-radius: 20px; margin-bottom: 8px;
  }
  .ph-title { font-size: 1.6rem; font-weight: 900; color: #0e5c36; margin: 0 0 5px; line-height: 1.15; }
  .ph-subtitle { font-size: 0.87rem; color: #475569; margin: 0; }
  .ph-right { display: flex; gap: 16px; flex-shrink: 0; }
  </style>