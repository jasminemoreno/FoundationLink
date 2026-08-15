<template>
    <div class="donor-spinner" :style="spinnerStyle" />
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    size:  { type: Number, default: 34 },
    color: { type: String, default: '#1a8a52' },
    track: { type: String, default: '#dcfce7' },
  })
  
  const spinnerStyle = computed(() => ({
    width: props.size + 'px',
    height: props.size + 'px',
    borderWidth: Math.max(2, Math.round(props.size / 11)) + 'px',
    borderColor: props.track,
    borderTopColor: props.color,
  }))
  </script>
  
  <style scoped>
  .donor-spinner {
    border-style: solid;
    border-radius: 50%;
    animation: donor-spin 0.7s linear infinite;
  }
  @keyframes donor-spin { to { transform: rotate(360deg); } }
  </style>