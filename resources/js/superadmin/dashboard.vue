<template>
  <div class="dashboard">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div class="welcome-text">
        <h1>Good morning, Super Admin 👋</h1>
        <p>Here's what's happening across FoundationLink today.</p>
      </div>
      <div class="banner-date">
        <div class="date-badge">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          {{ currentDate }}
        </div>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <StatCard
        v-for="stat in stats"
        :key="stat.label"
        layout="vertical"
        :icon="stat.icon"
        :value="stat.value"
        :label="stat.label"
        :sub="stat.sub"
        :progress="stat.progress"
        :bg="colorMap[stat.color]?.bg"
        :col="colorMap[stat.color]?.col"
        :border-color="colorMap[stat.color]?.col"
      />
    </div>

    <!-- Middle Row -->
    <div class="mid-row">

      <!-- Recent Approvals -->
      <div class="card approvals-card">
        <div class="card-header">
          <div>
            <h3>Pending Approvals</h3>
            <p>Foundations awaiting review</p>
          </div>
          <router-link to="/admin/approval" class="view-all">View All →</router-link>
        </div>
        <div class="approvals-list">
          <div v-if="pendingApprovals.length === 0" class="list-empty">No pending approvals.</div>
          <div class="approval-item" v-for="item in pendingApprovals" :key="item.name">
            <div class="approval-avatar" :style="{ background: item.color }">{{ item.initials }}</div>
            <div class="approval-info">
              <strong>{{ item.name }}</strong>
              <small>{{ item.type }} · Submitted {{ item.date }}</small>
            </div>
            <div class="approval-actions">
              <button class="btn-approve">✓</button>
              <button class="btn-reject">✕</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Campaigns (monetary/both) -->
      <div class="card campaigns-card">
        <div class="card-header">
          <div>
            <h3>Top Fundraising Campaigns</h3>
            <p>Highest funded active campaigns</p>
          </div>
          <router-link to="/admin/campaign" class="view-all">View All →</router-link>
        </div>
        <div class="campaigns-list">
          <div v-if="topCampaigns.length === 0" class="list-empty">No active campaigns yet.</div>
          <div class="campaign-item" v-for="(c, i) in topCampaigns" :key="c.name">
            <span class="rank">#{{ i + 1 }}</span>
            <div class="campaign-info">
              <strong>{{ c.name }}</strong>
              <div class="campaign-bar-wrap">
                <div class="campaign-bar">
                  <div class="campaign-bar-fill" :style="{ width: c.pct + '%', background: c.col }"></div>
                </div>
                <span class="campaign-pct">{{ c.pct }}%</span>
              </div>
            </div>
            <span class="campaign-amount">{{ c.amount }}</span>
          </div>
        </div>
      </div>

      <!-- Top Item Drives (item/both) -->
      <div class="card campaigns-card">
        <div class="card-header">
          <div>
            <h3>Top Item Drives</h3>
            <p>Most donated item campaigns</p>
          </div>
          <router-link to="/admin/campaign" class="view-all">View All →</router-link>
        </div>
        <div class="campaigns-list">
          <div v-if="topItemDrives.length === 0" class="list-empty">No active item drives yet.</div>
          <div class="campaign-item" v-for="(d, i) in topItemDrives" :key="d.name">
            <span class="rank">#{{ i + 1 }}</span>
            <div class="campaign-info">
              <strong>{{ d.name }}</strong>
              <small class="drive-donors">{{ d.donors }} donors</small>
            </div>
            <span class="campaign-amount">{{ d.count }} {{ d.unit }}</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom Row -->
    <div class="bottom-row">

      <!-- Recent Activity -->
      <div class="card activity-card">
        <div class="card-header">
          <div>
            <h3>Recent Activity</h3>
            <p>Latest system events</p>
          </div>
        </div>
        <div class="activity-list">
          <div v-if="activities.length === 0" class="list-empty">No recent activity.</div>
          <div class="activity-item" v-for="(a, i) in activities" :key="i">
            <div class="activity-dot" :class="a.type"></div>
            <div class="activity-content">
              <span>{{ a.msg }}</span>
              <small>{{ a.time }}</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Breakdown -->
      <div class="card category-card">
        <div class="card-header">
          <div>
            <h3>Campaigns by Category</h3>
            <p>Distribution overview</p>
          </div>
        </div>
        <div class="category-list">
          <div v-if="categories.length === 0" class="list-empty">No categories yet.</div>
          <div class="category-item" v-for="cat in categories" :key="cat.name">
            <div class="cat-top">
              <div class="cat-dot" :style="{ background: cat.color }"></div>
              <span class="cat-name">{{ cat.name }}</span>
              <span class="cat-count">{{ cat.count }}</span>
            </div>
            <div class="cat-bar">
              <div class="cat-bar-fill" :style="{ width: cat.pct + '%', background: cat.color }"></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import StatCard from '../components/superadmin/statcard.vue'


const currentDate = new Date().toLocaleDateString('en-US', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric'
})

// The API returns a color *name* per stat (blue/gold/teal/green) — this maps
// each name to the actual icon-background / accent color StatCard needs.
const colorMap = {
  blue:  { bg: '#eff6ff', col: '#3b82f6' },
  gold:  { bg: '#fefce8', col: '#D4AF37' },
  teal:  { bg: '#ecfeff', col: '#0891b2' },
  green: { bg: '#f0fdf4', col: '#059669' },
}

// The API sends stats without icon markup (kept out of PHP for maintainability).
// Icons are matched here by label since each label maps to a fixed stat.
const iconMap = {
  'Total Foundations': `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l8-4v18M13 21V11l6 3v7"/></svg>`,
  'Active Campaigns':  `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M2 12h20"/><circle cx="12" cy="12" r="9"/></svg>`,
  'Total Donors':      `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  'Total Donations':   `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`,
}

const stats             = ref([])
const pendingApprovals   = ref([])
const topCampaigns       = ref([])
const topItemDrives      = ref([])
const categories         = ref([])
const activities         = ref([])

const loadDashboard = async () => {
  try {
    const res = await api.get('/admin/dashboard')

    stats.value = (res.data.stats || []).map(s => ({
      ...s,
      icon: iconMap[s.label] || ''
    }))

    pendingApprovals.value  = res.data.pendingApprovals
    topCampaigns.value      = res.data.topCampaigns || []
    topItemDrives.value     = res.data.topItemDrives || []
    categories.value        = res.data.categories || []
    activities.value        = res.data.activities || []

  } catch (err) {
    console.error('Dashboard load failed:', err)
  }
}

onMounted(() => {
  loadDashboard()
})
</script>

<style scoped>
.dashboard {
  padding: 28px;
  background: #f8fafc;
  min-height: 100%;
}

/* Welcome Banner */
.welcome-banner {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 28px;
}

.welcome-banner h1 {
  font-size: 1.4rem;
  color: #0F2D52;
  margin: 0 0 4px;
}

.welcome-banner > .welcome-text p {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}

.date-badge {
  display: flex;
  align-items: center;
  gap: 7px;
  background: white;
  border: 1px solid #e2e8f0;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 0.82rem;
  color: #64748b;
  font-weight: 500;
}

/* Stat Cards grid (card visuals now live inside StatCard.vue) */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  margin-bottom: 22px;
}

/* Cards shared */
.card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 4px rgba(15,45,82,0.06);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 20px 20px 0;
  margin-bottom: 16px;
}

.card-header h3 { font-size: 0.95rem; color: #0F2D52; margin: 0 0 2px; }
.card-header p { font-size: 0.78rem; color: #94a3b8; margin: 0; }

.view-all { font-size: 0.78rem; color: #D4AF37; text-decoration: none; font-weight: 600; white-space: nowrap; }
.view-all:hover { text-decoration: underline; }

.list-empty { padding: 20px 0; text-align: center; color: #94a3b8; font-size: 0.83rem; }

/* Mid Row */
.mid-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 22px; }

/* Approvals */
.approvals-list { padding: 0 20px 20px; }

.approval-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}

.approval-item:last-child { border-bottom: none; }

.approval-avatar {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 10px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.78rem;
  font-weight: 700;
}

.approval-info { flex: 1; min-width: 0; }
.approval-info strong { display: block; font-size: 0.85rem; color: #0F2D52; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.approval-info small { font-size: 0.75rem; color: #94a3b8; }

.approval-actions { display: flex; gap: 6px; }
.btn-approve { width: 30px; height: 30px; border: none; background: #f0fdf4; color: #059669; border-radius: 8px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
.btn-reject { width: 30px; height: 30px; border: none; background: #fef2f2; color: #dc2626; border-radius: 8px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
.btn-approve:hover { background: #059669; color: white; }
.btn-reject:hover { background: #dc2626; color: white; }

/* Campaigns / Item Drives */
.campaigns-list { padding: 0 20px 20px; }

.campaign-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}

.campaign-item:last-child { border-bottom: none; }

.rank { font-size: 0.85rem; font-weight: 700; color: #cbd5e1; width: 24px; flex-shrink: 0; }

.campaign-info { flex: 1; min-width: 0; }
.campaign-info strong { display: block; font-size: 0.83rem; color: #0F2D52; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.campaign-bar-wrap { display: flex; align-items: center; gap: 8px; }
.campaign-bar { flex: 1; height: 5px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.campaign-bar-fill { height: 100%; border-radius: 99px; }
.campaign-pct { font-size: 0.72rem; color: #94a3b8; white-space: nowrap; }

.campaign-amount { font-size: 0.82rem; font-weight: 700; color: #0F2D52; white-space: nowrap; }

.drive-donors { font-size: 0.72rem; color: #94a3b8; }

/* Bottom Row — two cards side by side */
.bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

/* Activity */
.activity-list { padding: 0 20px 20px; }

.activity-item { display: flex; align-items: flex-start; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f8fafc; }
.activity-item:last-child { border-bottom: none; }

.activity-dot { width: 10px; height: 10px; min-width: 10px; border-radius: 50%; margin-top: 4px; }
.activity-dot.info { background: #3b82f6; }
.activity-dot.success { background: #059669; }
.activity-dot.danger { background: #dc2626; }
.activity-dot.warning { background: #D4AF37; }

.activity-content span { font-size: 0.82rem; color: #334155; display: block; line-height: 1.4; }
.activity-content small { font-size: 0.73rem; color: #94a3b8; }

/* Categories */
.category-list { padding: 0 20px 20px; }

.category-item { margin-bottom: 12px; }
.category-item:last-child { margin-bottom: 0; }

.cat-top { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
.cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.cat-name { flex: 1; font-size: 0.82rem; color: #334155; }
.cat-count { font-size: 0.8rem; font-weight: 600; color: #64748b; }

.cat-bar { height: 5px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.cat-bar-fill { height: 100%; border-radius: 99px; }
</style>