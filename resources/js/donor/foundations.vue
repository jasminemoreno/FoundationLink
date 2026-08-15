<template>
  <div class="page">
    <div class="container">

      <!-- PAGE HEAD -->
      <div class="page-head">
        <div class="page-head-left">
          <div class="page-eyebrow">Explore</div>
          <h1 class="page-title">Our Foundations</h1>
          <p class="page-sub">Discover verified organizations making a difference in your community.</p>
        </div>
        <div class="page-head-right">
          <div class="head-stat">
            <span class="head-stat-val">{{ foundations.length }}</span>
            <span class="head-stat-label">Verified Foundations</span>
          </div>
          <div class="head-stat">
            <span class="head-stat-val">{{ totalActive }}</span>
            <span class="head-stat-label">Active Campaigns</span>
          </div>
        </div>
      </div>

      <!-- SEARCH + FILTER BAR -->
      <div class="toolbar">
        <div class="search-wrap">
          <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input
            v-model="search"
            class="search-input"
            type="text"
            placeholder="Search foundations..."
          />
          <button v-if="search" class="search-clear" @click="search = ''">✕</button>
        </div>

        <div class="filter-tabs">
          <button
            v-for="f in filters" :key="f.key"
            class="filter-tab"
            :class="{ active: activeFilter === f.key }"
            @click="activeFilter = f.key"
          >{{ f.label }}</button>
        </div>

        <select v-model="activeCategory" class="category-select">
          <option value="all">All Categories</option>
          <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
        </select>

        <div class="view-toggle">
          <button class="view-btn" :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/>
              <rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/>
            </svg>
          </button>
          <button class="view-btn" :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
              <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- LOADING -->
      <div v-if="isLoading" class="loading-state">
        <Spinner :size="36" />
        <p>Finding foundations...</p>
      </div>

      <!-- EMPTY -->
      <EmptyState
        v-else-if="filtered.length === 0"
        icon="" title="No foundations found" message="Try adjusting your search or filter."
        action-label="Reset filters" @action="search = ''; activeFilter = 'all'; activeCategory = 'all'"
      >
        <template #default>
          <div class="empty-illustration">
            <svg width="64" height="64" fill="none" stroke="#1a8a52" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
        </template>
      </EmptyState>

      <!-- GRID VIEW -->
      <div v-else-if="viewMode === 'grid'" class="foundations-grid">
        <div
          class="foundation-card"
          v-for="f in filtered" :key="f.id"
          @click="$router.push('/donor/foundations/' + f.id)"
        >
          <!-- COVER -->
          <div class="fc-cover" :style="f.cover_photo ? `background-image: url(${getImage(f.cover_photo)})` : ''">
            <div class="fc-cover-overlay" />
            <div class="fc-cover-content">
              <span class="fc-verified">✓ Verified</span>
            </div>
          </div>

          <!-- LOGO -->
          <div class="fc-logo-wrap">
            <div class="fc-logo">
              <img v-if="f.logo" :src="getImage(f.logo)" :alt="f.name" />
              <span v-else>{{ f.initials }}</span>
            </div>
          </div>

          <!-- BODY -->
          <div class="fc-body">
            <h3 class="fc-name">{{ f.name }}</h3>
            <p v-if="f.category" class="fc-category">{{ f.category }}</p>
            <p v-if="f.description" class="fc-desc">{{ f.description }}</p>

            <div class="fc-location" v-if="f.city_municipality">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
              </svg>
              {{ f.city_municipality }}{{ f.province ? ', ' + f.province : '' }}
            </div>

            <!-- STATS ROW -->
            <div class="fc-stats">
              <div class="fc-stat">
                <span class="fc-stat-val green">{{ f.active_campaigns }}</span>
                <span class="fc-stat-label">Active</span>
              </div>
              <div class="fc-stat-divider" />
              <div class="fc-stat">
                <span class="fc-stat-val">{{ f.total_campaigns }}</span>
                <span class="fc-stat-label">Total</span>
              </div>
              <div class="fc-stat-divider" />
              <div class="fc-stat">
                <span class="fc-stat-val">{{ f.completed_campaigns }}</span>
                <span class="fc-stat-label">Done</span>
              </div>
            </div>

            <button class="fc-btn">
              View Foundation
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- LIST VIEW -->
      <div v-else class="foundations-list">
        <div
          class="fl-item"
          v-for="f in filtered" :key="f.id"
          @click="$router.push('/donor/foundations/' + f.id)"
        >
          <div class="fl-logo">
            <img v-if="f.logo" :src="getImage(f.logo)" :alt="f.name" />
            <span v-else>{{ f.initials }}</span>
          </div>

          <div class="fl-info">
            <div class="fl-top">
              <h3 class="fl-name">{{ f.name }}</h3>
              <span class="fl-verified">✓ Verified</span>
              <span v-if="f.category" class="fl-category">{{ f.category }}</span>
            </div>
            <p class="fl-desc">{{ f.description }}</p>
            <div class="fl-location" v-if="f.city_municipality">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
              </svg>
              {{ f.city_municipality }}{{ f.province ? ', ' + f.province : '' }}
            </div>
          </div>

          <div class="fl-stats">
            <div class="fl-stat">
              <span class="fl-stat-val green">{{ f.active_campaigns }}</span>
              <span class="fl-stat-label">Active Campaigns</span>
            </div>
            <div class="fl-stat">
              <span class="fl-stat-val">{{ f.total_campaigns }}</span>
              <span class="fl-stat-label">Total Campaigns</span>
            </div>
          </div>

          <div class="fl-arrow">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- RESULTS COUNT -->
      <div v-if="!isLoading && filtered.length > 0" class="results-count">
        Showing {{ filtered.length }} of {{ foundations.length }} foundations
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import Spinner from '../components/donor/spinner.vue'
import EmptyState from '../components/donor/emptystate.vue'
import { getImage } from '../composable/donor'

const foundations   = ref([])
const isLoading      = ref(false)
const search         = ref('')
const activeFilter   = ref('all')
const activeCategory = ref('all')
const viewMode        = ref('grid')

const filters = [
  { key: 'all',    label: 'All'         },
  { key: 'active', label: 'Has Active'  },
  { key: 'done',   label: 'Completed'   },
]

async function load() {
  isLoading.value = true
  try {
    const res = await api.get('/donor/foundations')
    foundations.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error(err)
    foundations.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

const totalActive = computed(() =>
  foundations.value.reduce((sum, f) => sum + (f.active_campaigns || 0), 0)
)

// Category dropdown options — unique categories present in the loaded foundations
const categories = computed(() => {
  const set = new Set(
    foundations.value
      .map(f => f.category)
      .filter(Boolean)
  )
  return Array.from(set).sort()
})

const filtered = computed(() => {
  let list = foundations.value

  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(f =>
      f.name?.toLowerCase().includes(q) ||
      f.description?.toLowerCase().includes(q) ||
      f.category?.toLowerCase().includes(q) ||
      f.city_municipality?.toLowerCase().includes(q)
    )
  }

  if (activeFilter.value === 'active') {
    list = list.filter(f => f.active_campaigns > 0)
  } else if (activeFilter.value === 'done') {
    list = list.filter(f => f.completed_campaigns > 0)
  }

  if (activeCategory.value !== 'all') {
    list = list.filter(f => f.category === activeCategory.value)
  }

  return list
})
</script>

<style scoped>
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 48px; }
.container { max-width: 1200px; margin: 0 auto; padding: 32px 24px; }

/* PAGE HEAD */
.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 28px;
  gap: 20px;
  flex-wrap: wrap;
}
.page-eyebrow {
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #1a8a52;
  margin-bottom: 6px;
}
.page-title {
  font-size: 1.8rem;
  font-weight: 900;
  color: #0e5c36;
  margin: 0 0 6px;
  line-height: 1.1;
}
.page-sub {
  font-size: 0.88rem;
  color: #475569;
  margin: 0;
}
.page-head-right {
  display: flex;
  gap: 24px;
  flex-shrink: 0;
}
.head-stat {
  background: white;
  border-radius: 14px;
  padding: 14px 20px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,80,40,0.08);
  min-width: 110px;
}
.head-stat-val {
  display: block;
  font-size: 1.5rem;
  font-weight: 900;
  color: #0F2D52;
  line-height: 1;
  margin-bottom: 4px;
}
.head-stat-label {
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 600;
}

/* TOOLBAR */
.toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-wrap {
  position: relative;
  flex: 1;
  min-width: 200px;
}
.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}
.search-input {
  width: 100%;
  padding: 10px 36px 10px 36px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.87rem;
  background: white;
  color: #0F2D52;
  outline: none;
  transition: border-color 0.2s;
  box-sizing: border-box;
}
.search-input:focus { border-color: #1a8a52; }
.search-input::placeholder { color: #cbd5e1; }
.search-clear {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 0.75rem;
  padding: 4px;
}

.filter-tabs {
  display: flex;
  gap: 4px;
  background: white;
  padding: 4px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,80,40,0.06);
}
.filter-tab {
  padding: 7px 16px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}
.filter-tab:hover  { background: #f0fdf4; color: #1a8a52; }
.filter-tab.active { background: #1a8a52; color: white; }

/* CATEGORY SELECT */
.category-select {
  padding: 10px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.87rem;
  background: white;
  color: #0F2D52;
  outline: none;
  cursor: pointer;
  transition: border-color 0.2s;
}
.category-select:focus { border-color: #1a8a52; }

.view-toggle {
  display: flex;
  gap: 4px;
  background: white;
  padding: 4px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,80,40,0.06);
}
.view-btn {
  width: 34px;
  height: 34px;
  border: none;
  background: transparent;
  color: #94a3b8;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}
.view-btn:hover  { background: #f0fdf4; color: #1a8a52; }
.view-btn.active { background: #1a8a52; color: white; }

/* LOADING */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 80px;
  background: white;
  border-radius: 20px;
  color: #94a3b8;
  font-size: 0.88rem;
}

/* EMPTY ILLUSTRATION (custom icon, EmptyState handles the rest) */
.empty-illustration {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #f0fdf4;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

/* ─── GRID VIEW ─── */
.foundations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.foundation-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,80,40,0.07);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  position: relative;
}
.foundation-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 36px rgba(0,80,40,0.16);
}

/* COVER */
.fc-cover {
  height: 110px;
  background: linear-gradient(135deg, #0e5c36, #1a8a52);
  background-size: cover;
  background-position: center;
  position: relative;
}
.fc-cover-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.15), rgba(0,0,0,0.4));
}
.fc-cover-content {
  position: relative;
  z-index: 1;
  padding: 12px 14px;
  display: flex;
  justify-content: flex-end;
}
.fc-verified {
  font-size: 0.68rem;
  font-weight: 800;
  background: rgba(255,255,255,0.95);
  color: #059669;
  padding: 3px 9px;
  border-radius: 20px;
}

/* LOGO */
.fc-logo-wrap {
  display: flex;
  justify-content: center;
  margin-top: -30px;
  margin-bottom: 4px;
  position: relative;
  z-index: 2;
}
.fc-logo {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  background: linear-gradient(135deg, #0e5c36, #1a8a52);
  color: white;
  font-size: 1rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3px solid white;
  box-shadow: 0 4px 14px rgba(0,80,40,0.25);
  overflow: hidden;
  flex-shrink: 0;
}
.fc-logo img { width: 100%; height: 100%; object-fit: cover; }

/* BODY */
.fc-body { padding: 8px 20px 20px; }

.fc-name {
  font-size: 1rem;
  font-weight: 900;
  color: #0F2D52;
  margin: 0 0 4px;
  text-align: center;
}
.fc-category {
  font-size: 0.7rem;
  font-weight: 700;
  color: #1a8a52;
  text-align: center;
  margin: 0 0 10px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.fc-desc {
  font-size: 0.78rem;
  color: #64748b;
  line-height: 1.55;
  margin: 0 0 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-align: center;
}
.fc-location {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 0.72rem;
  color: #94a3b8;
  margin-bottom: 14px;
}

/* FC STATS */
.fc-stats {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  background: #f8fafc;
  border-radius: 12px;
  padding: 10px 0;
  margin-bottom: 14px;
}
.fc-stat {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}
.fc-stat-divider {
  width: 1px;
  height: 28px;
  background: #e2e8f0;
}
.fc-stat-val {
  font-size: 1rem;
  font-weight: 900;
  color: #0F2D52;
}
.fc-stat-val.green { color: #059669; }
.fc-stat-label {
  font-size: 0.65rem;
  color: #94a3b8;
  font-weight: 600;
}

.fc-btn {
  width: 100%;
  padding: 10px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #0e5c36, #1a8a52);
  color: white;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: opacity 0.2s, transform 0.15s;
}
.fc-btn:hover { opacity: 0.9; transform: translateY(-1px); }

/* ─── LIST VIEW ─── */
.foundations-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.fl-item {
  background: white;
  border-radius: 16px;
  padding: 20px 22px;
  display: flex;
  align-items: center;
  gap: 18px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,80,40,0.06);
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
  border: 1.5px solid transparent;
}
.fl-item:hover {
  transform: translateX(4px);
  box-shadow: 0 6px 20px rgba(0,80,40,0.12);
  border-color: #1a8a52;
}

.fl-logo {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, #0e5c36, #1a8a52);
  color: white;
  font-size: 1rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(0,80,40,0.2);
}
.fl-logo img { width: 100%; height: 100%; object-fit: cover; }

.fl-info { flex: 1; min-width: 0; }
.fl-top  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
.fl-name { font-size: 0.95rem; font-weight: 800; color: #0F2D52; margin: 0; }
.fl-verified { font-size: 0.65rem; font-weight: 800; background: #f0fdf4; color: #059669; padding: 2px 7px; border-radius: 20px; }
.fl-category  { font-size: 0.65rem; font-weight: 700; background: #eff6ff; color: #3b82f6; padding: 2px 7px; border-radius: 20px; }
.fl-desc {
  font-size: 0.78rem;
  color: #64748b;
  margin: 0 0 6px;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.fl-location {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.7rem;
  color: #94a3b8;
}

.fl-stats {
  display: flex;
  gap: 20px;
  flex-shrink: 0;
}
.fl-stat { text-align: center; }
.fl-stat-val {
  display: block;
  font-size: 1.1rem;
  font-weight: 900;
  color: #0F2D52;
  line-height: 1;
  margin-bottom: 3px;
}
.fl-stat-val.green { color: #059669; }
.fl-stat-label { font-size: 0.65rem; color: #94a3b8; font-weight: 600; white-space: nowrap; }

.fl-arrow {
  color: #cbd5e1;
  flex-shrink: 0;
  transition: color 0.15s, transform 0.15s;
}
.fl-item:hover .fl-arrow { color: #1a8a52; transform: translateX(3px); }

/* RESULTS COUNT */
.results-count {
  margin-top: 20px;
  text-align: center;
  font-size: 0.78rem;
  color: #94a3b8;
  font-weight: 600;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .page-head { flex-direction: column; align-items: flex-start; }
  .page-head-right { width: 100%; }
  .head-stat { flex: 1; }
  .toolbar { flex-direction: column; align-items: stretch; }
  .filter-tabs { overflow-x: auto; }
  .foundations-grid { grid-template-columns: 1fr; }
  .fl-stats { display: none; }
}
</style>