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
              </td>
            </tr>
          </tbody>
        </table>
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
import { formatMoney, formatDate } from '../composable/donor.js'

const route  = useRoute()
const router = useRouter()

const donations          = ref([])
const isLoading          = ref(false)
const activeTab          = ref('all')
const filterType         = ref('')
const foundations        = ref([])
const foundationsLoading = ref(false)
const highlightId         = ref(route.query.highlight ? Number(route.query.highlight) : null)

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

@media (max-width: 700px) {
  .stats-row { grid-template-columns: 1fr; }
  .table-card { overflow-x: auto; }
  table { min-width: 620px; }
}
</style>