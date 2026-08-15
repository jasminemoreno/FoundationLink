<template>
  <div class="page">
    <div class="container">

      <div class="page-head">
        <h1>Browse Campaigns</h1>
        <p>Support causes that matter to you</p>
      </div>

      <!-- STATUS SWITCH -->
      <div class="switch">
        <button :class="{ active: tab === 'new' }" @click="changeTab('new')">New</button>
        <button :class="{ active: tab === 'completed' }" @click="changeTab('completed')">Completed</button>
        <button :class="{ active: tab === 'paused' }" @click="changeTab('paused')">Paused</button>
        <button :class="{ active: tab === 'followed' }" @click="changeTab('followed')">Followed</button>
      </div>

      <!-- SEARCH + TYPE + CATEGORY FILTER -->
      <div class="filters">
        <div class="search-box">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input v-model="search" placeholder="Search campaigns..." />
        </div>

        <select v-model="filterType" class="filter-select">
          <option value="">All Types</option>
          <option value="monetary">💰 Monetary</option>
          <option value="item">📦 Item</option>
          <option value="both">💰📦 Both</option>
        </select>

        <select v-model="filterCategory" class="filter-select">
          <option value="">All Categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <!-- LOADING -->
      <div v-if="isLoading" class="loading-wrap"><Spinner /><p>Loading campaigns...</p></div>

      <!-- EMPTY -->
      <EmptyState v-else-if="campaigns.length === 0" icon="📋" :message="emptyMessage" />

      <!-- GRID -->
      <div v-else class="campaigns-grid">
        <CampaignCard
          v-for="c in campaigns" :key="c.id"
          :campaign="c"
          :highlighted="!!highlightId && c.id === highlightId"
          @view-details="openDetails" @donate="openDonate"
        />
      </div>

    </div>

    <!-- VIEW DETAILS MODAL -->
    <CampaignDetailsModal
      v-if="detailsTarget"
      :campaign="detailsTarget"
      @close="detailsTarget = null"
      @donate="onDonateFromDetails"
    />

    <DonateModal
      v-if="donateTarget"
      :campaign="donateTarget"
      @close="donateTarget = null"
      @donated="onDonated"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import DonateModal from '@/components/donor/DonateModal.vue'
import CampaignDetailsModal from '../components/donor/ViewDetail.vue'
import Spinner from '../components/donor/spinner.vue'
import EmptyState from '../components/donor/emptystate.vue'
import CampaignCard from '../components/donor/campaigncard.vue'

const route  = useRoute()
const router = useRouter()

const campaigns      = ref([])
const categories      = ref([])
const isLoading       = ref(false)
const search          = ref('')
const filterType       = ref('')
const filterCategory   = ref('')
const tab             = ref(route.query.tab || 'new')
const highlightId      = ref(route.query.highlight ? Number(route.query.highlight) : null)
const donateTarget     = ref(null)
const detailsTarget    = ref(null)

const emptyMessage = computed(() => {
  if (tab.value === 'completed') return 'No completed campaigns yet.'
  if (tab.value === 'paused') return 'No paused campaigns.'
  if (tab.value === 'followed') return 'No campaigns yet from foundations you follow.'
  return 'No campaigns found.'
})

async function load() {
  isLoading.value = true
  try {
    const res = await api.get('/donor/campaigns', {
      params: { search: search.value, type: filterType.value, category: filterCategory.value, tab: tab.value }
    })
    campaigns.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error(err)
    campaigns.value = []
  } finally {
    isLoading.value = false
    if (highlightId.value) {
      await nextTick()
      scrollToHighlight()
    }
  }
}

async function loadCategories() {
  try {
    const res = await api.get('/donor/categories')
    categories.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error(err)
    categories.value = []
  }
}

function scrollToHighlight() {
  const el = document.querySelector(`[data-campaign-id="${highlightId.value}"]`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
  // clear highlight + query param after a moment so refreshing doesn't re-highlight forever
  setTimeout(() => {
    highlightId.value = null
    router.replace({ query: { ...route.query, highlight: undefined } })
  }, 2500)
}

onMounted(() => {
  load()
  loadCategories()
})
watch([search, filterType, filterCategory, tab], () => load())

function changeTab(t) {
  if (tab.value === t) return
  tab.value = t
  router.replace({ query: { ...route.query, tab: t } })
}

function openDonate(c)  { donateTarget.value = c }
function openDetails(c) { detailsTarget.value = c }
function onDonated()    { donateTarget.value = null; load() }

// Called when the user clicks "Donate Now" from inside the details modal
function onDonateFromDetails(c) {
  detailsTarget.value = null
  donateTarget.value  = c
}
</script>

<style scoped>
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 40px; }
.container { max-width: 1200px; margin: 0 auto; padding: 32px 24px; }

.page-head { margin-bottom: 20px; }
.page-head h1 { font-size: 1.5rem; font-weight: 900; color: #0e5c36; margin: 0 0 4px; }
.page-head p  { font-size: 0.85rem; color: #475569; margin: 0; }

/* STATUS SWITCH */
.switch {
  display: flex; gap: 6px; background: white; padding: 6px; border-radius: 14px;
  margin-bottom: 18px; max-width: 100%; overflow-x: auto; box-shadow: 0 2px 8px rgba(0,80,40,0.06);
}
.switch button {
  flex: 1; white-space: nowrap; padding: 10px 14px; border: none; background: transparent;
  border-radius: 10px; font-size: 0.83rem; font-weight: 700; color: #4d7a64; cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.switch button.active { background: #1a8a52; color: white; box-shadow: 0 2px 8px rgba(26,138,82,0.3); }
.switch button:hover:not(.active) { background: #f0fdf4; }

.filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 8px; background: white; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 16px; flex: 1; min-width: 200px; }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.87rem; width: 100%; color: #0F2D52; }
.filter-select { padding: 10px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 0.87rem; background: white; color: #0F2D52; outline: none; cursor: pointer; }

.loading-wrap { text-align: center; padding: 60px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 12px; background: white; border-radius: 16px; }

.campaigns-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }

@media (max-width: 560px) {
  .switch { gap: 4px; }
  .switch button { font-size: 0.75rem; padding: 9px 6px; }
}
</style>