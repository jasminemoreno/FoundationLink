<template>
  <div class="page">

    <!-- HEADER -->
    <PageHeader title="Donations" subtitle="Track and manage donations to your campaigns" />

    <!-- PENDING VERIFICATION BANNER -->
    <PendingVerificationBanner v-if="!isVerified && !checkingStatus">
      Donation data will appear here once your foundation has been verified by the super admin.
    </PendingVerificationBanner>

    <template v-else>

    <!-- STATS -->
    <div class="stats-row">
      <StatCard
        v-for="s in stats" :key="s.label"
        :icon="s.icon" :value="s.value" :label="s.label"
        :bg="s.bg" :col="s.col"
      />
    </div>

    <!-- FILTERS -->
    <div class="filters">
      <div class="filter-left">
        <!-- type tabs -->
        <div class="tabs">
          <button
            v-for="t in typeTabs" :key="t.key"
            class="tab-btn" :class="{ active: activeType === t.key }"
            @click="activeType = t.key"
          >
            {{ t.label }}
            <span class="tab-count">{{ typeCount(t.key) }}</span>
          </button>
        </div>

        <!-- status filter -->
        <select v-model="filterStatus" class="filter-select">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="received">Received</option>
          <option value="cancelled">Cancelled</option>
        </select>

        <!-- campaign filter -->
        <select v-model="filterCampaign" class="filter-select">
          <option value="">All Campaigns</option>
          <option v-for="c in campaignOptions" :key="c.id" :value="c.id">
            {{ c.title }}
          </option>
        </select>
      </div>

      <div class="search-box">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="search" placeholder="Search donor or campaign..." />
      </div>
    </div>

    <!-- LOADING -->
    <EmptyState v-if="isLoading" loading message="Loading donations..." />

    <!-- EMPTY -->
    <EmptyState v-else-if="filtered.length === 0" icon="🎁" message="No donations found." />

    <!-- TABLE -->
    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>Donor</th>
            <th>Campaign</th>
            <th>Type</th>
            <th>Details</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in filtered" :key="d.id">

            <!-- DONOR -->
            <td>
              <div class="donor-cell">
                <AvatarInitials :initials="d.donor.initials" :seed="d.donor.id" :size="36" :src="d.donor.profile_photo || ''" />
                <div>
                  <strong>{{ d.donor.name }}</strong>
                  <small>{{ d.donor.email }}</small>
                </div>
              </div>
            </td>

            <!-- CAMPAIGN -->
            <td>
              <span class="campaign-name">{{ d.campaign.title }}</span>
            </td>

            <!-- TYPE -->
            <td>
              <Badge :label="typeLabel(d.type)" :tone="d.type === 'monetary' ? 'success' : 'info'" />
            </td>

            <!-- DETAILS -->
            <td>
              <div v-if="d.type === 'monetary'" class="amount">
                ₱{{ formatMoney(d.amount) }}
              </div>
              <div v-else class="item-details">
                <span class="item-name">{{ d.item_name }}</span>
                <span class="item-qty">x{{ d.item_quantity }}</span>
              </div>
            </td>

            <!-- DATE -->
            <td class="date-cell">{{ formatDate(d.donated_at) }}</td>

            <!-- STATUS -->
            <td>
              <Badge :label="statusLabel(d.status)" :tone="toneForDonationStatus(d.status)" />
            </td>

            <!-- ACTIONS -->
            <td>
              <div class="actions">
                <button class="action-btn view" title="View" @click="openView(d)">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>

                <button
                  v-if="d.status === 'pending'"
                  class="action-btn receive"
                  title="Mark as Received"
                  @click="updateStatus(d, 'received')"
                >
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </button>

                <button
                  v-if="d.status === 'pending'"
                  class="action-btn cancel"
                  title="Cancel"
                  @click="updateStatus(d, 'cancelled')"
                >
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                  </svg>
                </button>
              </div>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    </template>

    <!-- VIEW MODAL -->
    <FoundationModal
      v-if="viewDonation"
      title="Donation Details"
      width="480px"
      @close="viewDonation = null"
    >
      <!-- donor info -->
      <div class="modal-donor">
        <AvatarInitials :initials="viewDonation.donor.initials" :seed="viewDonation.donor.id" :size="48" :src="viewDonation.donor.profile_photo || ''" />
        <div>
          <div class="donor-name">{{ viewDonation.donor.name }}</div>
          <div class="donor-email">{{ viewDonation.donor.email }}</div>
        </div>
        <span class="donation-ref">#{{ viewDonation.id }}</span>
      </div>

      <!-- DETAILS -->
      <div class="detail-section">
        <div class="detail-section-title">Details</div>

        <div class="detail-row">
          <span class="detail-label">Campaign</span>
          <span class="detail-value">{{ viewDonation.campaign.title }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Status</span>
          <Badge :label="statusLabel(viewDonation.status)" :tone="toneForDonationStatus(viewDonation.status)" />
        </div>
        <div class="detail-row">
          <span class="detail-label">Type</span>
          <Badge :label="typeLabel(viewDonation.type)" :tone="viewDonation.type === 'monetary' ? 'success' : 'info'" />
        </div>
        <div class="detail-row">
          <span class="detail-label">Date</span>
          <span class="detail-value">{{ formatDate(viewDonation.donated_at) }}</span>
        </div>

        <template v-if="viewDonation.type === 'monetary'">
          <div class="detail-row">
            <span class="detail-label">Amount</span>
            <span class="detail-amount">₱{{ formatMoney(viewDonation.amount) }}</span>
          </div>
        </template>
        <template v-else>
          <div class="detail-row">
            <span class="detail-label">Item</span>
            <span class="detail-value">{{ viewDonation.item_name }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Quantity</span>
            <span class="detail-value">{{ viewDonation.item_quantity }}</span>
          </div>
          <div class="detail-row detail-row-stack" v-if="viewDonation.item_description">
            <span class="detail-label">Description</span>
            <span class="detail-value">{{ viewDonation.item_description }}</span>
          </div>
        </template>
      </div>

      <!-- VERIFICATION -->
      <div class="detail-section">
        <div class="detail-section-title">Verification</div>

        <template v-if="viewDonation.type === 'monetary'">
          <div
            v-if="viewDonation.proof_photo"
            class="verify-row"
            @click="openImage(viewDonation.proof_photo, 'Proof of Payment')"
          >
            <img :src="getImage(viewDonation.proof_photo)" class="verify-thumb-sm" alt="Proof of payment" />
            <div class="verify-meta">
              <div class="verify-meta-title">Payment receipt</div>
              <div class="verify-meta-sub">Uploaded by donor</div>
            </div>
            <span class="verify-view-btn">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              View
            </span>
          </div>
          <div v-else class="verify-row verify-row-empty">
            <span class="verify-empty-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
            </span>
            <div class="verify-meta">
              <div class="verify-meta-title verify-meta-title-muted">No proof of payment uploaded</div>
              <div class="verify-meta-sub">This donation has not been verified</div>
            </div>
          </div>
        </template>

        <template v-else>
          <div
            v-if="viewDonation.item_photo"
            class="verify-row"
            @click="openImage(viewDonation.item_photo, 'Item Photo')"
          >
            <img :src="getImage(viewDonation.item_photo)" class="verify-thumb-sm" alt="Donated item" />
            <div class="verify-meta">
              <div class="verify-meta-title">Item photo</div>
              <div class="verify-meta-sub">Uploaded by donor</div>
            </div>
            <span class="verify-view-btn">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              View
            </span>
          </div>
          <div v-else class="verify-row verify-row-empty">
            <span class="verify-empty-icon">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
            </span>
            <div class="verify-meta">
              <div class="verify-meta-title verify-meta-title-muted">No item photo uploaded</div>
              <div class="verify-meta-sub">This donation has not been verified</div>
            </div>
          </div>
        </template>
      </div>

      <!-- NOTES -->
      <div class="detail-section" v-if="viewDonation.notes">
        <div class="detail-section-title">Notes</div>
        <p class="detail-notes">{{ viewDonation.notes }}</p>
      </div>

      <template #footer>
        <button class="btn-cancel" @click="viewDonation = null">Close</button>
        <button
          v-if="viewDonation.status === 'pending'"
          class="btn-danger"
          @click="updateStatus(viewDonation, 'cancelled'); viewDonation = null"
        >Cancel Donation</button>
        <button
          v-if="viewDonation.status === 'pending'"
          class="btn-save"
          @click="updateStatus(viewDonation, 'received'); viewDonation = null"
        >Mark as Received</button>
      </template>
    </FoundationModal>

    <!-- IMAGE LIGHTBOX (proof of payment / item photo enlarge) -->
    <div v-if="enlargedImage" class="lightbox-overlay" @click.self="closeLightbox">
      <div class="lightbox-panel">
        <div class="lightbox-head">
          <span>{{ enlargedLabel }}</span>
          <button class="lightbox-close" @click="closeLightbox">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
        <img :src="enlargedImage" class="lightbox-img" alt="Enlarged verification photo" />
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
import AvatarInitials from "../components/foundation-admin/avatarinitials.vue"
import Badge from "../components/foundation-admin/badge.vue"
import FoundationModal from "../components/foundation-admin/basemodal.vue"

const donations      = ref([])
const isLoading      = ref(false)
const search         = ref("")
const activeType     = ref("all")
const filterStatus   = ref("")
const filterCampaign = ref("")
const viewDonation   = ref(null)
const enlargedImage  = ref(null)
const enlargedLabel  = ref("")

const isVerified     = ref(false)
const checkingStatus = ref(true)

const typeTabs = [
  { key: "all",      label: "All"      },
  { key: "monetary", label: "Monetary" },
  { key: "item",     label: "Items"    },
]

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

/* ── FETCH ── */
async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/donations")
    donations.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error("Failed to load donations:", err)
    donations.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadFoundationStatus()
  if (isVerified.value) load()
})

/* ── CAMPAIGN OPTIONS for filter dropdown ── */
const campaignOptions = computed(() => {
  const seen = new Set()
  return donations.value
    .filter(d => {
      if (seen.has(d.campaign.id)) return false
      seen.add(d.campaign.id)
      return true
    })
    .map(d => ({ id: d.campaign.id, title: d.campaign.title }))
})

/* ── FILTER ── */
const filtered = computed(() =>
  donations.value.filter(d => {
    const q = search.value.toLowerCase()
    const matchSearch =
      (d.donor.name   || "").toLowerCase().includes(q) ||
      (d.donor.email  || "").toLowerCase().includes(q) ||
      (d.campaign.title || "").toLowerCase().includes(q)

    const matchType     = activeType.value     === "all" || d.type     === activeType.value
    const matchStatus   = filterStatus.value   === ""    || d.status   === filterStatus.value
    const matchCampaign = filterCampaign.value === ""    || d.campaign.id === filterCampaign.value

    return matchSearch && matchType && matchStatus && matchCampaign
  })
)

function typeCount(key) {
  if (key === "all") return donations.value.length
  return donations.value.filter(d => d.type === key).length
}

/* ── STATS ── */
const stats = computed(() => {
  const monetary = donations.value.filter(d => d.type === "monetary")
  const items    = donations.value.filter(d => d.type === "item")
  const pending  = donations.value.filter(d => d.status === "pending")
  const received = donations.value.filter(d => d.status === "received")

  return [
    {
      label: "Total Raised",
      value: "₱" + formatMoney(monetary.reduce((s, d) => s + Number(d.amount || 0), 0)),
      bg: "#f0fdf4", col: "#059669",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
    },
    {
      label: "Item Donations",
      value: items.length,
      bg: "#eff6ff", col: "#3b82f6",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
    },
    {
      label: "Pending",
      value: pending.length,
      bg: "#fefce8", col: "#ca8a04",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`
    },
    {
      label: "Received",
      value: received.length,
      bg: "#f5f3ff", col: "#7c3aed",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`
    },
  ]
})

/* ── UPDATE STATUS ── */
async function updateStatus(d, status) {
  try {
    await api.patch(`/foundation/donations/${d.id}/status`, { status })
    const target = donations.value.find(x => x.id === d.id)
    if (target) target.status = status
  } catch (err) {
    console.error("Status update failed:", err)
  }
}

/* ── VIEW ── */
function openView(d) { viewDonation.value = { ...d } }

/* ── VERIFICATION IMAGE (proof of payment / item photo) ── */
function getImage(path) { return path ? `http://127.0.0.1:8000/storage/${path}` : '' }
function openImage(path, label) {
  enlargedImage.value = getImage(path)
  enlargedLabel.value = label
}
function closeLightbox() {
  enlargedImage.value = null
  enlargedLabel.value = ""
}

/* ── HELPERS ── */
function formatMoney(v) { return Number(v || 0).toLocaleString() }

function formatDate(d) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("en-PH", {
    year: "numeric", month: "short", day: "numeric"
  })
}

function typeLabel(t) {
  return { monetary: "Monetary", item: "Item" }[t] || t
}

function statusLabel(s) {
  return { pending: "Pending", received: "Received", cancelled: "Cancelled" }[s] || s
}

function toneForDonationStatus(s) {
  if (s === 'received')  return 'success'
  if (s === 'cancelled') return 'danger'
  return 'warning'
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }

/* FILTERS */
.filters { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.filter-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.tabs { display: flex; gap: 4px; background: white; padding: 4px; border-radius: 12px; border: 1px solid #e2e8f0; }
.tab-btn { display: flex; align-items: center; gap: 6px; padding: 7px 14px; border: none; background: transparent; color: #64748b; font-size: 0.83rem; font-weight: 500; border-radius: 8px; cursor: pointer; transition: all 0.15s; }
.tab-btn:hover  { background: #f1f5f9; color: #1e293b; }
.tab-btn.active { background: #0F2D52; color: white; }
.tab-count { font-size: 0.7rem; background: #f1f5f9; color: #64748b; padding: 1px 6px; border-radius: 20px; }
.tab-btn.active .tab-count { background: rgba(255,255,255,0.2); color: white; }

.filter-select { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.83rem; background: white; color: #0F2D52; outline: none; cursor: pointer; }

.search-box { display: flex; align-items: center; gap: 8px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 14px; color: #94a3b8; }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.85rem; width: 220px; color: #0F2D52; }

/* TABLE */
.table-card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }

table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 1px solid #f1f5f9; }
thead th { padding: 14px 16px; text-align: left; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }
tbody td { padding: 12px 16px; font-size: 0.84rem; color: #475569; }

/* DONOR CELL */
.donor-cell   { display: flex; align-items: center; gap: 10px; }
.donor-cell strong { display: block; color: #0F2D52; font-size: 0.86rem; }
.donor-cell small  { color: #94a3b8; font-size: 0.74rem; }

.campaign-name { font-size: 0.83rem; color: #0F2D52; font-weight: 500; }

/* AMOUNT / ITEM */
.amount      { font-size: 0.95rem; font-weight: 700; color: #059669; }
.item-details { display: flex; align-items: center; gap: 6px; }
.item-name   { font-weight: 600; color: #0F2D52; font-size: 0.83rem; }
.item-qty    { font-size: 0.75rem; color: #94a3b8; background: #f1f5f9; padding: 2px 6px; border-radius: 6px; }

.date-cell { font-size: 0.8rem; color: #64748b; white-space: nowrap; }

/* ACTIONS */
.actions    { display: flex; gap: 6px; }
.action-btn { width: 29px; height: 29px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn.view    { background: #eff6ff; color: #3b82f6; }
.action-btn.receive { background: #f0fdf4; color: #059669; }
.action-btn.cancel  { background: #fef2f2; color: #dc2626; }
.action-btn:hover   { opacity: 0.75; transform: scale(1.1); }

/* MODAL */
.modal-donor { display: flex; align-items: center; gap: 12px; padding-bottom: 16px; margin-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
.donor-name  { font-size: 0.95rem; font-weight: 600; color: #0F2D52; }
.donor-email { font-size: 0.8rem; color: #94a3b8; margin-top: 2px; }
.donation-ref { margin-left: auto; font-size: 0.75rem; font-weight: 600; color: #94a3b8; font-variant-numeric: tabular-nums; }

.detail-section { padding: 16px 0; border-bottom: 1px solid #f1f5f9; }
.detail-section:last-of-type { border-bottom: none; padding-bottom: 4px; }
.detail-section-title {
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
  color: #94a3b8; margin-bottom: 10px;
}

.detail-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 7px 0;
}
.detail-row-stack { align-items: flex-start; }
.detail-label { font-size: 0.8rem; color: #64748b; flex-shrink: 0; }
.detail-value { font-size: 0.84rem; font-weight: 600; color: #0F2D52; text-align: right; }
.detail-row-stack .detail-value { text-align: left; margin-top: 4px; flex-basis: 100%; }
.detail-amount { font-size: 1.05rem; font-weight: 800; color: #059669; }

.detail-notes {
  font-size: 0.84rem; color: #475569; line-height: 1.55;
  background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px;
  padding: 10px 12px; margin: 0;
}

/* DONATION VERIFICATION (proof of payment / item photo) */
.verify-row {
  display: flex; align-items: center; gap: 12px;
  padding: 10px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.verify-row:hover { border-color: #cbd5e1; background: #f8fafc; }

.verify-thumb-sm {
  width: 48px; height: 48px; border-radius: 8px;
  object-fit: cover; flex-shrink: 0;
  border: 1px solid #e2e8f0;
}

.verify-meta { flex: 1; min-width: 0; }
.verify-meta-title { font-size: 0.83rem; font-weight: 600; color: #0F2D52; }
.verify-meta-sub { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }

.verify-view-btn {
  display: flex; align-items: center; gap: 5px;
  font-size: 0.76rem; font-weight: 600; color: #0F2D52;
  flex-shrink: 0; padding: 6px 10px;
  border: 1px solid #e2e8f0; border-radius: 8px;
}
.verify-row:hover .verify-view-btn { border-color: #0F2D52; }

.verify-row-empty { cursor: default; background: #f8fafc; }
.verify-row-empty:hover { border-color: #e2e8f0; background: #f8fafc; }
.verify-empty-icon {
  width: 48px; height: 48px; border-radius: 8px;
  background: #f1f5f9; color: #94a3b8;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.verify-meta-title-muted { color: #64748b; }

.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-save   { padding: 9px 20px; border: none; background: #059669; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-danger { padding: 9px 20px; border: none; background: #dc2626; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }

/* IMAGE LIGHTBOX */
.lightbox-overlay {
  position: fixed; inset: 0; z-index: 300;
  background: rgba(15,23,42,0.75);
  backdrop-filter: blur(3px);
  display: flex; align-items: center; justify-content: center;
  padding: 32px;
}
.lightbox-panel {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  max-width: 90vw; max-height: 90vh;
  box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  display: flex; flex-direction: column;
}
.lightbox-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.lightbox-head span { font-size: 0.78rem; font-weight: 600; color: #475569; }
.lightbox-close {
  width: 26px; height: 26px; border-radius: 7px;
  border: 1px solid #e2e8f0; background: transparent;
  color: #64748b; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.lightbox-close:hover { background: #f1f5f9; color: #0F2D52; }
.lightbox-img {
  display: block;
  max-width: 90vw; max-height: calc(90vh - 49px);
  object-fit: contain;
}
</style>