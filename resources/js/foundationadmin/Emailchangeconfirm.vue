<template>
    <div class="confirm-page">
      <div class="confirm-card">
        <div class="confirm-icon" :class="status">
          <svg v-if="status === 'loading'" class="spin" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" opacity="0.25"/>
            <path d="M12 2a10 10 0 0 1 10 10"/>
          </svg>
          <svg v-else-if="status === 'success'" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          <svg v-else width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </div>
  
        <h2>{{ heading }}</h2>
        <p>{{ message }}</p>
  
        <router-link v-if="status !== 'loading'" to="/login" class="btn-back">
          Back to Login
        </router-link>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useRoute } from 'vue-router'
  import api from '@/services/api'
  
  const route = useRoute()
  
  const status  = ref('loading') // loading | success | error
  const message = ref('')
  
  const heading = computed(() => {
    if (status.value === 'loading') return 'Confirming your new email…'
    if (status.value === 'success') return 'Email updated'
    return 'Link invalid or expired'
  })
  
  onMounted(async () => {
    const token = route.params.token
    try {
      const res = await api.post(`/foundation/profile/email/confirm/${token}`)
      status.value = 'success'
      message.value = res.data.message
    } catch (err) {
      status.value = 'error'
      message.value = err.response?.data?.message || 'This verification link is invalid or has expired.'
    }
  })
  </script>
  
  <style scoped>
  .confirm-page {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #f0f4f9; padding: 24px;
  }
  .confirm-card {
    background: white; border-radius: 20px; padding: 40px 32px;
    max-width: 380px; width: 100%; text-align: center;
    box-shadow: 0 4px 20px rgba(15,45,82,0.08);
  }
  
  .confirm-icon {
    width: 60px; height: 60px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px;
  }
  .confirm-icon.loading { background: #eff6ff; color: #1a56c4; }
  .confirm-icon.success { background: #f0fdf4; color: #059669; }
  .confirm-icon.error   { background: #fef2f2; color: #dc2626; }
  .confirm-icon .spin { animation: spin 0.9s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  
  .confirm-card h2 { font-size: 1.1rem; font-weight: 800; color: #133c8a; margin: 0 0 8px; }
  .confirm-card p  { font-size: 0.87rem; color: #64748b; line-height: 1.5; margin: 0 0 22px; }
  
  .btn-back {
    display: inline-block; padding: 10px 22px; border-radius: 10px;
    background: #1a56c4; color: white; text-decoration: none;
    font-size: 0.85rem; font-weight: 700;
  }
  .btn-back:hover { background: #133c8a; }
  </style>