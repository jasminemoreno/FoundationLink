<template>
  <div class="page">

    <!-- HEADER -->
    <PageHeader title="Donors" :subtitle="tab === 'donors' ? 'People who have donated to your campaigns' : 'Donors who follow your foundation'">
      <template v-if="isVerified">
        <div class="search-box">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input v-model="search" :placeholder="tab === 'donors' ? 'Search donors...' : 'Search followers...'" />
        </div>
        <select v-if="tab === 'donors'" v-model="filterType" class="filter-select">
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

    <!-- TABS: people who donated / people who follow -->
    <div class="tabs" role="tablist">
      <button role="tab" :aria-selected="tab === 'donors'" :class="['tab', { active: tab === 'donors' }]" @click="tab = 'donors'">
        Donors <span class="tab-count">{{ donors.length }}</span>
      </button>
      <button role="tab" :aria-selected="tab === 'followers'" :class="['tab', { active: tab === 'followers' }]" @click="tab = 'followers'">
        Followers <span class="tab-count">{{ followersData.summary.total }}</span>
      </button>
    </div>

    <!-- ══════════ FOLLOWERS ══════════ -->
    <template v-if="tab === 'followers'">
      <div class="stats-row">
        <StatCard
          v-for="s in followerStats" :key="s.label"
          :icon="s.icon" :value="s.value" :label="s.label"
          :bg="s.bg" :col="s.col"
        />
      </div>

      <EmptyState v-if="loadingFollowers" loading message="Loading followers..." />
      <EmptyState v-else-if="filteredFollowers.length === 0" icon="🔔"
        :message="followersData.summary.total === 0 ? 'No followers yet. Donors who follow you get your new campaigns, updates and promotions.' : 'No followers match your search.'" />

      <div v-else class="table-card">
        <p class="privacy-note">Only names and photos are shown — followers' contact details stay private.</p>
        <table class="followers-table">
          <thead>
            <tr>
              <th>Follower</th>
              <th>Following since</th>
              <th>Donated to you?</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in filteredFollowers" :key="f.id">
              <td>
                <div class="donor-cell">
                  <AvatarInitials :initials="f.initials" :seed="f.id" :size="36" :src="f.profile_photo || ''" />
                  <strong>{{ f.name }}</strong>
                </div>
              </td>
              <td class="date-cell">{{ formatDate(f.followed_at) }}</td>
              <td>
                <span v-if="f.has_donated" class="donated-pill yes">
                  ✓ Yes · {{ f.donations_count }} donation{{ f.donations_count > 1 ? 's' : '' }}
                </span>
                <span v-else class="donated-pill no">Not yet</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ══════════ DONORS ══════════ -->
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

    </template><!-- /donors tab -->

    </template>

    <!-- DONOR DETAILS -->
    <FoundationModal
      v-if="viewDonor"
      title="Donor Details"
      width="520px"
      @close="viewDonor = null"
    >
      <!-- who they are -->
      <div class="dv-hero">
        <AvatarInitials :initials="viewDonor.initials" :seed="viewDonor.id" :size="60" :src="viewDonor.profile_photo || ''" />
        <div class="dv-hero-info">
          <div class="dv-name">{{ viewDonor.name }}</div>
          <div class="dv-badges">
            <span v-if="viewDonor.is_follower" class="dv-badge follow">★ Follows your foundation</span>
            <span v-if="viewDonor.total_donations > 1" class="dv-badge repeat">↻ Repeat donor</span>
            <span v-if="viewDonor.first_donated" class="dv-since">Donor since {{ monthYear(viewDonor.first_donated) }}</span>
          </div>
          <div class="dv-contact">
            <a :href="`mailto:${viewDonor.email}`" class="dv-contact-link">✉ {{ viewDonor.email }}</a>
            <a v-if="viewDonor.phone" :href="`tel:${viewDonor.phone}`" class="dv-contact-link">📞 {{ viewDonor.phone }}</a>
          </div>
        </div>
      </div>

      <!-- what they gave (received only, like Reports) -->
      <div class="dv-stats">
        <div class="dv-stat">
          <span class="dv-stat-val">{{ viewDonor.total_donations }}</span>
          <span class="dv-stat-label">Donations</span>
        </div>
        <div class="dv-stat">
          <span class="dv-stat-val money">₱{{ formatMoney(viewDonor.total_donated) }}</span>
          <span class="dv-stat-label">Money received</span>
        </div>
        <div class="dv-stat">
          <span class="dv-stat-val">{{ viewDonor.total_items }}</span>
          <span class="dv-stat-label">Item donations received</span>
        </div>
        <div class="dv-stat">
          <span class="dv-stat-val">{{ viewDonor.campaigns.length }}</span>
          <span class="dv-stat-label">{{ viewDonor.campaigns.length === 1 ? 'Campaign' : 'Campaigns' }} supported</span>
        </div>
      </div>
      <p v-if="viewDonor.pending_count" class="dv-pending">
        ⏳ {{ viewDonor.pending_count }} {{ viewDonor.pending_count === 1 ? 'donation is' : 'donations are' }} still waiting for you to mark as received.
      </p>

      <!-- latest donations -->
      <section class="dv-section">
        <h4 class="dv-title">Recent donations</h4>
        <ul class="dv-recent">
          <li v-for="r in viewDonor.recent || []" :key="r.id">
            <button type="button" class="dv-recent-row" :title="'Open this donation'" @click="openDonation(r.id)">
              <span class="dv-recent-icon" :class="r.type" aria-hidden="true">{{ r.type === 'monetary' ? '💰' : '📦' }}</span>
              <span class="dv-recent-main">
                <span class="dv-recent-what">{{ r.type === 'monetary' ? '₱' + formatMoney(r.amount) : `${r.item_name} × ${r.item_quantity}` }}</span>
                <span class="dv-recent-campaign">{{ r.campaign }} · {{ formatDate(r.donated_at) }}</span>
              </span>
              <span class="dv-status" :class="r.status">{{ { pending: 'Pending', received: 'Received', cancelled: 'Cancelled' }[r.status] || r.status }}</span>
              <span class="dv-chevron" aria-hidden="true">›</span>
            </button>
          </li>
        </ul>
        <p v-if="viewDonor.total_donations > (viewDonor.recent || []).length" class="dv-more">
          Showing the latest {{ (viewDonor.recent || []).length }} of {{ viewDonor.total_donations }}. See all on the Donations page.
        </p>
      </section>

      <!-- campaigns -->
      <section class="dv-section">
        <h4 class="dv-title">Campaigns supported</h4>
        <div class="dv-campaigns">
          <span v-for="(c, i) in viewDonor.campaigns" :key="i" class="dv-campaign">{{ c }}</span>
        </div>
      </section>

      <template #footer>
        <button class="btn-cancel" @click="viewDonor = null">Close</button>
        <a :href="`mailto:${viewDonor.email}`" class="btn-email">✉ Email donor</a>
      </template>
    </FoundationModal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useRouter } from "vue-router"
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
const router     = useRouter()

// a recent donation opens its full details on the Donations page
function openDonation(id) {
  viewDonor.value = null
  router.push(`/foundation/donation?view=${id}`)
}

function monthYear(d) {
  return new Date(d).toLocaleDateString('en-PH', { month: 'long', year: 'numeric' })
}

const isVerified     = ref(false)
const checkingStatus = ref(true)

/* ── FOLLOWERS (who follows this foundation; names and photos only) ── */
const tab = ref("donors")
const loadingFollowers = ref(false)
const followersData = ref({
  summary: { total: 0, donated: 0, new_this_month: 0, became_donors_percent: 0 },
  by_month: [],
  followers: [],
})
async function loadFollowers() {
  loadingFollowers.value = true
  try {
    const res = await api.get("/foundation/followers")
    if (res.data?.summary) followersData.value = res.data
  } catch (err) {
    console.error("Failed to load followers:", err)
  } finally {
    loadingFollowers.value = false
  }
}
const filteredFollowers = computed(() => {
  const q = search.value.toLowerCase()
  return followersData.value.followers.filter(f => (f.name || "").toLowerCase().includes(q))
})
const followerStats = computed(() => {
  const s = followersData.value.summary
  return [
    {
      label: "Followers",
      value: s.total,
      bg: "#e8f0fe", col: "#2563eb",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`
    },
    {
      label: "Became donors",
      value: `${s.donated} (${s.became_donors_percent}%)`,
      bg: "#ecfdf5", col: "#047857",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
    },
    {
      label: "New this month",
      value: s.new_this_month,
      bg: "#fef3c7", col: "#b45309",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>`
    },
  ]
})

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
  if (isVerified.value) {
    load()
    loadFollowers()
  }
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
    bg: "#e8f0fe", col: "#2563eb",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
  },
  {
    label: "Total Raised",
    value: "₱" + formatMoney(donors.value.reduce((s, d) => s + Number(d.total_donated || 0), 0)),
    bg: "#ecfdf5", col: "#047857",
    icon: `<svg stroke-linecap="round" stroke-linejoin="round" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>`
  },
  {
    label: "Item Donors",
    value: donors.value.filter(d => d.total_items > 0).length,
    bg: "#fef3c7", col: "#b45309",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
  },
  {
    label: "Monetary Donors",
    value: donors.value.filter(d => d.total_donated > 0).length,
    bg: "#ede9fe", col: "#6d28d9",
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

.search-box { display: flex; align-items: center; gap: 8px; background: white; border: 1px solid var(--fa-border); border-radius: 10px; padding: 9px 14px; color: var(--fa-muted); }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.85rem; width: 200px; color: var(--fa-heading); }
.filter-select { padding: 9px 14px; border: 1px solid var(--fa-border); border-radius: 10px; font-size: 0.85rem; background: white; color: var(--fa-heading); outline: none; cursor: pointer; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px; } /* wraps: 2 per row on phones */

/* TABLE */
.table-card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(30,58,138,0.06); overflow-x: auto; -webkit-overflow-scrolling: touch; } /* wide table scrolls inside its box on phones, never cut off */
.table-card > table { min-width: 760px; }

table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 1px solid #f1f5f9; }
thead th { padding: 14px 16px; text-align: left; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fa-muted); }
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }
tbody td { padding: 12px 16px; font-size: 0.84rem; color: var(--fa-text-2); }

.donor-cell   { display: flex; align-items: center; gap: 10px; }
.donor-cell strong { display: block; color: var(--fa-heading); font-size: 0.86rem; }
.donor-cell small  { color: var(--fa-muted); font-size: 0.74rem; }

.campaigns-cell { display: flex; flex-wrap: wrap; gap: 4px; max-width: 220px; }
.campaign-tag  { font-size: 0.7rem; background: var(--fa-primary-soft); color: var(--fa-primary); padding: 2px 8px; border-radius: 20px; font-weight: 500; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
.campaign-more { font-size: 0.7rem; background: #f1f5f9; color: var(--fa-muted); padding: 2px 8px; border-radius: 20px; font-weight: 500; }

.count-badge { font-size: 0.8rem; font-weight: 700; background: #f1f5f9; color: var(--fa-heading); padding: 3px 10px; border-radius: 20px; }

.amount { color: var(--fa-success); font-size: 0.9rem; }
.muted  { color: var(--fa-muted); }

.item-count { font-size: 0.8rem; font-weight: 600; background: var(--fa-primary-soft); color: var(--fa-primary); padding: 3px 10px; border-radius: 20px; }

.date-cell { font-size: 0.8rem; color: var(--fa-muted); white-space: nowrap; }

.actions    { display: flex; gap: 6px; }
.action-btn { width: 29px; height: 29px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn.view { background: var(--fa-primary-soft); color: var(--fa-primary); }
.action-btn:hover { opacity: 0.75; transform: scale(1.1); }

/* MODAL */
/* Donor Details */
.dv-hero { display: flex; align-items: center; gap: 16px; padding: 16px; border-radius: 14px; background: var(--fa-primary-tint); border: 1px solid var(--fa-primary-border); margin-bottom: 14px; }
.dv-hero-info { flex: 1; min-width: 0; }
.dv-name { font-size: 1.1rem; font-weight: 800; color: var(--fa-heading); }
.dv-badges { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 6px; }
.dv-badge { padding: 2px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
.dv-badge.follow { background: #fff7ed; color: #b45309; border: 1px solid #fed7aa; }
.dv-badge.repeat { background: var(--fa-success-bg); color: var(--fa-success); border: 1px solid var(--fa-success-border); }
.dv-since { font-size: 0.75rem; color: var(--fa-text-2); }
.dv-contact { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 8px; }
.dv-contact-link { font-size: 0.8rem; font-weight: 600; color: var(--fa-primary); text-decoration: none; word-break: break-all; }
.dv-contact-link:hover { text-decoration: underline; }

.dv-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.dv-stat { background: #fff; border: 1px solid var(--fa-border); border-radius: 12px; padding: 10px 8px; text-align: center; }
.dv-stat-val { display: block; font-size: 1.05rem; font-weight: 800; color: var(--fa-heading); }
.dv-stat-val.money { color: var(--fa-success); }
.dv-stat-label { display: block; margin-top: 2px; font-size: 0.68rem; color: var(--fa-muted); line-height: 1.3; }
.dv-pending { margin: 10px 0 0; padding: 8px 12px; border-radius: 10px; background: var(--fa-warning-bg); border: 1px solid var(--fa-warning-border); color: var(--fa-warning); font-size: 0.8rem; font-weight: 600; }

.dv-section { margin-top: 18px; }
.dv-title { margin: 0 0 8px; font-size: 0.72rem; font-weight: 800; color: var(--fa-muted); text-transform: uppercase; letter-spacing: 0.06em; }
.dv-recent { list-style: none; margin: 0; padding: 0; border: 1px solid var(--fa-border); border-radius: 12px; overflow: hidden; }
.dv-recent li + li { border-top: 1px solid #f1f5f9; }
.dv-recent-row { width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: none; background: #fff; text-align: left; font-family: inherit; cursor: pointer; }
.dv-recent-row:hover { background: var(--fa-primary-tint); }
.dv-recent-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; background: var(--fa-success-bg); flex-shrink: 0; }
.dv-recent-icon.item { background: var(--fa-primary-soft); }
.dv-recent-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.dv-recent-what { font-size: 0.86rem; font-weight: 700; color: var(--fa-heading); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dv-recent-campaign { font-size: 0.74rem; color: var(--fa-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dv-status { padding: 2px 9px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; }
.dv-status.pending   { background: var(--fa-warning-bg); color: var(--fa-warning); }
.dv-status.received  { background: var(--fa-success-bg); color: var(--fa-success); }
.dv-status.cancelled { background: var(--fa-danger-bg); color: var(--fa-danger); }
.dv-chevron { color: #cbd5e1; font-size: 1.1rem; font-weight: 700; }
.dv-more { margin: 6px 0 0; font-size: 0.74rem; color: var(--fa-muted); }

.dv-campaigns { display: flex; flex-wrap: wrap; gap: 6px; }
.dv-campaign { padding: 4px 10px; border-radius: 20px; background: var(--fa-primary-soft); color: var(--fa-primary); font-size: 0.76rem; font-weight: 600; }

.btn-email { padding: 9px 18px; border-radius: 10px; background: var(--fa-primary); color: #fff; font-size: 0.87rem; font-weight: 600; text-decoration: none; }
.btn-email:hover { background: var(--fa-primary-hover); }

@media (max-width: 520px) {
  .dv-stats { grid-template-columns: repeat(2, 1fr); }
  .dv-hero { flex-direction: column; align-items: flex-start; }
}

.btn-cancel { padding: 9px 18px; border: 1px solid var(--fa-border); background: white; color: var(--fa-muted); border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }

/* Donors / Followers tabs */
.tabs { display: inline-flex; gap: 4px; padding: 4px; background: var(--fa-primary-tint); border: 1px solid var(--fa-primary-border); border-radius: 12px; margin-bottom: 18px; }
.tab { border: none; background: transparent; padding: 8px 16px; border-radius: 9px; font-size: 0.86rem; font-weight: 600; color: var(--fa-muted); cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; }
.tab:hover { color: var(--fa-heading); }
.tab.active { background: white; color: var(--fa-primary); box-shadow: 0 1px 3px rgba(30,58,138,0.12); }
.tab-count { font-size: 0.72rem; font-weight: 700; padding: 1px 8px; border-radius: 20px; background: var(--fa-primary-soft); color: var(--fa-primary); }

/* Followers */
.table-card > table.followers-table { min-width: 520px; }
.privacy-note { margin: 0; padding: 12px 18px 0; font-size: 0.78rem; color: var(--fa-muted); }
.donated-pill { display: inline-block; font-size: 0.76rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.donated-pill.yes { background: #ecfdf5; color: #047857; }
.donated-pill.no  { background: #f1f5f9; color: var(--fa-muted); }
</style>