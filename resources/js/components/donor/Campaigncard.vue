<template>
    <div
      class="donor-campaign-card"
      :class="{ highlighted }"
      :data-campaign-id="campaign.id"
    >
      <div class="cc-cover" @click="$emit('view-details', campaign)">
        <img v-if="campaign.cover_photo" :src="getImage(campaign.cover_photo)" alt="" />
        <div v-else class="cc-cover-placeholder">📢</div>
        <div class="cc-cover-overlay" />
        <span class="cc-type" :class="campaign.type">{{ campaign.type }}</span>
      </div>
  
      <div class="cc-body">
        <div class="cc-foundation-row">
          <span class="cc-foundation-dot" />
          <span class="cc-foundation">{{ campaign.foundation?.name }}</span>
        </div>
  
        <h3 class="cc-title" @click="$emit('view-details', campaign)">{{ campaign.title }}</h3>
        <p class="cc-desc">{{ campaign.description }}</p>
  
        <div v-if="showDeadline && campaign.end_date" class="cc-deadline">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          Ends {{ formatDate(campaign.end_date) }}
        </div>
  
        <div v-if="campaign.type !== 'item'" class="cc-progress">
          <div class="cc-prog-info">
            <span class="cc-raised">₱{{ formatMoney(campaign.current_amount) }}</span>
            <span class="cc-sep">of</span>
            <span class="cc-goal">₱{{ formatMoney(campaign.goal_amount) }}</span>
            <span class="cc-pct">{{ campaign.percent }}%</span>
          </div>
          <div class="cc-bar">
            <div class="cc-bar-fill" :style="{ width: Math.min(campaign.percent, 100) + '%' }" />
          </div>
        </div>
  
        <div class="cc-btn-row">
          <button class="cc-btn-details" @click="$emit('view-details', campaign)">View Details</button>
          <button v-if="campaign.status === 'active'" class="cc-btn-donate" @click="$emit('donate', campaign)">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            Donate Now
          </button>
          <div v-else class="cc-status-badge" :class="campaign.status">
            {{ campaign.status === 'completed' ? '✅ Completed' : campaign.status === 'paused' ? '⏸ Paused' : campaign.status }}
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { getImage, formatMoney, formatDate } from '../../composable/donor'
  
  defineProps({
    campaign:     { type: Object,  required: true },
    highlighted:  { type: Boolean, default: false },
    // Dashboard's "Featured Campaigns" never shows the deadline row (only active
    // campaigns are featured there); Campaigns.vue does. Toggle with this prop.
    showDeadline: { type: Boolean, default: true },
  })
  
  defineEmits(['view-details', 'donate'])
  </script>
  
  <style scoped>
  .donor-campaign-card {
    background: white;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,80,40,0.07);
    transition: transform 0.2s, box-shadow 0.2s, outline 0.3s;
  }
  .donor-campaign-card:hover { transform: translateY(-5px); box-shadow: 0 14px 32px rgba(0,80,40,0.14); }
  .donor-campaign-card.highlighted { outline: 3px solid #1a8a52; outline-offset: 2px; box-shadow: 0 0 0 6px rgba(26,138,82,0.15); }
  
  .cc-cover { position: relative; height: 180px; background: linear-gradient(135deg, #0e5c36, #1a8a52); overflow: hidden; cursor: pointer; }
  .cc-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.35s; }
  .donor-campaign-card:hover .cc-cover img { transform: scale(1.05); }
  .cc-cover-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2.8rem; }
  .cc-cover-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.35) 0%, transparent 60%); }
  
  .cc-type {
    position: absolute; top: 12px; left: 12px;
    font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: 0.05em; color: white;
  }
  .cc-type.monetary { background: rgba(5,150,105,0.9); }
  .cc-type.item     { background: rgba(59,130,246,0.9); }
  .cc-type.both     { background: rgba(124,58,237,0.9); }
  
  .cc-body { padding: 18px; }
  .cc-foundation-row { display: flex; align-items: center; gap: 5px; margin-bottom: 5px; }
  .cc-foundation-dot { width: 5px; height: 5px; border-radius: 50%; background: #1a8a52; flex-shrink: 0; }
  .cc-foundation { font-size: 0.72rem; color: #1a8a52; font-weight: 700; }
  
  .cc-title { font-size: 0.95rem; font-weight: 900; color: #0F2D52; margin: 0 0 6px; cursor: pointer; }
  .cc-title:hover { text-decoration: underline; }
  .cc-desc {
    font-size: 0.78rem; color: #64748b; line-height: 1.55; margin: 0 0 14px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  }
  
  .cc-deadline { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: #94a3b8; margin-bottom: 10px; }
  
  .cc-progress { margin-bottom: 14px; }
  .cc-prog-info { display: flex; align-items: baseline; gap: 4px; margin-bottom: 6px; }
  .cc-raised { font-size: 0.95rem; font-weight: 900; color: #0e5c36; }
  .cc-sep    { font-size: 0.72rem; color: #cbd5e1; }
  .cc-goal   { font-size: 0.75rem; color: #94a3b8; flex: 1; }
  .cc-pct    { font-size: 0.72rem; font-weight: 800; color: #1a8a52; }
  .cc-bar    { height: 6px; background: #dcfce7; border-radius: 99px; overflow: hidden; }
  .cc-bar-fill { height: 100%; background: linear-gradient(90deg, #1a8a52, #059669); border-radius: 99px; transition: width 0.6s ease; }
  
  .cc-btn-row { display: flex; gap: 8px; }
  .cc-btn-details {
    flex: 1; padding: 11px; border: 1.5px solid #e2e8f0; border-radius: 12px; background: white;
    color: #0F2D52; font-size: 0.83rem; font-weight: 800; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
  }
  .cc-btn-details:hover { border-color: #1a8a52; background: #f0fdf4; }
  
  .cc-btn-donate {
    flex: 1; padding: 11px; border: none; border-radius: 12px;
    background: linear-gradient(135deg, #0e5c36, #1a8a52); color: white;
    font-size: 0.83rem; font-weight: 800; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: opacity 0.2s, transform 0.15s; box-shadow: 0 3px 10px rgba(10,66,40,0.2);
  }
  .cc-btn-donate:hover { opacity: 0.9; transform: translateY(-1px); }
  
  .cc-status-badge { flex: 1; padding: 11px; border-radius: 12px; text-align: center; font-size: 0.83rem; font-weight: 800; box-sizing: border-box; }
  .cc-status-badge.completed { background: #f0fdf4; color: #1a8a52; }
  .cc-status-badge.paused    { background: #fefce8; color: #92400e; }
  </style>