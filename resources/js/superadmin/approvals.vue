<template>
  <div class="page">

    <!-- HEADER -->
    <div class="page-header">
      <div class="header-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="tab-btn"
          :class="{ active: activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
          <span class="tab-count" :class="tab.key">{{ tab.count }}</span>
        </button>
      </div>

      
    </div>

    <!-- LIST -->
    <div class="approvals-grid">
      <FoundationCard
        v-for="f in filtered"
        :key="f.id"
        :foundation="f"
        @view="openView"
      />
    </div>

    <div class="empty" v-if="filtered.length === 0">
      No foundations found.
    </div>

    <!-- MODAL -->
    <FoundationViewModal
      v-model="showModal"
      :foundation="selectedFoundation"
      @approve="approve"
      @reject="reject"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"

import FoundationCard from "@/components/superadmin/FoundationCard.vue"
import FoundationViewModal from "@/components/superadmin/FoundationViewModal.vue"
import { useSearch } from "../composable/usesearch"

const activeTab = ref("pending")
const { searchQuery } = useSearch()
const foundations = ref([])
const showModal = ref(false)
const selectedFoundation = ref(null)

const tabs = ref([
  { key: "pending", label: "Pending review", count: 0 },
  { key: "approved", label: "Approved", count: 0 },
  { key: "rejected", label: "Rejected", count: 0 },
])

function normalizeStatus(status) {
  if (status === "pending_verification" || status === "under_review") return "pending"
  if (status === "verified") return "approved"
  if (status === "rejected") return "rejected"
  return "pending"
}

async function fetchFoundations() {
  const res = await api.get("/admin/foundations")

  foundations.value = res.data.map(f => ({
    id:          f.id,
    name:        f.name,
    description: f.description,
    address:     f.full_address || '—',

    street:            f.street,
    barangay:          f.barangay,
    city_municipality: f.city_municipality,
    province:          f.province,
    category:          f.category,
    logo:              f.logo,

    submitted: new Date(f.created_at).toLocaleDateString(),
    initials:  f.name?.split(" ").map(n => n[0]).join("").toUpperCase() || "F",
    status:    normalizeStatus(f.status),
    user:      f.user,

    identity_documents:   f.identity_documents   || [],
    legitimacy_documents: f.legitimacy_documents || []
  }))

  updateTabs()
}
function updateTabs() {
  tabs.value[0].count = foundations.value.filter(f => f.status === "pending").length
  tabs.value[1].count = foundations.value.filter(f => f.status === "approved").length
  tabs.value[2].count = foundations.value.filter(f => f.status === "rejected").length
}

function openView(f) {
  selectedFoundation.value = f
  showModal.value = true
}

/* ✅ APPROVE */
async function approve(f) {
  await api.post(`/admin/foundations/${f.id}/approve`)
  f.status = "approved"
  updateTabs()
}

/* ❌ REJECT */
async function reject(f) {
  const reason = prompt("Reason for rejection")
  if (!reason) return

  await api.post(`/admin/foundations/${f.id}/reject`, { reason })
  f.status = "rejected"
  updateTabs()
}

/* FILTER */
const filtered = computed(() =>
  foundations.value.filter(f =>
    f.status === activeTab.value &&
    f.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
)

onMounted(fetchFoundations)
</script>
<style scoped>
.page {
  padding: 28px;
  background: #f8fafc;
  min-height: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 12px;
}

/* TABS */
.header-tabs {
  display: flex;
  gap: 2px;
  background: #fff;
  padding: 4px;
  border-radius: 12px;
  border: 0.5px solid #e2e8f0;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 13px;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
}
.tab-btn:hover { background: #f1f5f9; color: #1e293b; }
.tab-btn.active { background: #0F2D52; color: #fff; }

.tab-count {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
}
.tab-count.pending  { background: #fefce8; color: #92400e; }
.tab-count.approved { background: #f0fdf4; color: #065f46; }
.tab-count.rejected { background: #fef2f2; color: #991b1b; }

/* SEARCH */
.search-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 0.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 9px 14px;
  min-width: 220px;
  color: #94a3b8;
}
.search-wrap input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 13px;
  color: #1e293b;
  width: 100%;
}
.search-wrap input::placeholder { color: #94a3b8; }

/* GRID */
.approvals-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.empty {
  text-align: center;
  padding: 60px;
  color: #94a3b8;
  font-size: 14px;
}
</style>