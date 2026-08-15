<template>
    <div v-if="modelValue" class="fa-lightbox" @click="$emit('update:modelValue', null)">
      <img :src="modelValue" alt="" />
      <button class="fa-lb-close" @click.stop="$emit('update:modelValue', null)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
  </template>
  
  <script setup>
  // Usage: <Lightbox v-model="lightbox" />
  // where `lightbox` is a ref holding an image URL string or null.
  
  defineProps({
    modelValue: { type: String, default: null },
  })
  defineEmits(['update:modelValue'])
  </script>
  
  <style scoped>
  .fa-lightbox {
    position: fixed; inset: 0; background: rgba(0,0,0,0.9);
    display: flex; align-items: center; justify-content: center; z-index: 200;
  }
  .fa-lightbox img { max-width: 90%; max-height: 90vh; border-radius: 10px; object-fit: contain; }
  .fa-lb-close {
    position: absolute; top: 20px; right: 20px; width: 36px; height: 36px;
    border-radius: 50%; border: none; background: rgba(255,255,255,0.15);
    color: white; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.15s;
  }
  .fa-lb-close:hover { background: rgba(255,255,255,0.25); }
  </style>