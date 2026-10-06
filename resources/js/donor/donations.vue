<template>
  <div class="page">
    <div class="container">

      <div class="page-head">
        <span class="kicker">Your Impact</span>
        <h1>My Donations</h1>
        <p>Track all your donations and their status</p>
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
          <h2 class="section-title">Our Foundations</h2>
          <router-link to="/donor/campaigns" class="see-all">See all campaigns →</router-link>
        </div>

        <div v-if="foundationsLoading" class="empty-state"><Spinner /></div>

        <EmptyState v-else-if="foundations.length === 0" icon="" message="No verified foundations yet." />

        <div v-else class="foundations-row">
          <FoundationBubble
            v-for="f in foundations" :key="f.id"
            :foundation="f" :size="76" :ringed="false"
          />
        </div>
      </div>

      <!-- FILTERS -->
      <div class="filters">
        <div class="tabs">
          <button
            v-for="t in tabs" :key="t.key"
            class="tab-btn" :class="{ active: activeTab === t.key }"
            @click="activeTab = t.key"
          >{{ t.label }} <span class="tab-count">{{ tabCount(t.key) }}</span></button>
        </div>
        <select v-model="filterType" class="filter-select">
          <option value="">All Types</option>
          <option value="monetary">💰 Monetary</option>
          <option value="item">📦 Item</option>
        </select>
      </div>

      <!-- LOADING -->
      <div v-if="isLoading" class="empty-state"><Spinner /><p>Loading...</p></div>

      <!-- EMPTY -->
      <EmptyState
        v-else-if="filtered.length === 0"
        icon="🎁" message="No donations found."
        action-label="Browse Campaigns" action-to="/donor/campaigns"
      />

      <!-- TABLE -->
      <div v-else class="table-card">
        <table>
          <thead>
            <tr>
              <th>Campaign</th>
              <th>Type</th>
              <th>Details</th>
              <th>Date</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="d in filtered" :key="d.id"
              :data-donation-id="d.id"
              :class="{ highlighted: highlightId === d.id }"
            >
              <td class="camp-cell">{{ d.campaign?.title }}</td>
              <td>
                <span class="type-badge" :class="d.type">
                  {{ d.type === 'monetary' ? '💰 Monetary' : '📦 Item' }}
                </span>
              </td>
              <td>
                <span v-if="d.type === 'monetary'" class="amount">₱{{ formatMoney(d.amount) }}</span>
                <span v-else class="item-info">{{ d.item_name }} × {{ d.item_quantity }}</span>
              </td>
              <td class="date-cell">{{ formatDate(d.donated_at) }}</td>
              <td>
                <span class="status-badge" :class="d.status"><span class="status-dot"></span>{{ statusLabel(d.status) }}</span>
                <button
                  v-if="canCancel(d)"
                  type="button"
                  class="cancel-link"
                  @click="askCancel(d)"
                >
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                  Cancel donation
                </button>
              </td>
              <td class="view-cell">
                <button type="button" class="view-btn" @click="openDetails(d)">View</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- DONATION DETAILS -->
    <div v-if="detailTarget" class="detail-overlay" @click.self="closeDetails">
      <div class="detail-modal" role="dialog" aria-modal="true">

        <div class="detail-head">
          <h3>Donation details</h3>
          <button type="button" class="detail-x" aria-label="Close" @click="closeDetails">×</button>
        </div>

        <div class="detail-status">
          <span class="status-badge" :class="detailTarget.status">
            <span class="status-dot"></span>{{ statusLabel(detailTarget.status) }}
          </span>
          <span class="detail-note">{{ statusNote(detailTarget.status) }}</span>
        </div>

        <div class="detail-list">
          <div class="detail-row">
            <span class="detail-label">Campaign</span>
            <span class="detail-value">{{ detailTarget.campaign?.title }}</span>
          </div>
          <div v-if="detailTarget.campaign?.foundation" class="detail-row">
            <span class="detail-label">Foundation</span>
            <span class="detail-value">{{ detailTarget.campaign.foundation }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Type</span>
            <span class="detail-value">{{ detailTarget.type === 'monetary' ? 'Monetary' : 'Item' }}</span>
          </div>

          <template v-if="detailTarget.type === 'monetary'">
            <div class="detail-row">
              <span class="detail-label">Amount</span>
              <span class="detail-value amount">₱{{ formatMoney(detailTarget.amount) }}</span>
            </div>
          </template>

          <template v-else>
            <div class="detail-row">
              <span class="detail-label">Item</span>
              <span class="detail-value">{{ detailTarget.item_name }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Quantity</span>
              <span class="detail-value">{{ detailTarget.item_quantity }}</span>
            </div>
            <div v-if="detailTarget.item_description" class="detail-row">
              <span class="detail-label">Description</span>
              <span class="detail-value">{{ detailTarget.item_description }}</span>
            </div>
            <div v-if="detailTarget.delivery_method" class="detail-row">
              <span class="detail-label">Delivery</span>
              <span class="detail-value">{{ deliveryLabel(detailTarget.delivery_method) }}</span>
            </div>
            <div v-if="detailTarget.delivery_address" class="detail-row">
              <span class="detail-label">Address</span>
              <span class="detail-value">{{ detailTarget.delivery_address }}</span>
            </div>
          </template>

          <div v-if="detailTarget.notes" class="detail-row">
            <span class="detail-label">Notes</span>
            <span class="detail-value">{{ detailTarget.notes }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">{{ formatDate(detailTarget.donated_at) }}</span>
          </div>
        </div>

        <!-- uploaded photo -->
        <div v-if="detailTarget.type === 'monetary' && detailTarget.proof_photo" class="detail-photo">
          <span class="detail-label">Proof of payment</span>
          <a :href="getImage(detailTarget.proof_photo)" target="_blank" rel="noopener">
            <img :src="getImage(detailTarget.proof_photo)" alt="Proof of payment" />
          </a>
        </div>
        <div v-if="detailTarget.type === 'item' && detailTarget.item_photo" class="detail-photo">
          <span class="detail-label">Item photo</span>
          <a :href="getImage(detailTarget.item_photo)" target="_blank" rel="noopener">
            <img :src="getImage(detailTarget.item_photo)" alt="Item photo" />
          </a>
        </div>

        <div class="detail-actions">
          <button
            v-if="canCancel(detailTarget)"
            type="button"
            class="cancel-link detail-cancel"
            @click="cancelFromDetails"
          >
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            Cancel donation
          </button>
          <button type="button" class="btn-keep" @click="closeDetails">Close</button>
        </div>

      </div>
    </div>

    <!-- CANCEL ITEM DONATION CONFIRMATION -->
    <div v-if="cancelTarget" class="cancel-overlay" @click.self="closeCancel">
      <div class="cancel-modal" role="dialog" aria-modal="true">
        <h3>Cancel this donation?</h3>
        <p class="cancel-text">
          You are cancelling
          <strong>{{ cancelTarget.item_name }} × {{ cancelTarget.item_quantity }}</strong>
          for <strong>{{ cancelTarget.campaign?.title }}</strong>.
          The foundation will be notified, and you can't undo this.
        </p>

        <p v-if="cancelError" class="cancel-error">{{ cancelError }}</p>

        <div class="cancel-actions">
          <button type="button" class="btn-keep" @click="closeCancel" :disabled="cancelling">
            Keep donation
          </button>
          <button type="button" class="btn-confirm-cancel" @click="confirmCancel" :disabled="cancelling">
            <span v-if="!cancelling">Yes, cancel</span>
            <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import Spinner from '../components/donor/spinner.vue'
import EmptyState from '../components/donor/emptystate.vue'
import FoundationBubble from '../components/donor/foundationbubble.vue'
import StatCard from '../components/donor/statcard.vue'
import { formatMoney, formatDate, getImage } from '../composable/donor.js'

const route  = useRoute()
const router = useRouter()

const donations          = ref([])
const isLoading          = ref(false)
const activeTab          = ref('all')
const filterType         = ref('')
const foundations        = ref([])
const foundationsLoading = ref(false)
const highlightId         = ref(route.query.highlight ? Number(route.query.highlight) : null)

/* ── CANCEL ITEM DONATION ── */
const cancelTarget = ref(null)
const cancelling   = ref(false)
const cancelError  = ref('')

/* ── DONATION DETAILS ── */
const detailTarget = ref(null)

const tabs = [
  { key: 'all',       label: 'All'       },
  { key: 'pending',   label: 'Pending'   },
  { key: 'received',  label: 'Received'  },
  { key: 'cancelled', label: 'Cancelled' },
]

async function load() {
  isLoading.value          = true
  foundationsLoading.value = true
  try {
    const [donRes, foundRes] = await Promise.all([
      api.get('/donor/donations'),
      api.get('/donor/foundations'),
    ])
    donations.value   = Array.isArray(donRes.data)   ? donRes.data   : []
    foundations.value = Array.isArray(foundRes.data) ? foundRes.data : []
  } catch (err) {
    console.error(err)
    donations.value   = []
    foundations.value = []
  } finally {
    isLoading.value          = false
    foundationsLoading.value = false
  }

  if (highlightId.value) locateHighlight()
}

onMounted(load)

async function locateHighlight() {
  // make sure the tab/type filters don't hide the highlighted row
  const target = donations.value.find(d => d.id === highlightId.value)
  if (target) {
    activeTab.value  = 'all'
    filterType.value = ''
  }

  await nextTick()
  const el = document.querySelector(`[data-donation-id="${highlightId.value}"]`)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })

  setTimeout(() => {
    highlightId.value = null
    router.replace({ query: { ...route.query, highlight: undefined } })
  }, 2500)
}

const filtered = computed(() =>
  donations.value.filter(d => {
    const matchTab  = activeTab.value === 'all' || d.status === activeTab.value
    const matchType = filterType.value === ''   || d.type   === filterType.value
    return matchTab && matchType
  })
)

function tabCount(key) {
  if (key === 'all') return donations.value.length
  return donations.value.filter(d => d.status === key).length
}

const stats = computed(() => [
  {
    label: "Total Donated",
    value: "₱" + formatMoney(donations.value.filter(d => d.type === 'monetary').reduce((s, d) => s + Number(d.amount || 0), 0)),
    bg: "linear-gradient(135deg, #d5f5e3, #a8e6c1)", col: "#0e5c36",
    icon: `<svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
  },
  {
    label: "Item Donations",
    value: donations.value.filter(d => d.type === 'item').length,
    bg: "linear-gradient(135deg, #dbeafe, #bfdbfe)", col: "#1e40af",
    icon: `<svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
  },
  {
    label: "Received",
    value: donations.value.filter(d => d.status === 'received').length,
    bg: "linear-gradient(135deg, #fef3c7, #fde68a)", col: "#92400e",
    icon: `<svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`
  },
])

function statusLabel(s) {
  return { pending: 'Pending', received: 'Received', cancelled: 'Cancelled' }[s] || s
}

/* ── DONATION DETAILS ── */
function openDetails(d) {
  detailTarget.value = d
}

function closeDetails() {
  detailTarget.value = null
}

function cancelFromDetails() {
  const d = detailTarget.value
  detailTarget.value = null
  if (d) askCancel(d)
}

function deliveryLabel(m) {
  return { pickup: 'Pick-up', dropoff: 'Drop-off' }[m] || m
}

function statusNote(s) {
  return {
    pending:   'Waiting for the foundation to confirm they received it.',
    received:  'The foundation confirmed receiving this donation.',
    cancelled: 'This donation was cancelled.',
  }[s] || ''
}

/* ── CANCEL ITEM DONATION ──
   Only pending ITEM donations can be cancelled by the donor.
   Monetary donations stay foundation-only. */
function canCancel(d) {
  return d.type === 'item' && d.status === 'pending'
}

function askCancel(d) {
  cancelError.value  = ''
  cancelTarget.value = d
}

function closeCancel() {
  if (cancelling.value) return
  cancelTarget.value = null
  cancelError.value  = ''
}

async function confirmCancel() {
  if (!cancelTarget.value) return
  cancelling.value  = true
  cancelError.value = ''

  const id = cancelTarget.value.id
  try {
    await api.patch(`/donor/donations/${id}/cancel`)

    // reflect the change right away: the badge, tab counts and stats update
    const row = donations.value.find(d => d.id === id)
    if (row) row.status = 'cancelled'

    cancelling.value   = false
    cancelTarget.value = null
  } catch (err) {
    cancelError.value = err.response?.data?.message || 'Could not cancel this donation. Please try again.'
    cancelling.value  = false
  }
}
</script>
<style scoped>
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 60px; }
.container { max-width: 1200px; margin: 0 auto; padding: 40px 24px; }

.page-head { margin-bottom: 28px; }
.kicker {
  display: inline-block; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.12em;
  text-transform: uppercase; color: #1a8a52; background: #e6f7ee; padding: 4px 12px;
  border-radius: 20px; margin-bottom: 10px;
}
.page-head h1 { font-size: 1.7rem; font-weight: 900; color: #0e5c36; margin: 0 0 5px; letter-spacing: -0.01em; }
.page-head p  { font-size: 0.87rem; color: #5b7568; margin: 0; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 28px; }

/* SECTION */
.section { margin-bottom: 28px; }
.section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.section-title { font-size: 1.02rem; font-weight: 800; color: #0e3d27; margin: 0; }
.see-all { font-size: 0.82rem; font-weight: 700; color: #1a8a52; text-decoration: none; transition: opacity 0.15s; }
.see-all:hover { opacity: 0.7; }

.empty-state { text-align: center; padding: 60px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 12px; background: white; border-radius: 18px; box-shadow: 0 2px 8px rgba(0,80,40,0.06); }

/* FOUNDATIONS ROW */
.foundations-row {
  display: flex;
  gap: 22px;
  overflow-x: auto;
  padding: 4px 4px 10px;
  scrollbar-width: none;
}
.foundations-row::-webkit-scrollbar { display: none; }

/* FILTERS */
.filters { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.tabs { display: flex; gap: 4px; background: white; padding: 5px; border-radius: 13px; box-shadow: 0 2px 8px rgba(0,80,40,0.07); }
.tab-btn { display: flex; align-items: center; gap: 6px; padding: 8px 15px; border: none; background: transparent; color: #64748b; font-size: 0.83rem; font-weight: 650; border-radius: 9px; cursor: pointer; transition: all 0.18s; }
.tab-btn:hover  { background: #f0fdf4; color: #1a8a52; }
.tab-btn.active { background: #1a8a52; color: white; box-shadow: 0 2px 8px rgba(26,138,82,0.3); }
.tab-count { font-size: 0.7rem; background: rgba(255,255,255,0.25); padding: 1px 7px; border-radius: 20px; }
.tab-btn:not(.active) .tab-count { background: #eef4f0; color: #94a3b8; }
.filter-select { padding: 10px 16px; border: 1.5px solid #e2e8f0; border-radius: 11px; font-size: 0.85rem; background: white; color: #0F2D52; outline: none; cursor: pointer; font-weight: 600; box-shadow: 0 2px 8px rgba(0,80,40,0.05); }

/* TABLE */
.table-card { background: white; border-radius: 18px; overflow: hidden; box-shadow: 0 3px 14px rgba(0,80,40,0.09); }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8fdfa; border-bottom: 1.5px solid #eef4f0; }
thead th { padding: 15px 20px; text-align: left; font-size: 0.71rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #8ba296; }
tbody tr { border-bottom: 1px solid #f5f9f6; transition: background 0.18s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f6fdf9; }
tbody tr.highlighted { background: #dcfce7; box-shadow: inset 3px 0 0 #1a8a52; }
tbody td { padding: 16px 20px; font-size: 0.86rem; color: #475569; }

.camp-cell { font-weight: 750; color: #0F2D52; max-width: 220px; }
.amount    { font-weight: 850; color: #059669; font-size: 0.92rem; }
.item-info { font-weight: 650; color: #0F2D52; }
.date-cell { white-space: nowrap; font-size: 0.81rem; color: #8ba296; font-weight: 600; }

.type-badge { font-size: 0.72rem; font-weight: 750; padding: 4px 11px; border-radius: 20px; }
.type-badge.monetary { background: #f0fdf4; color: #059669; }
.type-badge.item     { background: #eff6ff; color: #3b82f6; }

.status-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.73rem; font-weight: 750; padding: 4px 12px; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.status-badge.pending   { background: #fefce8; color: #92400e; }
.status-badge.pending .status-dot   { background: #ca8a04; }
.status-badge.received  { background: #f0fdf4; color: #059669; }
.status-badge.received .status-dot  { background: #059669; }
.status-badge.cancelled { background: #fef2f2; color: #dc2626; }
.status-badge.cancelled .status-dot { background: #dc2626; }

/* CANCEL ITEM DONATION (new — used only by the cancel link and its confirm modal) */
.cancel-link {
  display: inline-flex; align-items: center; gap: 5px; margin-top: 8px;
  padding: 5px 12px; border: 1.5px solid #fecaca; border-radius: 20px;
  background: #fff5f5; color: #dc2626;
  font-size: 0.73rem; font-weight: 750; font-family: inherit; cursor: pointer;
  transition: background 0.18s, color 0.18s, border-color 0.18s;
}
.cancel-link:hover { background: #dc2626; border-color: #dc2626; color: white; }
.cancel-link:focus-visible { outline: 2px solid #dc2626; outline-offset: 2px; }

.cancel-overlay {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(15,45,82,0.45);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.cancel-modal {
  background: white; border-radius: 16px; padding: 24px;
  max-width: 400px; width: 100%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.18);
}
.cancel-modal h3 { font-size: 1.02rem; font-weight: 800; color: #0F2D52; margin: 0 0 10px; }
.cancel-text { font-size: 0.85rem; color: #475569; line-height: 1.6; margin: 0 0 16px; }
.cancel-error {
  font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0;
  border-radius: 8px; padding: 9px 13px; margin: 0 0 12px;
}
.cancel-actions { display: flex; gap: 10px; justify-content: flex-end; }
.btn-keep, .btn-confirm-cancel {
  padding: 10px 18px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.btn-keep { border: 1.5px solid #e2e8f0; background: white; color: #64748b; }
.btn-keep:hover:not(:disabled) { background: #f8fafc; }
.btn-confirm-cancel { border: none; background: #dc2626; color: white; min-width: 110px; }
.btn-confirm-cancel:hover:not(:disabled) { background: #b91c1c; }
.btn-keep:disabled, .btn-confirm-cancel:disabled { opacity: 0.65; cursor: not-allowed; }

/* DONATION DETAILS (new — used only by the View button and the details modal) */
.view-cell { text-align: right; white-space: nowrap; }
.view-btn {
  padding: 6px 16px; border: 1.5px solid #cfe9db; border-radius: 20px;
  background: white; color: #1a8a52;
  font-size: 0.76rem; font-weight: 750; font-family: inherit; cursor: pointer;
  transition: background 0.18s, color 0.18s, border-color 0.18s;
}
.view-btn:hover { background: #1a8a52; border-color: #1a8a52; color: white; }
.view-btn:focus-visible { outline: 2px solid #1a8a52; outline-offset: 2px; }

.detail-overlay {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(15,45,82,0.45);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.detail-modal {
  background: white; border-radius: 16px; padding: 24px;
  max-width: 460px; width: 100%; max-height: 90vh; overflow-y: auto;
  box-shadow: 0 10px 30px rgba(0,0,0,0.18);
}
.detail-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.detail-head h3 { font-size: 1.02rem; font-weight: 800; color: #0F2D52; margin: 0; }
.detail-x { border: none; background: none; font-size: 1.5rem; line-height: 1; color: #94a3b8; cursor: pointer; padding: 0 4px; }
.detail-x:hover { color: #475569; }

.detail-status { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
.detail-note { font-size: 0.78rem; color: #8ba296; }

.detail-list { border-top: 1px solid #f1f5f9; }
.detail-row { display: grid; grid-template-columns: 110px 1fr; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
.detail-label { font-size: 0.74rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #8ba296; padding-top: 2px; }
.detail-value { color: #0F2D52; font-weight: 600; word-break: break-word; }

.detail-photo { margin-top: 16px; }
.detail-photo img { display: block; margin-top: 8px; max-width: 100%; max-height: 220px; border-radius: 10px; border: 1px solid #e2e8f0; object-fit: cover; }

.detail-actions { display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 20px; }
.detail-cancel { margin-top: 0; }

@media (max-width: 700px) {
  .stats-row { grid-template-columns: 1fr; }
  .table-card { overflow-x: auto; }
  table { min-width: 620px; }
}
</style>