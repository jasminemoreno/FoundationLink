<template>
  <div class="page">

    <!-- HEADER -->
    <PageHeader title="Donors" subtitle="People who have donated to your campaigns">
      <template v-if="isVerified">
        <div class="search-box">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input v-model="search" placeholder="Search donors..." />
        </div>
        <select v-model="filterType" class="filter-select">
          <option value="">All Donors</option>
          <option value="monetary">Monetary Only</option>
          <option value="item">Item Only</option>
        </select>
      </template>
    </PageHeader>

    <!-- PENDING VERIFICATION BANNER -->
    <PendingVerificationBanner v-if="!isVerified && !checkingStatus">
      Donor data will appear here once your foundation has been verified by the super admin.
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

    <!-- LOADING -->
    <EmptyState v-if="isLoading" loading message="Loading donors..." />

    <!-- EMPTY -->
    <EmptyState v-else-if="filtered.length === 0" icon="👥" message="No donors found." />

    <!-- TABLE -->
    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>Donor</th>
            <th>Campaigns</th>
            <th>Total Donations</th>
            <th>Total Raised</th>
            <th>Items Donated</th>
            <th>Last Donated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in filtered" :key="d.id">

            <!-- DONOR -->
            <td>
              <div class="donor-cell">
                <AvatarInitials :initials="d.initials" :seed="d.id" :size="36" :src="d.profile_photo || ''" />
                <div>
                  <strong>{{ d.name }}</strong>
                  <small>{{ d.email }}</small>
                </div>
              </div>
            </td>

            <!-- CAMPAIGNS -->
            <td>
              <div class="campaigns-cell">
                <span
                  v-for="(c, i) in d.campaigns.slice(0, 2)" :key="i"
                  class="campaign-tag"
                >{{ c }}</span>
                <span v-if="d.campaigns.length > 2" class="campaign-more">
                  +{{ d.campaigns.length - 2 }}
                </span>
              </div>
            </td>

            <!-- TOTAL DONATIONS -->
            <td>
              <span class="count-badge">{{ d.total_donations }}</span>
            </td>

            <!-- TOTAL RAISED -->
            <td>
              <strong class="amount">
                {{ d.total_donated > 0 ? '₱' + formatMoney(d.total_donated) : '—' }}
              </strong>
            </td>

            <!-- ITEMS -->
            <td>
              <span v-if="d.total_items > 0" class="item-count">
                {{ d.total_items }} item{{ d.total_items > 1 ? 's' : '' }}
              </span>
              <span v-else class="muted">—</span>
            </td>

            <!-- LAST DONATED -->
            <td class="date-cell">{{ formatDate(d.last_donated) }}</td>

            <!-- ACTIONS -->
            <td>
              <button class="action-btn view" title="View Profile" @click="openView(d)">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    </template>

    <!-- VIEW MODAL -->
    <FoundationModal
      v-if="viewDonor"
      title="Donor Profile"
      width="460px"
      @close="viewDonor = null"
    >
      <!-- avatar + name -->
      <div class="modal-donor-hero">
        <AvatarInitials :initials="viewDonor.initials" :seed="viewDonor.id" :size="56" :src="viewDonor.profile_photo || ''" />
        <div>
          <div class="donor-hero-name">{{ viewDonor.name }}</div>
          <div class="donor-hero-email">{{ viewDonor.email }}</div>
          <div class="donor-hero-phone" v-if="viewDonor.phone">
            📞 {{ viewDonor.phone }}
          </div>
        </div>
      </div>

      <!-- stats -->
      <div class="donor-stats">
        <div class="ds-item">
          <span class="ds-val">{{ viewDonor.total_donations }}</span>
          <span class="ds-label">Donations</span>
        </div>
        <div class="ds-item">
          <span class="ds-val">₱{{ formatMoney(viewDonor.total_donated) }}</span>
          <span class="ds-label">Total Raised</span>
        </div>
        <div class="ds-item">
          <span class="ds-val">{{ viewDonor.total_items }}</span>
          <span class="ds-label">Items Given</span>
        </div>
        <div class="ds-item">
          <span class="ds-val">{{ viewDonor.campaigns.length }}</span>
          <span class="ds-label">Campaigns</span>
        </div>
      </div>

      <!-- campaigns supported -->
      <div class="modal-section">
        <div class="section-label">Campaigns Supported</div>
        <div class="campaigns-list">
          <span
            v-for="(c, i) in viewDonor.campaigns" :key="i"
            class="campaign-tag"
          >{{ c }}</span>
        </div>
      </div>

      <!-- last donation -->
      <div class="modal-section">
        <div class="section-label">Last Donation</div>
        <div class="last-date">{{ formatDate(viewDonor.last_donated) }}</div>
      </div>

      <template #footer>
        <button class="btn-cancel" @click="viewDonor = null">Close</button>
      </template>
    </FoundationModal>

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
import FoundationModal from "../components/foundation-admin/basemodal.vue"

const donors     = ref([])
const isLoading  = ref(false)
const search     = ref("")
const filterType = ref("")
const viewDonor  = ref(null)

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

/* ── FETCH ── */
async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/donors")
    donors.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error("Failed to load donors:", err)
    donors.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadFoundationStatus()
  if (isVerified.value) load()
})

/* ── FILTER ── */
const filtered = computed(() =>
  donors.value.filter(d => {
    const q = search.value.toLowerCase()
    const matchSearch =
      (d.name  || "").toLowerCase().includes(q) ||
      (d.email || "").toLowerCase().includes(q)

    const matchType =
      filterType.value === ""         ? true :
      filterType.value === "monetary" ? d.total_donated > 0 :
      filterType.value === "item"     ? d.total_items > 0   : true

    return matchSearch && matchType
  })
)

/* ── STATS ── */
const stats = computed(() => [
  {
    label: "Total Donors",
    value: donors.value.length,
    bg: "#eff6ff", col: "#3b82f6",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
  },
  {
    label: "Total Raised",
    value: "₱" + formatMoney(donors.value.reduce((s, d) => s + Number(d.total_donated || 0), 0)),
    bg: "#f0fdf4", col: "#059669",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
  },
  {
    label: "Item Donors",
    value: donors.value.filter(d => d.total_items > 0).length,
    bg: "#fefce8", col: "#ca8a04",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
  },
  {
    label: "Monetary Donors",
    value: donors.value.filter(d => d.total_donated > 0).length,
    bg: "#f5f3ff", col: "#7c3aed",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>`
  },
])

/* ── HELPERS ── */
function openView(d) { viewDonor.value = { ...d } }

function formatMoney(v) { return Number(v || 0).toLocaleString() }

function formatDate(d) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("en-PH", {
    year: "numeric", month: "short", day: "numeric"
  })
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.search-box { display: flex; align-items: center; gap: 8px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 14px; color: #94a3b8; }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.85rem; width: 200px; color: #0F2D52; }
.filter-select { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; background: white; color: #0F2D52; outline: none; cursor: pointer; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }

/* TABLE */
.table-card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }

table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 1px solid #f1f5f9; }
thead th { padding: 14px 16px; text-align: left; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }
tbody td { padding: 12px 16px; font-size: 0.84rem; color: #475569; }

.donor-cell   { display: flex; align-items: center; gap: 10px; }
.donor-cell strong { display: block; color: #0F2D52; font-size: 0.86rem; }
.donor-cell small  { color: #94a3b8; font-size: 0.74rem; }

.campaigns-cell { display: flex; flex-wrap: wrap; gap: 4px; max-width: 220px; }
.campaign-tag  { font-size: 0.7rem; background: #eff6ff; color: #3b82f6; padding: 2px 8px; border-radius: 20px; font-weight: 500; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
.campaign-more { font-size: 0.7rem; background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 20px; font-weight: 500; }

.count-badge { font-size: 0.8rem; font-weight: 700; background: #f1f5f9; color: #0F2D52; padding: 3px 10px; border-radius: 20px; }

.amount { color: #059669; font-size: 0.9rem; }
.muted  { color: #cbd5e1; }

.item-count { font-size: 0.8rem; font-weight: 600; background: #eff6ff; color: #3b82f6; padding: 3px 10px; border-radius: 20px; }

.date-cell { font-size: 0.8rem; color: #64748b; white-space: nowrap; }

.actions    { display: flex; gap: 6px; }
.action-btn { width: 29px; height: 29px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn.view { background: #eff6ff; color: #3b82f6; }
.action-btn:hover { opacity: 0.75; transform: scale(1.1); }

/* MODAL */
.modal-donor-hero { display: flex; align-items: center; gap: 16px; background: #f8fafc; border-radius: 14px; padding: 16px; margin-bottom: 20px; }
.donor-hero-name  { font-size: 1rem; font-weight: 700; color: #0F2D52; }
.donor-hero-email { font-size: 0.8rem; color: #94a3b8; margin-top: 2px; }
.donor-hero-phone { font-size: 0.8rem; color: #64748b; margin-top: 4px; }

.donor-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; margin-bottom: 20px; }
.ds-item { background: #f8fafc; border-radius: 10px; padding: 12px; text-align: center; }
.ds-val  { display: block; font-size: 1rem; font-weight: 800; color: #0F2D52; }
.ds-label { font-size: 0.7rem; color: #94a3b8; margin-top: 2px; }

.modal-section { margin-bottom: 16px; }
.section-label { font-size: 0.72rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }

.campaigns-list { display: flex; flex-wrap: wrap; gap: 6px; }
.last-date { font-size: 0.88rem; color: #0F2D52; font-weight: 500; }

.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
</style>