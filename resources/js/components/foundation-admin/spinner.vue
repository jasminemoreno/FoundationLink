<template>
    <div class="fa-spinner" :style="spinnerStyle" />
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    size:  { type: Number, default: 32 },
    color: { type: String, default: '#0F2D52' },
    track: { type: String, default: '#e2e8f0' },
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
  .fa-spinner {
    border-style: solid;
    border-radius: 50%;
    animation: fa-spin 0.7s linear infinite;
  }
  @keyframes fa-spin { to { transform: rotate(360deg); } }
  </style>