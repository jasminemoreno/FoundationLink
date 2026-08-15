<template>
    <div class="donor-empty-state" :class="{ card: card }">
      <div v-if="icon" class="es-icon">{{ icon }}</div>
      <h3 v-if="title" class="es-title">{{ title }}</h3>
      <p v-if="message" class="es-message">{{ message }}</p>
      <slot />
      <router-link v-if="actionTo" :to="actionTo" class="es-action">{{ actionLabel }}</router-link>
      <button v-else-if="actionLabel" class="es-action es-action-btn" @click="$emit('action')">{{ actionLabel }}</button>
    </div>
  </template>
  
  <script setup>
  defineProps({
    icon:        { type: String,  default: '📋' },
    title:       { type: String,  default: '' },
    message:     { type: String,  default: '' },
    actionLabel: { type: String,  default: '' },
    actionTo:    { type: String,  default: '' }, // if set, renders a router-link; otherwise a button emitting 'action'
    card:        { type: Boolean, default: true },
  })
  defineEmits(['action'])
  </script>
  
  <style scoped>
  .donor-empty-state {
    text-align: center;
    padding: 60px 40px;
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
  }
  .donor-empty-state.card {
    background: white;
    border-radius: 18px;
    box-shadow: 0 2px 10px rgba(0,80,40,0.07);
  }
  .es-icon    { font-size: 2.5rem; }
  .es-title   { font-size: 1rem; font-weight: 800; color: #0F2D52; margin: 0; }
  .es-message { font-size: 0.88rem; color: #94a3b8; margin: 0; }
  .es-action {
    display: inline-block;
    margin-top: 4px;
    padding: 10px 22px;
    background: #1a8a52;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s;
  }
  .es-action:hover { background: #157042; }
  </style>