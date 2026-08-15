<template>
    <button class="donor-heart-btn" :class="{ liked }" @click="$emit('toggle')">
      <span class="hb-icon">{{ liked ? '❤️' : '🤍' }}</span>
      <span>{{ count }}</span>
    </button>
  </template>
  
  <script setup>
  // Presentational only. Parent still owns the optimistic update + API call
  // (FoundationDetail.vue and Feed.vue hit different endpoints for this), e.g.:
  //
  // <ReactionButton :liked="u.my_reaction" :count="u.total_reactions" @toggle="toggleReaction(u)" />
  
  defineProps({
    liked: { type: Boolean, default: false },
    count: { type: Number,  default: 0 },
  })
  defineEmits(['toggle'])
  </script>
  
  <style scoped>
  .donor-heart-btn {
    display: inline-flex; align-items: center; gap: 7px;
    border: 1px solid #e2e8f0; background: white;
    padding: 6px 13px; border-radius: 20px; cursor: pointer;
    font-size: 0.82rem; font-weight: 700; color: #64748b;
    transition: all 0.15s;
  }
  .donor-heart-btn:hover { border-color: #1a8a52; }
  .donor-heart-btn.liked { background: #fef0f0; border-color: #fbb6b6; color: #d44a4a; }
  .hb-icon { font-size: 0.95rem; }
  </style>