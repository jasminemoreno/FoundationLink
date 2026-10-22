<template>
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <img src="@/assets/img/logo.png" alt="logo" class="footer-logo" />
        <div>
          <span class="footer-name">Foundation<span class="footer-accent">Link</span></span>
          <p class="footer-desc">Manage your campaigns and donations with clarity and trust.</p>
        </div>
      </div>

      <div class="footer-links">
        <div class="fl-col">
          <span class="fl-title">Navigation</span>
          <router-link to="/foundation/dashboard" class="fl-link">Dashboard</router-link>
          <router-link to="/foundation/campaign"   class="fl-link">Campaigns</router-link>
          <router-link to="/foundation/donation"   class="fl-link">Donations</router-link>
          <router-link to="/foundation/donor"      class="fl-link">Donors</router-link>
          <router-link to="/foundation/report"     class="fl-link">Reports</router-link>
        </div>
        <div class="fl-col">
          <span class="fl-title">Support</span>
          <router-link to="/foundation/help"    class="fl-link">Help Center</router-link>
          <router-link to="/foundation/setting" class="fl-link">Settings</router-link>
          <router-link to="/foundation/terms"   class="fl-link">Terms of Service</router-link>
          <router-link to="/foundation/privacy" class="fl-link">Privacy Policy</router-link>
          <router-link to="/foundation/contact" class="fl-link">Contact Us</router-link>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© {{ year }} FoundationLink. All rights reserved.</span>
      <span>Foundation Admin Panel</span>
    </div>
  </footer>
</template>

<script setup>
const year = new Date().getFullYear()
</script>

<style scoped>
/* Light footer: soft blue panel with dark, readable text */
.footer { background: var(--fa-surface); border-top: 1px solid var(--fa-border); color: var(--fa-text); margin-top: auto; }
.footer-inner { max-width: 1200px; margin: 0 auto; padding: 32px 24px; display: flex; justify-content: space-between; gap: 40px; flex-wrap: wrap; }

.footer-brand { display: flex; align-items: flex-start; gap: 12px; max-width: 300px; }
.footer-logo  { width: 40px; height: 40px; object-fit: contain; flex-shrink: 0; }
.footer-name  { font-size: 1.05rem; font-weight: 900; display: block; color: var(--fa-heading); }
.footer-accent { color: var(--fa-primary); }
.footer-desc  { font-size: 0.8rem; color: var(--fa-muted); line-height: 1.6; margin-top: 6px; }

.footer-links { display: flex; gap: 48px; }
.fl-col   { display: flex; flex-direction: column; gap: 9px; }
.fl-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--fa-heading); margin-bottom: 4px; }
.fl-link  { font-size: 0.85rem; color: var(--fa-text-2); text-decoration: none; transition: color 0.15s; }
.fl-link:hover { color: var(--fa-primary); text-decoration: underline; }

.footer-bottom { max-width: 1200px; margin: 0 auto; padding: 14px 24px; border-top: 1px solid var(--fa-border-soft); display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--fa-muted); flex-wrap: wrap; gap: 8px; }

@media (max-width: 700px) {
  .footer-inner { flex-direction: column; }
  .footer-links { gap: 32px; }
}
</style>
