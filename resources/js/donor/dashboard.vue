<template>
  <div class="page">
    <div class="container">

      <!-- HERO -->
      <div class="hero">
        <div class="hero-left">
          <div class="hero-eyebrow">
            <span class="hero-dot" />
            Good {{ timeOfDay }}, donor
          </div>
          <h1>Welcome back, <span class="hero-name">{{ user?.first_name }}!</span> 👋</h1>
          <p>Your generosity is making a real difference. Keep it up.</p>
          <div class="hero-actions">
            <router-link to="/donor/campaigns" class="btn-hero-primary">
              Donate Now
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </router-link>
            <router-link to="/donor/foundations" class="btn-hero-secondary">
              Explore Foundations
            </router-link>
          </div>
        </div>
        <div class="hero-right">
          <div class="hero-ring hero-ring-1" />
          <div class="hero-ring hero-ring-2" />
          <img src="@/assets/img/logo.png" alt="" class="hero-img" />
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-row">
        <StatCard
          v-for="s in stats" :key="s.label"
          :icon="s.icon" :value="s.value" :label="s.label"
          :bg="s.bg" :col="s.col"
        />
      </div>

      <!-- FOUNDATIONS -->
      <div class="section">
        <div class="section-head">
          <div class="section-head-left">
            <h2 class="section-title">Our Foundations</h2>
            <span class="section-count" v-if="foundations.length">{{ foundations.length }} verified</span>
          </div>
          <router-link to="/donor/foundations" class="see-all">
            See all
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </router-link>
        </div>

        <div v-if="foundationsLoading" class="inline-loading"><Spinner /></div>

        <EmptyState
          v-else-if="foundations.length === 0"
          icon="" message="No verified foundations yet." :card="false"
        />

        <div v-else class="foundations-track">
          <FoundationBubble v-for="f in foundations" :key="f.id" :foundation="f" />
        </div>
      </div>

      <!-- FEATURED CAMPAIGNS -->
      <div class="section">
        <div class="section-head">
          <div class="section-head-left">
            <h2 class="section-title">Featured Campaigns</h2>
            <span class="section-badge">Live</span>
          </div>
          <router-link to="/donor/campaigns" class="see-all">
            See all
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </router-link>
        </div>

        <div v-if="isLoading" class="inline-loading"><Spinner /></div>

        <EmptyState
          v-else-if="featured.length === 0"
          icon="📢" message="No active campaigns yet."
          action-label="Browse All" action-to="/donor/campaigns"
        />

        <div v-else class="campaigns-grid">
          <CampaignCard
            v-for="c in featured" :key="c.id"
            :campaign="c" :show-deadline="false"
            @view-details="openDetails" @donate="openDonate"
          />
        </div>
      </div>

      <!-- RECENT DONATIONS -->
      <div class="section">
        <div class="section-head">
          <div class="section-head-left">
            <h2 class="section-title">My Recent Donations</h2>
          </div>
          <router-link to="/donor/donations" class="see-all">
            See all
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </router-link>
        </div>

        <EmptyState
          v-if="recentDonations.length === 0"
          icon="🎁" message="You haven't donated yet. Start making an impact today!"
          action-label="Browse Campaigns" action-to="/donor/campaigns"
        />

        <div v-else class="recent-panel">
          <div class="recent-item" v-for="d in recentDonations" :key="d.id">
            <div class="recent-icon-wrap" :class="d.type">
              <span>{{ d.type === 'monetary' ? '💰' : '📦' }}</span>
            </div>
            <div class="recent-info">
              <div class="recent-campaign">{{ d.campaign?.title }}</div>
              <div class="recent-detail">
                <span v-if="d.type === 'monetary'" class="recent-amount">₱{{ formatMoney(d.amount) }}</span>
                <span v-else class="recent-amount">{{ d.item_name }} × {{ d.item_quantity }}</span>
              </div>
            </div>
            <div class="recent-right">
              <span class="recent-status" :class="d.status">{{ statusLabel(d.status) }}</span>
              <span class="recent-date">{{ formatDate(d.donated_at) }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- VIEW DETAILS MODAL -->
    <CampaignDetailsModal
      v-if="detailsTarget"
      :campaign="detailsTarget"
      @close="detailsTarget = null"
      @donate="onDonateFromDetails"
    />

    <!-- DONATE MODAL -->
    <DonateModal
      v-if="donateTarget"
      :campaign="donateTarget"
      @close="donateTarget = null"
      @donated="onDonated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import DonateModal from '@/components/donor/DonateModal.vue'
import CampaignDetailsModal from '../components/donor/ViewDetail.vue'
import Spinner from '../components/donor/spinner.vue'
import EmptyState from '../components/donor/emptystate.vue'
import FoundationBubble from '../components/donor/foundationbubble.vue'
import StatCard from '../components/donor/statcard.vue'
import CampaignCard from '../components/donor/campaigncard.vue'
import { formatMoney, formatDate } from '../composable/donor.js'

const isLoading          = ref(false)
const foundationsLoading = ref(false)
const featured           = ref([])
const recentDonations    = ref([])
const foundations        = ref([])
const donateTarget       = ref(null)
const detailsTarget      = ref(null)

const dashData = ref({ total_donated: 0, total_items: 0, campaigns_supported: 0 })

/* ── CURRENT USER (stays in sync when the profile is edited) ── */
function loadUserFromStorage() {
  try { return JSON.parse(sessionStorage.getItem('user') || 'null') }
  catch { return null }
}

const user = ref(loadUserFromStorage())

// Fired by the donor profile page after saving (same event the Topbar listens to)
function handleUserUpdated(e) {
  user.value = e.detail || loadUserFromStorage()
}

const timeOfDay = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 17) return 'afternoon'
  return 'evening'
})

const stats = computed(() => [
  {
    label: 'Total Donated',
    value: '₱' + formatMoney(dashData.value.total_donated),
    bg: '#f0fdf4', col: '#059669',
    icon: `<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
  },
  {
    label: 'Item Donations',
    value: dashData.value.total_items,
    bg: '#eff6ff', col: '#3b82f6',
    icon: `<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
  },
  {
    label: 'Campaigns Supported',
    value: dashData.value.campaigns_supported,
    bg: '#f5f3ff', col: '#7c3aed',
    icon: `<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
  },
])

async function load() {
  isLoading.value          = true
  foundationsLoading.value = true
  try {
    const [dashRes, foundRes] = await Promise.all([
      api.get('/donor/dashboard'),
      api.get('/donor/foundations'),
    ])
    dashData.value        = dashRes.data
    featured.value        = dashRes.data.featured_campaigns || []
    recentDonations.value = dashRes.data.recent_donations   || []
    foundations.value     = Array.isArray(foundRes.data) ? foundRes.data : []
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value          = false
    foundationsLoading.value = false
  }
}

onMounted(() => {
  window.addEventListener('user-updated', handleUserUpdated)
  // re-read in case the profile was edited while this page was cached/hidden
  user.value = loadUserFromStorage()
  load()
})

onUnmounted(() => {
  window.removeEventListener('user-updated', handleUserUpdated)
})

function openDonate(c)  { donateTarget.value = c }
function openDetails(c) { detailsTarget.value = c }
function onDonated()    { donateTarget.value = null; load() }

// Called when the user clicks "Donate Now" from inside the details modal
function onDonateFromDetails(c) {
  detailsTarget.value = null
  donateTarget.value  = c
}

function statusLabel(s) {
  return { pending: 'Pending', received: 'Received', cancelled: 'Cancelled' }[s] || s
}
</script>

<style scoped>
/* ─── BASE ─── */
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 48px; }
.container { max-width: 1200px; margin: 0 auto; padding: 32px 24px; }

/* ─── HERO ─── */
.hero {
  background: linear-gradient(135deg, #1f9459 0%, #36ab70 50%, #4dbb83 100%);
  border-radius: 24px;
  padding: 44px 48px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 24px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(10, 66, 40, 0.25);
}
.hero::before {
  content: '';
  position: absolute;
  top: -60px; right: -60px;
  width: 240px; height: 240px;
  border-radius: 50%;
  background: rgba(255,255,255,0.04);
  pointer-events: none;
}
.hero::after {
  content: '';
  position: absolute;
  bottom: -80px; left: 30%;
  width: 320px; height: 320px;
  border-radius: 50%;
  background: rgba(255,255,255,0.03);
  pointer-events: none;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.6);
  margin-bottom: 10px;
}
.hero-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #7eedc7;
  display: inline-block;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.7); }
}

.hero-left h1 {
  font-size: 1.75rem;
  font-weight: 900;
  color: white;
  margin: 0 0 10px;
  line-height: 1.2;
}
.hero-name { color: #7eedc7; }
.hero-left p {
  font-size: 0.9rem;
  color: rgba(255,255,255,0.65);
  margin: 0 0 24px;
}

.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn-hero-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: white;
  color: #0e5c36;
  font-size: 0.88rem;
  font-weight: 800;
  border-radius: 12px;
  text-decoration: none;
  transition: all 0.2s;
  box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}
.btn-hero-primary:hover { background: #f0fdf4; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }

.btn-hero-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 22px;
  background: rgba(255,255,255,0.12);
  color: white;
  font-size: 0.88rem;
  font-weight: 700;
  border-radius: 12px;
  text-decoration: none;
  border: 1.5px solid rgba(255,255,255,0.25);
  transition: all 0.2s;
  backdrop-filter: blur(8px);
}
.btn-hero-secondary:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }

.hero-right {
  position: relative;
  flex-shrink: 0;
  width: 140px; height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.hero-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.1);
}
.hero-ring-1 { width: 120px; height: 120px; animation: spin-slow 12s linear infinite; }
.hero-ring-2 { width: 155px; height: 155px; border-style: dashed; animation: spin-slow 20s linear infinite reverse; }
@keyframes spin-slow { to { transform: rotate(360deg); } }

.hero-img {
  width: 96px; height: 96px;
  box-sizing: border-box;
  padding: 14px;
  background: white;
  border-radius: 50%;
  object-fit: contain;
  opacity: 1;
  box-shadow: 0 6px 20px rgba(0,0,0,0.18);
  position: relative;
  z-index: 1;
}

/* ─── STATS ─── */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 32px;
}

/* ─── SECTION ─── */
.section { margin-bottom: 36px; }
.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}
.section-head-left { display: flex; align-items: center; gap: 10px; }
.section-title { font-size: 1.05rem; font-weight: 900; color: #0e5c36; margin: 0; }
.section-count {
  font-size: 0.7rem;
  font-weight: 700;
  background: #dcfce7;
  color: #059669;
  padding: 2px 8px;
  border-radius: 20px;
}
.section-badge {
  font-size: 0.65rem;
  font-weight: 800;
  background: #fef2f2;
  color: #dc2626;
  padding: 2px 8px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  animation: badge-pulse 2s ease-in-out infinite;
}
@keyframes badge-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.55; }
}

.see-all {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #1a8a52;
  text-decoration: none;
  transition: gap 0.15s;
}
.see-all:hover { gap: 8px; }

/* ─── FOUNDATIONS TRACK ─── */
.foundations-track {
  display: flex;
  gap: 22px;
  overflow-x: auto;
  padding: 8px 4px 12px;
  scrollbar-width: none;
}
.foundations-track::-webkit-scrollbar { display: none; }

/* ─── CAMPAIGNS GRID ─── */
.campaigns-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
  gap: 18px;
}

/* ─── RECENT PANEL ─── */
.recent-panel {
  background: white;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,80,40,0.07);
}
.recent-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 15px 22px;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}
.recent-item:last-child { border-bottom: none; }
.recent-item:hover { background: #f8fffe; }

.recent-icon-wrap {
  width: 42px; height: 42px;
  border-radius: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.recent-icon-wrap.monetary { background: #f0fdf4; }
.recent-icon-wrap.item     { background: #eff6ff; }

.recent-info { flex: 1; min-width: 0; }
.recent-campaign { font-size: 0.87rem; font-weight: 800; color: #0F2D52; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.recent-amount   { font-size: 0.78rem; color: #64748b; font-weight: 600; }

.recent-right { text-align: right; flex-shrink: 0; }
.recent-status {
  display: inline-block;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 3px 9px;
  border-radius: 20px;
  margin-bottom: 4px;
  text-transform: capitalize;
}
.recent-status.pending   { background: #fefce8; color: #92400e; }
.recent-status.received  { background: #f0fdf4; color: #059669; }
.recent-status.cancelled { background: #fef2f2; color: #dc2626; }
.recent-date { display: block; font-size: 0.7rem; color: #94a3b8; }

/* ─── LOADING ─── */
.inline-loading {
  display: flex;
  justify-content: center;
  padding: 32px;
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  .hero { padding: 28px 24px; flex-direction: column; text-align: center; }
  .hero-actions { justify-content: center; }
  .hero-right { display: none; }
  .stats-row { grid-template-columns: 1fr; }
  .campaigns-grid { grid-template-columns: 1fr; }
}
</style>