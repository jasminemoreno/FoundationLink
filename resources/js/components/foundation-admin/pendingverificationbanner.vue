<template>
    <div class="fa-status-banner">
      <div class="fa-banner-icon">⏳</div>
      <h3>Foundation Verification Pending</h3>
      <p><slot>You'll be able to access this once your foundation has been verified by the super admin.</slot></p>
    </div>
  </template>
  
  <script setup>
  // Default slot text covers the generic case; pass custom copy via the default
  // slot for page-specific wording, e.g.:
  // <PendingVerificationBanner>Donation data will appear here once your foundation has been verified.</PendingVerificationBanner>
  </script>
  
  <style scoped>
  .fa-status-banner {
    background: linear-gradient(135deg, #fff7e6, #fff1d6);
    border: 1px solid #ffd28a;
    padding: 36px 28px;
    border-radius: 16px;
    text-align: center;
  }
  .fa-banner-icon { font-size: 2.5rem; margin-bottom: 12px; }
  .fa-status-banner h3 { margin: 0 0 8px; font-size: 1.1rem; font-weight: 800; color: #b7791f; }
  .fa-status-banner p {
    margin: 0 auto;
    font-size: 0.86rem;
    color: #7a5a1b;
    line-height: 1.6;
    max-width: 480px;
  }
  </style>