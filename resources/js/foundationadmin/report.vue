<template>
  <div class="page">

    <PageHeader title="Reports" subtitle="Overview of your foundation's performance" />

    <!-- PENDING VERIFICATION BANNER -->
    <PendingVerificationBanner v-if="!isVerified && !checkingStatus">
      Reports will be available here once your foundation has been verified by the super admin.
    </PendingVerificationBanner>

    <!-- LOADING -->
    <EmptyState v-else-if="isLoading" loading message="Loading reports..." />

    <div v-else>

      <!-- SUMMARY CARDS -->
      <div class="stats-row">
        <StatCard
          v-for="s in summaryCards" :key="s.label"
          :icon="s.icon" :value="s.value" :label="s.label"
          :bg="s.bg" :col="s.col" :icon-size="40"
        />
      </div>

      <!-- MONTHLY BREAKDOWN -->
      <div class="panel" style="margin-bottom: 20px;">
        <div class="panel-head">
          <h3 class="panel-title">Monthly Breakdown (Last 6 Months)</h3>
        </div>
        <div class="panel-body">
          <EmptyState v-if="monthly.length === 0" message="No data yet." compact />
          <div v-else class="monthly-grid">
            <div v-for="m in monthly" :key="m.month" class="month-card">
              <div class="month-label">{{ m.month }}</div>
              <div class="month-raised">₱{{ formatMoney(m.monetary) }}</div>
              <div class="month-meta">
                <span class="meta-item monetary">₱{{ formatMoney(m.monetary) }} raised</span>
                <span class="meta-item item">{{ m.items }} items</span>
                <span class="meta-item total">{{ m.total }} total</span>
              </div>
              <div class="month-bar">
                <div
                  class="month-bar-fill"
                  :style="{ height: monthBarHeight(m.monetary) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PER CAMPAIGN -->
      <div class="panel">
        <div class="panel-head">
          <h3 class="panel-title">Campaign Performance</h3>
        </div>
        <div class="panel-body">
          <EmptyState v-if="perCampaign.length === 0" icon="📋" message="No campaigns yet." compact />
          <table v-else class="report-table">
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Status</th>
                <th>Type</th>
                <th>Goal</th>
                <th>Raised</th>
                <th>Progress</th>
                <th>Items</th>
                <th>Donors</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in perCampaign" :key="c.id">
                <td class="campaign-name">{{ c.title }}</td>
                <td>
                  <Badge :label="statusLabel(c.status)" :tone="toneForCampaignStatus(c.status)" />
                </td>
                <td>
                  <Badge :label="c.type" :tone="toneForType(c.type)" />
                </td>
                <td>
                  <span v-if="c.type !== 'item'">₱{{ formatMoney(c.goal_amount) }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="raised-col">
                  <span v-if="c.type !== 'item'">₱{{ formatMoney(c.raised) }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td style="min-width: 140px;">
                  <div v-if="c.type !== 'item'" class="inline-progress">
                    <div class="inline-bar">
                      <div
                        class="inline-fill"
                        :style="{ width: c.percent + '%', background: progressColor(c.percent) }"
                      ></div>
                    </div>
                    <span class="inline-pct">{{ c.percent }}%</span>
                  </div>
                  <span v-else class="muted">—</span>
                </td>
                <td class="center">{{ c.items }}</td>
                <td class="center">{{ c.donors }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"
import PageHeader from "../components/foundation-admin/pageheader.vue"
import PendingVerificationBanner from "../components/foundation-admin/pendingverificationbanner.vue"
import StatCard from "../components/foundation-admin/statcard.vue"
import EmptyState from "../components/foundation-admin/emptystate.vue"
import Badge from "../components/foundation-admin/badge.vue"

const isLoading  = ref(false)
const summary    = ref({})
const monthly    = ref([])
const perCampaign = ref([])

const isVerified     = ref(false)
const checkingStatus = ref(true)

/* ── FOUNDATION STATUS ── */
async function loadFoundationStatus() {
  checkingStatus.value = true
  try {
    const res = await api.get("/foundation/dashboard")
    isVerified.value = res.data?.foundation?.status === 'verified'
  } catch (err) {
    console.error("Failed to load foundation status:", err)
    isVerified.value = false
  } finally {
    checkingStatus.value = false
  }
}

async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/reports")
    summary.value     = res.data.summary    || {}
    monthly.value     = res.data.monthly    || []
    perCampaign.value = res.data.per_campaign || []
  } catch (err) {
    console.error("Failed to load reports:", err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadFoundationStatus()
  if (isVerified.value) load()
})

const summaryCards = computed(() => [
  {
    label: "Total Raised",
    value: "₱" + formatMoney(summary.value.total_raised),
    bg: "#f0fdf4", col: "#059669",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
  },
  {
    label: "Total Donations",
    value: summary.value.total_donations ?? 0,
    bg: "#eff6ff", col: "#3b82f6",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
  },
  {
    label: "Total Donors",
    value: summary.value.total_donors ?? 0,
    bg: "#f5f3ff", col: "#7c3aed",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>`
  },
  {
    label: "Item Donations",
    value: summary.value.total_items ?? 0,
    bg: "#fefce8", col: "#ca8a04",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
  },
  {
    label: "Total Campaigns",
    value: summary.value.total_campaigns ?? 0,
    bg: "#fdf2f8", col: "#db2777",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`
  },
  {
    label: "Active Campaigns",
    value: summary.value.active_campaigns ?? 0,
    bg: "#ecfdf5", col: "#10b981",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>`
  },
])

function monthBarHeight(val) {
  const max = Math.max(...monthly.value.map(m => m.monetary), 1)
  return Math.round((val / max) * 100)
}

function formatMoney(v) { return Number(v || 0).toLocaleString() }

function progressColor(pct) {
  if (pct >= 80) return "#059669"
  if (pct >= 50) return "#0F2D52"
  return "#3b82f6"
}

function statusLabel(s) {
  return { active: "Active", completed: "Completed", draft: "Draft", cancelled: "Cancelled" }[s] || s
}

function toneForCampaignStatus(s) {
  if (s === 'active')    return 'success'
  if (s === 'completed') return 'info'
  if (s === 'cancelled') return 'danger'
  return 'neutral' // draft
}

function toneForType(t) {
  if (t === 'monetary') return 'success'
  if (t === 'item')     return 'info'
  return 'purple' // both
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.stats-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 20px; }

.panel { background: white; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }
.panel-head  { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px 12px; border-bottom: 1px solid #f1f5f9; }
.panel-title { font-size: 0.92rem; font-weight: 700; color: #0F2D52; margin: 0; }
.panel-body  { padding: 20px; }

/* MONTHLY GRID */
.monthly-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
.month-card   { background: #f8fafc; border-radius: 12px; padding: 14px 12px; display: flex; flex-direction: column; gap: 6px; }
.month-label  { font-size: 0.72rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
.month-raised { font-size: 1rem; font-weight: 800; color: #0F2D52; }
.month-meta   { display: flex; flex-direction: column; gap: 3px; }
.meta-item    { font-size: 0.7rem; font-weight: 500; }
.meta-item.monetary { color: #059669; }
.meta-item.item     { color: #3b82f6; }
.meta-item.total    { color: #94a3b8; }
.month-bar    { height: 60px; background: #e2e8f0; border-radius: 6px; overflow: hidden; display: flex; align-items: flex-end; margin-top: 4px; }
.month-bar-fill { width: 100%; background: linear-gradient(180deg, #3b82f6, #0F2D52); border-radius: 6px; transition: height 0.5s ease; min-height: 4px; }

/* TABLE */
.report-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.report-table th { text-align: left; padding: 10px 12px; color: #94a3b8; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid #f1f5f9; }
.report-table td { padding: 12px; border-bottom: 1px solid #f8fafc; color: #475569; vertical-align: middle; }
.report-table tbody tr:last-child td { border-bottom: none; }
.report-table tbody tr:hover { background: #f8fafc; }

.campaign-name { font-weight: 600; color: #0F2D52; max-width: 160px; }
.raised-col    { font-weight: 700; color: #059669; }
.center        { text-align: center; }
.muted         { color: #cbd5e1; }

.inline-progress { display: flex; align-items: center; gap: 8px; }
.inline-bar  { flex: 1; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.inline-fill { height: 100%; border-radius: 99px; transition: width 0.5s; }
.inline-pct  { font-size: 0.72rem; font-weight: 700; color: #0F2D52; white-space: nowrap; }
</style>