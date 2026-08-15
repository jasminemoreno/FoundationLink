<template>
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <img src="@/assets/img/logo.png" alt="logo" class="footer-logo" />
        <div>
          <span class="footer-name">Foundation<span class="footer-accent">Link</span></span>
          <p class="footer-desc">Together, we bring hope through transparent and meaningful giving.</p>
        </div>
      </div>

      <div class="footer-links">
        <div class="fl-col">
          <span class="fl-title">Navigation</span>
          <router-link to="/donor/home"      class="fl-link">Home</router-link>
          <router-link to="/donor/foundations"      class="fl-link">Foundations</router-link>
          <router-link to="/donor/campaigns" class="fl-link">Campaigns</router-link>
          <router-link to="/donor/donations" class="fl-link">My Donations</router-link>
          <router-link to="/donor/updates"   class="fl-link">Updates</router-link>
        </div>
        <div class="fl-col">
<span class="fl-title">Support</span>
<router-link to="/donor/help"    class="fl-link">Help Center</router-link>
<router-link to="/donor/profile" class="fl-link">Settings / Profile</router-link>
<router-link to="/donor/service"   class="fl-link">Terms of Service</router-link>
<router-link to="/donor/privacy" class="fl-link">Privacy Policy</router-link>
<router-link to="/donor/contact" class="fl-link">Contact Us</router-link>
</div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© {{ year }} FoundationLink. All rights reserved.</span>
      <span>Made with ❤️ for the community</span>
    </div>
  </footer>
</template>

<script setup>
const year = new Date().getFullYear()
</script>

<style scoped>
.footer { background: #0e5c36; color: white; margin-top: auto; }
.footer-inner { max-width: 1200px; margin: 0 auto; padding: 40px 24px; display: flex; justify-content: space-between; gap: 40px; flex-wrap: wrap; }

.footer-brand { display: flex; align-items: flex-start; gap: 12px; max-width: 300px; }
.footer-logo  { width: 44px; height: 44px; object-fit: contain; filter: brightness(1.3); flex-shrink: 0; }
.footer-name  { font-size: 1.1rem; font-weight: 900; display: block; }
.footer-accent { color: #7eedc7; }
.footer-desc  { font-size: 0.78rem; color: rgba(255,255,255,0.65); line-height: 1.6; margin-top: 6px; }

.footer-links { display: flex; gap: 48px; }
.fl-col   { display: flex; flex-direction: column; gap: 10px; }
.fl-title { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #7eedc7; margin-bottom: 4px; }
.fl-link  { font-size: 0.83rem; color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.15s; }
.fl-link:hover { color: white; }

.footer-bottom { max-width: 1200px; margin: 0 auto; padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: rgba(255,255,255,0.5); flex-wrap: wrap; gap: 8px; }
</style>