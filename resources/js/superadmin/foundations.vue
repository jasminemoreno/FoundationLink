<template>
  <div class="page">

    <PageHeader title="All Foundations">
      <template #default>{{ foundations.length }} foundations registered on the platform</template>
      <template #actions>
        <select v-model="filterStatus" class="filter-select">
          <option value="">All Status</option>
          <option value="pending_verification">Pending</option>
          <option value="verified">Verified</option>
          <option value="rejected">Rejected</option>
          <option value="suspended">Suspended</option>
        </select>
      </template>
    </PageHeader>

    <!-- Loading -->
    <EmptyState v-if="isLoading" loading loading-message="Loading foundations..." />

    <!-- Empty -->
    <EmptyState v-else-if="filtered.length === 0" message="No foundations found." />

    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>Foundation</th>
            <th>Category</th>
            <th>Campaigns</th>
            <th>Total Raised</th>
            <th>Donors</th>
            <th>Followers</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="f in filtered" :key="f.id">

            <td>
              <div class="fdn-cell">
                <AvatarInitials :name="f.name" :seed="f.id" :size="38" />
                <div>
                  <strong>{{ f.name }}</strong>
                  <small>{{ f.user?.email || 'No email' }}</small>
                </div>
              </div>
            </td>

            <td>
              <span class="cat-badge">
                {{ f.category?.name || 'No Category' }}
              </span>
            </td>

            <td>{{ f.campaigns_count ?? 0 }}</td>

            <td><strong>₱{{ formatMoney(f.total_raised ?? 0) }}</strong></td>

            <td>{{ f.donors_count ?? 0 }}</td>

            <td>{{ f.followers_count ?? 0 }}</td>

            <td>
              <span class="status-badge" :class="statusClass(f.status)">
                {{ statusLabel(f.status) }}
              </span>
            </td>

            <td>{{ formatDate(f.created_at) }}</td>

            <td>
              <div class="actions">
                <button class="action-btn view" title="View" @click="openView(f)">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>

                <button
                  class="action-btn"
                  :class="f.status === 'suspended' ? 'restore' : 'danger'"
                  :title="f.status === 'suspended' ? 'Unsuspend' : 'Suspend'"
                  @click="toggleSuspend(f)"
                >
                  <svg v-if="f.status !== 'suspended'" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                  </svg>
                  <svg v-else width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </button>
              </div>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    <!-- VIEW MODAL -->
    <BaseModal
      :model-value="!!viewFoundation"
      @update:model-value="v => !v && (viewFoundation = null)"
      width="500px"
    >
      <template #header>
        <div class="modal-header">
          <AvatarInitials
            v-if="viewFoundation"
            :name="viewFoundation.name"
            :seed="viewFoundation.id"
            :size="52"
            :radius="14"
          />
          <div>
            <h3>{{ viewFoundation?.name }}</h3>
            <p class="modal-email">{{ viewFoundation?.user?.email || '—' }}</p>
          </div>
          <button class="btn-icon-close" @click="viewFoundation = null">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </template>

      <div class="modal-grid" v-if="viewFoundation">
        <div class="mg-item">
          <span class="mg-label">Status</span>
          <span class="status-badge" :class="statusClass(viewFoundation.status)">
            {{ statusLabel(viewFoundation.status) }}
          </span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Category</span>
          <span>{{ viewFoundation.category?.name || '—' }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">City</span>
          <span>{{ viewFoundation.city_municipality || '—' }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Province</span>
          <span>{{ viewFoundation.province || '—' }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Barangay</span>
          <span>{{ viewFoundation.barangay || '—' }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Street</span>
          <span>{{ viewFoundation.street || '—' }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Campaigns</span>
          <span>{{ viewFoundation.campaigns_count ?? 0 }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Donors</span>
          <span>{{ viewFoundation.donors_count ?? 0 }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Followers</span>
          <span>{{ viewFoundation.followers_count ?? 0 }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Total Raised</span>
          <span>₱{{ formatMoney(viewFoundation.total_raised ?? 0) }}</span>
        </div>
        <div class="mg-item">
          <span class="mg-label">Joined</span>
          <span>{{ formatDate(viewFoundation.created_at) }}</span>
        </div>
        <div class="mg-item mg-item-full">
          <span class="mg-label">Description</span>
          <span>{{ viewFoundation.description || '—' }}</span>
        </div>
      </div>

      <template #footer>
        <button class="btn-cancel" @click="viewFoundation = null">Close</button>
        <button
          class="btn-danger"
          v-if="viewFoundation && viewFoundation.status !== 'suspended'"
          @click="toggleSuspend(viewFoundation); viewFoundation = null"
        >Suspend</button>
        <button
          class="btn-save"
          v-else-if="viewFoundation"
          @click="toggleSuspend(viewFoundation); viewFoundation = null"
        >Unsuspend</button>
      </template>
    </BaseModal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"
import { useSearch } from "../composable/usesearch"
import PageHeader from '../components/superadmin/pageheader.vue'
import EmptyState from '../components/superadmin/emptystate.vue'
import BaseModal from '../components/superadmin/basemodal.vue'
import AvatarInitials from '../components/superadmin/avatarinitials.vue'

const foundations  = ref([])
const { searchQuery } = useSearch()
const filterStatus = ref("")
const isLoading    = ref(false)
const viewFoundation = ref(null)

/* ── FETCH ── */
async function fetchFoundations() {
  isLoading.value = true
  try {
    const res = await api.get("/admin/foundations")
    foundations.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error("Failed to load foundations:", err)
    foundations.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchFoundations)

/* ── FILTER ── */
const filtered = computed(() =>
  foundations.value.filter(f => {
    const matchSearch = (f.name || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchStatus = filterStatus.value === "" || f.status === filterStatus.value
    return matchSearch && matchStatus
  })
)

/* ── TOGGLE SUSPEND ── */
async function toggleSuspend(f) {
  const endpoint = f.status === 'suspended'
    ? `/admin/foundations/${f.id}/unsuspend`
    : `/admin/foundations/${f.id}/suspend`

  try {
    await api.patch(endpoint)
    const target = foundations.value.find(x => x.id === f.id)
    if (target) target.status = f.status === 'suspended' ? 'verified' : 'suspended'
  } catch (err) {
    console.error('Suspend toggle failed:', err)
  }
}

/* ── VIEW ── */
function openView(f) { viewFoundation.value = { ...f } }

/* ── HELPERS ── */
function formatDate(date) {
  if (!date) return "—"
  return new Date(date).toLocaleDateString()
}

function formatMoney(val) {
  return Number(val || 0).toLocaleString()
}

function statusClass(status) {
  if (status === 'verified')             return 'active'
  if (status === 'pending_verification') return 'pending'
  if (status === 'rejected')             return 'rejected'
  if (status === 'suspended')            return 'suspended'
  return status
}

function statusLabel(status) {
  if (status === 'verified')             return 'Verified'
  if (status === 'pending_verification') return 'Pending'
  if (status === 'rejected')             return 'Rejected'
  if (status === 'suspended')            return 'Suspended'
  return status
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.filter-select { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; background: white; color: #0F2D52; outline: none; cursor: pointer; }

.table-card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }

table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 1px solid #f1f5f9; }
thead th { padding: 14px 18px; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }
tbody td { padding: 14px 18px; font-size: 0.85rem; color: #475569; }

.fdn-cell   { display: flex; align-items: center; gap: 12px; }
.fdn-cell strong { display: block; color: #0F2D52; font-size: 0.87rem; }
.fdn-cell small  { color: #94a3b8; font-size: 0.75rem; }

.cat-badge { background: #eff6ff; color: #3b82f6; font-size: 0.74rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; }

.status-badge { font-size: 0.74rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.status-badge.active    { background: #f0fdf4; color: #059669; }
.status-badge.pending   { background: #fefce8; color: #92400e; }
.status-badge.rejected  { background: #fef2f2; color: #991b1b; }
.status-badge.suspended { background: #fef2f2; color: #dc2626; }

.actions    { display: flex; gap: 6px; }
.action-btn { width: 30px; height: 30px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn.view    { background: #eff6ff; color: #3b82f6; }
.action-btn.danger  { background: #fef2f2; color: #dc2626; }
.action-btn.restore { background: #f0fdf4; color: #059669; }
.action-btn:hover   { opacity: 0.75; transform: scale(1.1); }

/* Modal content (structure/overlay now handled by BaseModal) */
.modal-header { display: flex; align-items: center; gap: 14px; padding: 28px 28px 20px; }
.modal-header h3 { font-size: 1rem; color: #0F2D52; margin: 0 0 3px; }
.modal-email { font-size: 0.8rem; color: #94a3b8; margin: 0; }
.btn-icon-close { margin-left: auto; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; flex-shrink: 0; }
.btn-icon-close:hover { background: #f1f5f9; }

.modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; border-radius: 12px; padding: 16px; }
.mg-item { display: flex; flex-direction: column; gap: 4px; }
.mg-item-full { grid-column: span 2; }
.mg-label { font-size: 0.72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }

.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-save   { padding: 9px 20px; border: none; background: #059669; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-danger { padding: 9px 20px; border: none; background: #dc2626; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
</style>