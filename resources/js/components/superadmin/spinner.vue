<template>
    <div class="spinner" :style="style"></div>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    size: { type: Number, default: 22 },        // was 22 (default), 32 (spinner-lg), 16 (spinner-sm)
    color: { type: String, default: '#0F2D52' }, // set to 'white' for use on dark/filled buttons
    track: { type: String, default: '#e2e8f0' },
    thickness: { type: Number, default: null },
  })
  
  const style = computed(() => {
    const t = props.thickness ?? Math.max(2, Math.round(props.size * 0.09))
    return {
      width: props.size + 'px',
      height: props.size + 'px',
      border: `${t}px solid ${props.track}`,
      borderTopColor: props.color,
    }
  })
  </script>
  
  <style scoped>
  .spinner {
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: inline-block;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  </style>