<template>
  <div class="page">
    <div class="container">

      <div class="page-head">
        <h1>Contact Us</h1>
        <p>Support for foundation administrators</p>
      </div>

      <div class="contact-grid">

        <div class="contact-card main">
          <div class="contact-icon">📧</div>
          <h3>Email Support</h3>
          <p>For verification issues, payment account setup, or any account questions. We aim to respond within 1-2 business days.</p>
          <a href="mailto:jmoreno4@ssct.edu.ph" class="btn-contact">
            jmoreno4@ssct.edu.ph
          </a>
        </div>

        <div class="contact-card">
          <div class="contact-icon">🔍</div>
          <h3>Check the Help Center first</h3>
          <p>Common questions about verification, campaigns, and payment accounts are already answered there.</p>
          <router-link to="/foundation/help" class="btn-contact-secondary">
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
.page { background: #f0f4f9; min-height: 100%; padding-bottom: 40px; box-sizing: border-box; }
.container { max-width: 720px; margin: 0 auto; padding: 32px 24px; }

.page-head { margin-bottom: 24px; text-align: center; }
.page-head h1 { font-size: 1.45rem; font-weight: 800; color: #133c8a; margin: 0 0 6px; }
.page-head p  { font-size: 0.85rem; color: #64748b; margin: 0; }

.contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }

.contact-card {
  background: white;
  border-radius: 16px;
  padding: 26px 22px;
  text-align: center;
  box-shadow: 0 1px 4px rgba(15,45,82,0.06);
  display: flex;
  flex-direction: column;
  align-items: center;
}
.contact-card.main { border: 2px solid #1a56c4; }

.contact-icon { font-size: 2rem; margin-bottom: 10px; }
.contact-card h3 { font-size: 0.95rem; font-weight: 800; color: #133c8a; margin: 0 0 8px; }
.contact-card p  { font-size: 0.8rem; color: #64748b; line-height: 1.6; margin: 0 0 16px; }

.btn-contact {
  display: inline-block;
  padding: 10px 20px;
  background: #1a56c4;
  color: white;
  border-radius: 10px;
  font-size: 0.83rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s;
  word-break: break-all;
}
.btn-contact:hover { background: #133c8a; }

.btn-contact-secondary {
  display: inline-block;
  padding: 10px 20px;
  background: white;
  color: #1a56c4;
  border: 1.5px solid #1a56c4;
  border-radius: 10px;
  font-size: 0.83rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s;
}
.btn-contact-secondary:hover { background: #f0f4ff; }

.notice {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: #fefce8;
  border: 1px solid #fde68a;
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 0.8rem;
  color: #92400e;
  line-height: 1.6;
}
.notice svg { flex-shrink: 0; margin-top: 1px; }

@media (max-width: 600px) {
  .contact-grid { grid-template-columns: 1fr; }
}
</style>