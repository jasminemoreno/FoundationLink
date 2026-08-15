<template>
  <div class="dashboard">

    <!-- PENDING VERIFICATION BANNER -->
    <div
      v-if="foundationStatus === 'pending_verification' || foundationStatus === 'under_review'"
      class="status-banner"
    >
      <div class="banner-icon">⏳</div>
      <h2>Foundation Verification Pending</h2>
      <p>
        Your foundation is currently under review. Please wait while our super administrator
        verifies your submitted information. Full dashboard access will be granted once your
        foundation has been approved.
      </p>
    </div>

    <!-- REJECTED BANNER -->
    <div v-else-if="foundationStatus === 'rejected'" class="status-banner rejected">
      <div class="banner-icon">❌</div>
      <h2>Foundation Rejected</h2>
      <p>
        <strong>Reason:</strong>
        {{ foundation?.rejection_reason || 'No reason provided.' }}
      </p>

      <button
        v-if="!showResubmitForm"
        class="resubmit-btn"
        @click="showResubmitForm = true"
      >
        Edit & Resubmit
      </button>

      <FoundationResubmitForm
        v-if="showResubmitForm"
        :foundation="foundation"
        @submitted="onResubmitted"
        @cancel="showResubmitForm = false"
      />
    </div>

    <!-- DASHBOARD CONTENT -->
    <div v-else>

      <!-- FOUNDATION HEADER -->
      <div class="foundation-header">
        <div class="foundation-info">
          <AvatarInitials :initials="foundationInitials" :seed="foundation?.id ?? 0" :size="48" />
          <div>
            <h2 class="foundation-name">{{ foundation?.name }}</h2>
            <p class="foundation-cat">{{ foundation?.category?.name || 'No Category' }}</p>
          </div>
        </div>
        <span class="verified-badge" v-if="foundationStatus === 'verified'">
          ✓ Verified
        </span>
      </div>

      <!-- STAT CARDS -->
      <div class="stat-cards">
        <StatCard
          v-for="s in stats" :key="s.label"
          :icon="s.icon" :value="s.value" :label="s.label"
          :bg="s.bg" :col="s.col"
        />
      </div>

      <!-- LOWER SECTION -->
      <div class="lower-section">

        <!-- RECENT DONATIONS -->
        <div class="panel">
          <div class="panel-head">
            <h3 class="panel-title">Recent Donations</h3>
            <router-link to="/foundation/donations" class="panel-link">View all →</router-link>
          </div>

          <div class="panel-body">
            <EmptyState v-if="isLoading" loading compact />

            <EmptyState
              v-else-if="recentDonations.length === 0"
              icon="🎁" message="No donations yet." compact
            />

            <table v-else class="donations-table">
              <thead>
                <tr>
                  <th>Donor</th>
                  <th>Campaign</th>
                  <th>Type</th>
                  <th>Details</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in recentDonations" :key="d.id">
                  <td>
                    <div class="donor-cell">
                      <AvatarInitials :initials="d.donor.initials" :seed="d.donor.id" :size="30" />
                      <div>
                        <strong>{{ d.donor.name }}</strong>
                        <small>{{ d.donor.email }}</small>
                      </div>
                    </div>
                  </td>
                  <td class="campaign-name">{{ d.campaign.title }}</td>
                  <td>
                    <Badge
                      :label="d.type === 'monetary' ? 'Monetary' : 'Item'"
                      :tone="d.type === 'monetary' ? 'success' : 'info'"
                    />
                  </td>
                  <td>
                    <span v-if="d.type === 'monetary'" class="amount">
                      ₱{{ formatMoney(d.amount) }}
                    </span>
                    <span v-else class="item-info">
                      {{ d.item_name }} x{{ d.item_quantity }}
                    </span>
                  </td>
                  <td>
                    <Badge :label="statusLabel(d.status)" :tone="toneForDonationStatus(d.status)" />
                  </td>
                  <td class="date-col">{{ formatDate(d.donated_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="right-col">

          <!-- CAMPAIGN PROGRESS -->
          <div class="panel">
            <div class="panel-head">
              <h3 class="panel-title">Campaign Progress</h3>
              <router-link to="/foundation/campaign" class="panel-link">View all →</router-link>
            </div>

            <div class="panel-body">
              <EmptyState
                v-if="campaigns.length === 0"
                icon="📋" message="No campaigns yet." compact
              />

              <div v-else class="progress-list">
                <div v-for="c in campaigns" :key="c.id" class="progress-item">
                  <div class="progress-meta">
                    <span class="progress-name">{{ c.title }}</span>
                    <span class="progress-pct">{{ c.percent }}%</span>
                  </div>
                  <div class="progress-bar-bg">
                    <div
                      class="progress-bar-fill"
                      :style="{ width: c.percent + '%', background: progressColor(c.percent) }"
                    ></div>
                  </div>
                  <div class="progress-foot">
                    <span>₱{{ formatMoney(c.current_amount) }}</span>
                    <span class="muted">of ₱{{ formatMoney(c.goal_amount) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- QUICK STATS -->
          <div class="panel" style="margin-top: 16px;">
            <div class="panel-head">
              <h3 class="panel-title">Foundation Info</h3>
            </div>
            <div class="panel-body info-list">
              <div class="info-row">
                <span class="info-label">Status</span>
                <Badge :label="statusLabel(foundationStatus)" :tone="toneForFoundationStatus(foundationStatus)" />
              </div>
              <div class="info-row">
                <span class="info-label">Category</span>
                <span>{{ foundation?.category?.name || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">City</span>
                <span>{{ foundation?.city_municipality || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Province</span>
                <span>{{ foundation?.province || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Pending Donations</span>
                <span class="pending-count">{{ donationStats.pending ?? 0 }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"
import FoundationResubmitForm from "@/components/foundation-admin/FoundationResubmitForm.vue"
import StatCard from "../components/foundation-admin/statcard.vue"
import EmptyState from "../components/foundation-admin/emptystate.vue"
import AvatarInitials from "../components/foundation-admin/avatarinitials.vue"
import Badge from "../components/foundation-admin/badge.vue"

const isLoading        = ref(false)
const foundation       = ref(null)
const foundationStatus = ref("pending_verification")
const recentDonations  = ref([])
const campaigns        = ref([])
const showResubmitForm = ref(false)
const followersCount   = ref(0)
const donationStats    = ref({
  total_monetary: 0,
  total_items:    0,
  total_donors:   0,
  pending:        0,
})

/* ── LOAD ── */
async function loadDashboard() {
  isLoading.value = true
  try {
    const res1 = await api.get("/foundation/dashboard")
    if (res1.data.foundation) {
      foundation.value       = res1.data.foundation
      foundationStatus.value = res1.data.foundation.status
    }
    followersCount.value = res1.data.followers_count ?? 0

    if (foundationStatus.value === 'verified') {
      const [res2, res3, res4] = await Promise.all([
        api.get("/foundation/dashboard/stats"),
        api.get("/foundation/donations"),
        api.get("/foundation/campaigns"),
      ])

      donationStats.value   = res2.data
      recentDonations.value = (Array.isArray(res3.data) ? res3.data : []).slice(0, 5)

      campaigns.value = (Array.isArray(res4.data) ? res4.data : [])
        .filter(c => c.type !== 'item' && c.goal_amount > 0)
        .map(c => ({
          ...c,
          percent: Math.min(
            Math.round((c.current_amount / c.goal_amount) * 100),
            100
          )
        }))
        .slice(0, 5)
    }

  } catch (err) {
    console.error("Dashboard error:", err)
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDashboard)

/* ── RESUBMIT HANDLER ── */
function onResubmitted(updatedFoundation) {
  foundation.value       = updatedFoundation
  foundationStatus.value = updatedFoundation.status
  showResubmitForm.value = false
}

/* ── COMPUTED ── */
const foundationInitials = computed(() => {
  if (!foundation.value?.name) return 'F'
  return foundation.value.name
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const stats = computed(() => [
  {
    label: "Total Campaigns",
    value: campaigns.value.length,
    bg: "#eff6ff", col: "#3b82f6",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`
  },
  {
    label: "Total Raised",
    value: "₱" + formatMoney(donationStats.value.total_monetary),
    bg: "#f0fdf4", col: "#059669",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
  },
  {
    label: "Item Donations",
    value: donationStats.value.total_items,
    bg: "#fefce8", col: "#ca8a04",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
  },
  {
    label: "Total Donors",
    value: donationStats.value.total_donors,
    bg: "#f5f3ff", col: "#7c3aed",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
  },
  {
    label: "Followers",
    value: followersCount.value,
    bg: "#fdf2f8", col: "#db2777",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
  },
])

/* ── HELPERS ── */
function formatMoney(v) { return Number(v || 0).toLocaleString() }

function formatDate(d) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("en-PH", {
    year: "numeric", month: "short", day: "numeric"
  })
}

function progressColor(pct) {
  if (pct >= 80) return "#059669"
  if (pct >= 50) return "#0F2D52"
  return "#3b82f6"
}

function statusLabel(s) {
  if (s === 'verified')             return 'Verified'
  if (s === 'pending_verification') return 'Pending'
  if (s === 'rejected')             return 'Rejected'
  if (s === 'suspended')            return 'Suspended'
  if (s === 'pending')              return 'Pending'
  if (s === 'received')             return 'Received'
  if (s === 'cancelled')            return 'Cancelled'
  return s
}

function toneForFoundationStatus(s) {
  if (s === 'verified')  return 'success'
  if (s === 'rejected')  return 'danger'
  if (s === 'suspended') return 'neutral'
  return 'warning' // pending_verification / under_review
}

function toneForDonationStatus(s) {
  if (s === 'received')  return 'success'
  if (s === 'cancelled') return 'danger'
  return 'warning' // pending
}
</script>

<style scoped>
.dashboard { padding: 24px; background: #f8fafc; min-height: 100%; box-sizing: border-box; }

/* BANNER */
.status-banner { background: linear-gradient(135deg, #fff7e6, #fff1d6); border: 1px solid #ffd28a; padding: 36px 28px; border-radius: 16px; text-align: center; }
.status-banner.rejected { background: linear-gradient(135deg, #fff5f5, #fee2e2); border-color: #fca5a5; }
.banner-icon { font-size: 2.5rem; margin-bottom: 12px; }
.status-banner h2 { margin: 0 0 10px; font-size: 1.2rem; font-weight: 800; color: #b7791f; }
.status-banner.rejected h2 { color: #dc2626; }
.status-banner p { margin: 0; font-size: 0.88rem; color: #7a5a1b; line-height: 1.7; max-width: 560px; margin: 0 auto; }
.status-banner.rejected p { color: #991b1b; }

.resubmit-btn { margin-top: 18px; padding: 11px 26px; background: #0F2D52; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: background 0.2s; }
.resubmit-btn:hover { background: #1565c0; }

/* FOUNDATION HEADER */
.foundation-header { display: flex; justify-content: space-between; align-items: center; background: white; border-radius: 14px; padding: 18px 22px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); }
.foundation-info   { display: flex; align-items: center; gap: 14px; }
.foundation-name   { font-size: 1rem; font-weight: 700; color: #0F2D52; margin: 0 0 3px; }
.foundation-cat    { font-size: 0.8rem; color: #94a3b8; margin: 0; }
.verified-badge    { font-size: 0.8rem; font-weight: 700; background: #f0fdf4; color: #059669; padding: 6px 14px; border-radius: 20px; border: 1px solid #bbf7d0; }

/* STAT CARDS */
.stat-cards { display: grid; grid-template-columns: repeat(5,1fr); gap: 16px; margin-bottom: 20px; }

/* LOWER */
.lower-section { display: grid; grid-template-columns: 1fr 320px; gap: 16px; }
.right-col { display: flex; flex-direction: column; }

/* PANEL */
.panel { background: white; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }
.panel-head  { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px 12px; border-bottom: 1px solid #f1f5f9; }
.panel-title { font-size: 0.92rem; font-weight: 700; color: #0F2D52; margin: 0; }
.panel-link  { font-size: 0.78rem; color: #3b82f6; text-decoration: none; font-weight: 500; }
.panel-link:hover { text-decoration: underline; }
.panel-body  { padding: 16px 20px; }

/* TABLE */
.donations-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.donations-table th { text-align: left; padding: 8px 10px; color: #94a3b8; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid #f1f5f9; }
.donations-table td { padding: 10px; border-bottom: 1px solid #f8fafc; color: #475569; }
.donations-table tbody tr:last-child td { border-bottom: none; }
.donations-table tbody tr:hover { background: #f8fafc; }

.donor-cell   { display: flex; align-items: center; gap: 8px; }
.donor-cell strong { display: block; color: #0F2D52; font-size: 0.82rem; font-weight: 600; }
.donor-cell small  { color: #94a3b8; font-size: 0.7rem; }
.campaign-name { font-size: 0.8rem; color: #0F2D52; font-weight: 500; max-width: 120px; }
.date-col { font-size: 0.75rem; color: #94a3b8; white-space: nowrap; }

.amount    { font-weight: 700; color: #059669; }
.item-info { font-size: 0.78rem; color: #0F2D52; }

/* PROGRESS */
.progress-list { display: flex; flex-direction: column; gap: 14px; }
.progress-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
.progress-name { font-size: 0.82rem; font-weight: 500; color: #0F2D52; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
.progress-pct  { font-size: 0.78rem; font-weight: 700; color: #0F2D52; flex-shrink: 0; }
.progress-bar-bg   { height: 7px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.progress-bar-fill { height: 100%; border-radius: 99px; transition: width 0.5s ease; }
.progress-foot { display: flex; justify-content: space-between; margin-top: 4px; font-size: 0.72rem; color: #0F2D52; font-weight: 600; }
.muted { color: #94a3b8; font-weight: 400; }

/* INFO LIST */
.info-list { padding: 8px 20px 16px; }
.info-row  { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.83rem; }
.info-row:last-child { border-bottom: none; }
.info-label { color: #94a3b8; font-size: 0.78rem; font-weight: 500; }
.pending-count { font-weight: 700; color: #ca8a04; background: #fefce8; padding: 2px 10px; border-radius: 20px; font-size: 0.78rem; }

@media (max-width: 1100px) {
  .stat-cards { grid-template-columns: repeat(3,1fr); }
}
@media (max-width: 700px) {
  .stat-cards { grid-template-columns: repeat(2,1fr); }
  .lower-section { grid-template-columns: 1fr; }
}
</style>