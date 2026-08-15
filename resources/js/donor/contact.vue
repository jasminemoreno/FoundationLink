<template>
    <div class="page">
      <div class="container">
  
        <div class="page-head">
          <h1>Contact Us</h1>
          <p>We're here to help with any questions or concerns.</p>
        </div>
  
        <div class="contact-grid">
  
          <div class="contact-card main">
            <div class="contact-icon">📧</div>
            <h3>Email Support</h3>
            <p>The fastest way to reach our team. We aim to respond within 1-2 business days.</p>
            <a href="mailto:support@foundationlink.com" class="btn-contact">
              support@foundationlink.com
            </a>
          </div>
  
          <div class="contact-card">
            <div class="contact-icon">🔍</div>
            <h3>Check the Help Center first</h3>
            <p>Many common questions about donations, campaigns, and your account are already answered there.</p>
            <router-link to="/donor/help" class="btn-contact-secondary">
              Visit Help Center
            </router-link>
          </div>
  
        </div>
  
        <div class="notice">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>Phone support and social media pages are not yet available. Email is currently the best way to reach us.</span>
        </div>
  
      </div>
    </div>
  </template>
  
  <script setup>
  </script>
  
  <style scoped>
  .page { background: #c8f0e0; min-height: 100vh; padding-bottom: 40px; }
  .container { max-width: 720px; margin: 0 auto; padding: 40px 24px; }
  
  .page-head { margin-bottom: 28px; text-align: center; }
  .page-head h1 { font-size: 1.6rem; font-weight: 900; color: #0e5c36; margin: 0 0 6px; }
  .page-head p  { font-size: 0.87rem; color: #475569; margin: 0; }
  
  .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
  
  .contact-card {
    background: white;
    border-radius: 18px;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 0 3px 12px rgba(0,80,40,0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .contact-card.main { border: 2px solid #1a8a52; }
  
  .contact-icon { font-size: 2.2rem; margin-bottom: 10px; }
  .contact-card h3 { font-size: 1rem; font-weight: 800; color: #0F2D52; margin: 0 0 8px; }
  .contact-card p  { font-size: 0.83rem; color: #64748b; line-height: 1.6; margin: 0 0 18px; }
  
  .btn-contact {
    display: inline-block;
    padding: 11px 22px;
    background: #1a8a52;
    color: white;
    border-radius: 11px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s;
    word-break: break-all;
  }
  .btn-contact:hover { background: #157042; }
  
  .btn-contact-secondary {
    display: inline-block;
    padding: 11px 22px;
    background: white;
    color: #1a8a52;
    border: 1.5px solid #1a8a52;
    border-radius: 11px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s;
  }
  .btn-contact-secondary:hover { background: #f0fdf4; }
  
  .notice {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    background: #fefce8;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 0.82rem;
    color: #92400e;
    line-height: 1.6;
  }
  .notice svg { flex-shrink: 0; margin-top: 1px; }
  
  @media (max-width: 600px) {
    .contact-grid { grid-template-columns: 1fr; }
  }
  </style>