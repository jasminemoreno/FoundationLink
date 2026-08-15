<template>
    <div v-if="modelValue" class="donor-lightbox" @click="$emit('update:modelValue', null)">
      <img :src="modelValue" alt="" />
      <button class="lb-close" @click.stop="$emit('update:modelValue', null)">✕</button>
    </div>
  </template>
  
  <script setup>
  // Usage: <Lightbox v-model="lightbox" />
  // where `lightbox` is a ref holding an image URL string or null.
  // Set lightbox.value = someUrl to open it; the component clears it on close.
  
  defineProps({
    modelValue: { type: String, default: null },
  })
  defineEmits(['update:modelValue'])
  </script>
  
  <style scoped>
  .donor-lightbox {
    position: fixed; inset: 0; background: rgba(0,0,0,0.9);
    display: flex; align-items: center; justify-content: center; z-index: 200;
  }
  .donor-lightbox img { max-width: 90%; max-height: 90vh; border-radius: 10px; object-fit: contain; }
  .lb-close {
    position: absolute; top: 20px; right: 20px; width: 36px; height: 36px;
    border-radius: 50%; border: none; background: rgba(255,255,255,0.15);
    color: white; font-size: 1rem; cursor: pointer;
  }
  .lb-close:hover { background: rgba(255,255,255,0.28); }
  </style>