<template>
    <div class="donor-foundation-bubble" @click="$router.push('/donor/foundations/' + foundation.id)">
      <div v-if="ringed" class="fb-ring">
        <div class="fb-circle" :style="circleStyle">
          <img v-if="foundation.logo" :src="getImage(foundation.logo)" :alt="foundation.name" />
          <span v-else>{{ foundation.initials }}</span>
        </div>
      </div>
      <div v-else class="fb-circle no-ring" :style="circleStyle">
        <img v-if="foundation.logo" :src="getImage(foundation.logo)" :alt="foundation.name" />
        <span v-else>{{ foundation.initials }}</span>
      </div>
  
      <div class="fb-name">{{ foundation.name }}</div>
      <div class="fb-meta">
        <span class="fb-dot" />
        {{ foundation.active_campaigns }} active
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  import { getImage } from '../../composable/donor'
  
  // Dashboard uses the gradient-ring style at 68px; MyDonations uses a plain
  // circle at 76px. Both are covered here via props instead of two copies.
  const props = defineProps({
    foundation: { type: Object,  required: true },
    size:       { type: Number,  default: 68 },
    ringed:     { type: Boolean, default: true },
  })
  
  const circleStyle = computed(() => ({
    width: props.size + 'px',
    height: props.size + 'px',
  }))
  </script>
  
  <style scoped>
  .donor-foundation-bubble {
    display: flex; flex-direction: column; align-items: center; gap: 7px;
    cursor: pointer; flex-shrink: 0; transition: transform 0.2s;
  }
  .donor-foundation-bubble:hover { transform: translateY(-5px); }
  
  .fb-ring { padding: 3px; border-radius: 50%; background: linear-gradient(135deg, #0e5c36, #7eedc7); display: inline-flex; }
  .fb-circle {
    border-radius: 50%;
    background: linear-gradient(135deg, #0e5c36, #1a8a52);
    color: white; font-size: 1.05rem; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    border: 3px solid white; overflow: hidden;
    box-shadow: 0 4px 14px rgba(0,80,40,0.18);
    transition: box-shadow 0.2s;
  }
  .fb-circle.no-ring { box-shadow: 0 4px 16px rgba(0,80,40,0.22); }
  .donor-foundation-bubble:hover .fb-circle { box-shadow: 0 8px 24px rgba(0,80,40,0.3); }
  .fb-circle img { width: 100%; height: 100%; object-fit: cover; }
  
  .fb-name {
    font-size: 0.72rem; font-weight: 800; color: #0F2D52; text-align: center;
    max-width: 80px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .fb-meta { display: flex; align-items: center; gap: 4px; font-size: 0.65rem; color: #1a8a52; font-weight: 700; }
  .fb-dot { width: 5px; height: 5px; border-radius: 50%; background: #1a8a52; display: inline-block; }
  </style>