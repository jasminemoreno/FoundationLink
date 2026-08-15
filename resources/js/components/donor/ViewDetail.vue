<template>
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="modal">
  
        <div class="modal-head">
          <h3>Campaign Details</h3>
          <button class="btn-close" @click="$emit('close')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
  
        <!-- COVER -->
        <div class="details-cover">
          <img v-if="campaign.cover_photo" :src="getImage(campaign.cover_photo)" alt="" />
          <div v-else class="details-cover-placeholder">📢</div>
          <span class="details-type-badge" :class="campaign.type">{{ campaign.type }}</span>
        </div>
  
        <!-- TITLE + FOUNDATION -->
        <div class="details-title-row">
          <h2 class="details-title">{{ campaign.title }}</h2>
          <div class="details-foundation">
            <span class="details-foundation-dot" />
            {{ campaign.foundation?.name }}
          </div>
        </div>
  
        <!-- STATUS -->
        <div class="details-status-row">
          <span class="status-chip" :class="campaign.status">{{ statusLabel(campaign.status) }}</span>
        </div>
  
        <!-- DESCRIPTION -->
        <div class="details-section">
          <div class="details-label">About this campaign</div>
          <p class="details-desc">{{ campaign.description || 'No description provided.' }}</p>
        </div>
  
        <!-- DATES -->
        <div class="details-dates">
          <div class="date-box">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <div>
              <div class="date-label">Start Date</div>
              <div class="date-value">{{ formatDate(campaign.start_date) }}</div>
            </div>
          </div>
          <div class="date-box">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <div>
              <div class="date-label">End Date</div>
              <div class="date-value">{{ formatDate(campaign.end_date) }}</div>
            </div>
          </div>
        </div>
  
        <!-- PROGRESS -->
        <div v-if="campaign.type !== 'item'" class="details-section">
          <div class="details-label">Funding Progress</div>
          <div class="prog-info">
            <span class="prog-raised">₱{{ formatMoney(campaign.current_amount) }}</span>
            <span class="prog-sep">of</span>
            <span class="prog-goal">₱{{ formatMoney(campaign.goal_amount) }}</span>
            <span class="prog-pct">{{ percent }}%</span>
          </div>
          <div class="prog-bar">
            <div class="prog-fill" :style="{ width: Math.min(percent, 100) + '%' }" />
          </div>
        </div>
  
        <!-- ACCEPTED PAYMENT METHODS -->
        <div v-if="(campaign.type === 'monetary' || campaign.type === 'both') && campaign.accepted_payment_methods?.length" class="details-section">
          <div class="details-label">Accepted Payment Methods</div>
          <div class="chip-row">
            <span class="method-chip" v-for="pm in campaign.accepted_payment_methods" :key="pm.payment_method_id || pm.id">
              {{ pm.icon || '💳' }} {{ pm.name }}
            </span>
          </div>
        </div>
  
        <!-- ACCEPTED DELIVERY METHODS -->
        <div v-if="(campaign.type === 'item' || campaign.type === 'both') && campaign.accepted_delivery_methods?.length" class="details-section">
          <div class="details-label">Item Delivery Options</div>
          <div class="chip-row">
            <span class="method-chip" v-for="dm in campaign.accepted_delivery_methods" :key="dm">
              {{ dm === 'dropoff' ? '🏢 Drop Off' : '🏠 Pick Up' }}
            </span>
          </div>
        </div>
  
        <!-- PAUSE REASON -->
        <div v-if="campaign.status === 'paused' && campaign.pause_reason" class="pause-box">
          <strong>Paused:</strong> {{ campaign.pause_reason }}
        </div>
  
        <div class="modal-actions">
          <button class="btn-cancel" @click="$emit('close')">Close</button>
          <button
            v-if="campaign.status === 'active'"
            class="btn-donate"
            @click="$emit('donate', campaign)"
          >
            Donate Now
          </button>
        </div>
  
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({ campaign: Object })
  defineEmits(['close', 'donate'])
  
  const percent = computed(() => {
    const goal = Number(props.campaign?.goal_amount || 0)
    const current = Number(props.campaign?.current_amount || 0)
    if (!goal) return 0
    return Math.round((current / goal) * 100)
  })
  
  function getImage(path) { return path ? `http://127.0.0.1:8000/storage/${path}` : '' }
  function formatMoney(v) { return Number(v || 0).toLocaleString() }
  function formatDate(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
  }
  function statusLabel(s) {
    return {
      active: '🟢 Active',
      completed: '✅ Completed',
      paused: '⏸ Paused',
      cancelled: '❌ Cancelled',
      draft: '📝 Draft',
    }[s] || s
  }
  </script>
  
  <style scoped>
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 200; padding: 24px; }
  .modal { background: white; border-radius: 20px; padding: 28px; width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
  
  .modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .modal-head h3 { font-size: 1rem; font-weight: 800; color: #0F2D52; margin: 0; }
  .btn-close { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: transparent; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; }
  .btn-close:hover { background: #f1f5f9; }
  
  .details-cover { position: relative; height: 180px; border-radius: 14px; overflow: hidden; background: linear-gradient(135deg, #0e5c36, #1a8a52); margin-bottom: 16px; }
  .details-cover img { width: 100%; height: 100%; object-fit: cover; }
  .details-cover-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2.8rem; }
  .details-type-badge { position: absolute; top: 10px; left: 10px; font-size: 0.65rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; color: white; }
  .details-type-badge.monetary { background: rgba(5,150,105,0.9); }
  .details-type-badge.item     { background: rgba(59,130,246,0.9); }
  .details-type-badge.both     { background: rgba(124,58,237,0.9); }
  
  .details-title-row { margin-bottom: 8px; }
  .details-title { font-size: 1.1rem; font-weight: 900; color: #0F2D52; margin: 0 0 6px; }
  .details-foundation { display: flex; align-items: center; gap: 5px; font-size: 0.8rem; color: #1a8a52; font-weight: 700; }
  .details-foundation-dot { width: 5px; height: 5px; border-radius: 50%; background: #1a8a52; }
  
  .details-status-row { margin-bottom: 16px; }
  .status-chip { display: inline-block; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; }
  .status-chip.active    { background: #f0fdf4; color: #059669; }
  .status-chip.completed { background: #eff6ff; color: #3b82f6; }
  .status-chip.paused    { background: #fefce8; color: #92400e; }
  .status-chip.cancelled { background: #fef2f2; color: #dc2626; }
  .status-chip.draft     { background: #f1f5f9; color: #64748b; }
  
  .details-section { margin-bottom: 16px; }
  .details-label { font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px; }
  .details-desc { font-size: 0.87rem; color: #334155; line-height: 1.6; margin: 0; }
  
  .details-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
  .date-box { display: flex; align-items: center; gap: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px 12px; color: #1a8a52; }
  .date-label { font-size: 0.68rem; color: #1a8a52; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
  .date-value { font-size: 0.83rem; color: #0F2D52; font-weight: 700; margin-top: 1px; }
  
  .prog-info { display: flex; align-items: baseline; gap: 5px; margin-bottom: 6px; }
  .prog-raised { font-size: 1rem; font-weight: 900; color: #0e5c36; }
  .prog-sep    { font-size: 0.75rem; color: #cbd5e1; }
  .prog-goal   { font-size: 0.78rem; color: #94a3b8; flex: 1; }
  .prog-pct    { font-size: 0.75rem; font-weight: 800; color: #1a8a52; }
  .prog-bar    { height: 7px; background: #dcfce7; border-radius: 99px; overflow: hidden; }
  .prog-fill   { height: 100%; background: linear-gradient(90deg, #1a8a52, #059669); border-radius: 99px; transition: width 0.5s ease; }
  
  .chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
  .method-chip { font-size: 0.8rem; font-weight: 700; color: #0F2D52; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 6px 12px; }
  
  .pause-box { font-size: 0.82rem; color: #92400e; background: #fefce8; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 14px; line-height: 1.5; margin-bottom: 16px; }
  
  .modal-actions { display: flex; gap: 10px; margin-top: 8px; }
  .btn-cancel { flex: 1; padding: 12px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; }
  .btn-donate { flex: 2; padding: 12px; border: none; background: #1a8a52; color: white; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; }
  .btn-donate:hover { background: #157042; }
  </style>