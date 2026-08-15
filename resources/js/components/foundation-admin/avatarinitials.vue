<template>
    <div
      class="fa-avatar"
      :style="{ width: size + 'px', height: size + 'px', background: bgColor, fontSize: fontSize, borderRadius: radius }"
    >
      {{ initials }}
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  // `seed` picks the background color (pass the donor/donation id, same as the
  // avatarColor() helper every page previously duplicated). `size` covers the
  // default/lg/xl variants seen across pages (36 / 48 / 56).
  const props = defineProps({
    initials: { type: String, required: true },
    seed:     { type: [String, Number], default: 0 },
    size:     { type: Number, default: 36 },
  })
  
  const palette = ["#3b82f6","#059669","#ca8a04","#7c3aed","#dc2626","#0891b2","#db2777"]
  
  const bgColor = computed(() => {
    const n = typeof props.seed === 'number' ? props.seed : String(props.seed).length
    return palette[n % palette.length]
  })
  
  const fontSize = computed(() => Math.max(10, Math.round(props.size * 0.32)) + 'px')
  const radius   = computed(() => Math.round(props.size * 0.28) + 'px')
  </script>
  
  <style scoped>
  .fa-avatar {
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
  }
  </style>