<template>
    <div class="fa-suspended-banner" :class="{ compact }">
      <div class="fa-banner-icon">⛔</div>
      <div class="fa-banner-text">
        <h3>Foundation Suspended</h3>
        <p v-if="reason" class="fa-banner-reason"><strong>Reason:</strong> {{ reason }}</p>
        <p><slot>You can't manage campaigns or donations until the super admin lifts the suspension.</slot></p>
        <p class="fa-banner-help">
          Questions? Contact
          <a href="mailto:foundationlink.support@gmail.com">foundationlink.support@gmail.com</a>
        </p>
      </div>
    </div>
  </template>

  <script setup>
  // Shown on foundation admin pages while the super admin has suspended the foundation.
  // `compact` is the slim version that sits above content still shown read-only.
  defineProps({
    reason: { type: String, default: '' },
    compact: { type: Boolean, default: false },
  })
  </script>

  <style scoped>
  .fa-suspended-banner {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border: 1px solid #fca5a5;
    padding: 36px 28px;
    border-radius: 16px;
    text-align: center;
  }
  .fa-banner-icon { font-size: 2.5rem; margin-bottom: 12px; }
  .fa-suspended-banner h3 { margin: 0 0 8px; font-size: 1.1rem; font-weight: 800; color: #b91c1c; }
  .fa-suspended-banner p {
    margin: 0 auto 6px;
    font-size: 0.86rem;
    color: #7f1d1d;
    line-height: 1.6;
    max-width: 520px;
  }
  .fa-banner-reason { background: rgba(255,255,255,0.6); border-radius: 10px; padding: 8px 12px; }
  .fa-banner-help { font-size: 0.8rem; }
  .fa-banner-help a { color: #b91c1c; font-weight: 700; }

  .fa-suspended-banner.compact {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 20px;
    text-align: left;
    margin-bottom: 20px;
  }
  .compact .fa-banner-icon { font-size: 1.6rem; margin: 0; }
  .compact h3 { font-size: 0.98rem; margin-bottom: 4px; }
  .compact p { margin: 0 0 4px; max-width: none; }
  </style>
