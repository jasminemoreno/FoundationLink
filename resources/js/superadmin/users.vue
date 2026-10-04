<template>
  <div class="page">

    <PageHeader title="Users">
      <template #default>{{ users.length }} registered users on the platform</template>
      <template #actions>
        <select v-model="filterRole" class="filter-select">
          <option value="">All Roles</option>
          <option value="donor">Donor</option>
          <option value="foundation_admin">Foundation Admin</option>
        </select>
      </template>
    </PageHeader>

    <!-- Summary Cards -->
    <div class="summary-row">
      <StatCard
        v-for="sm in summaries" :key="sm.label"
        layout="horizontal"
        :icon="sm.icon"
        :value="sm.value"
        :label="sm.label"
        :bg="sm.bg"
        :col="sm.col"
      />
    </div>

    <!-- Table -->
    <div class="table-card">

      <!-- Loading -->
      <EmptyState v-if="isLoading" loading loading-message="Loading users..." />

      <!-- Empty -->
      <EmptyState v-else-if="filtered.length === 0" message="No users found." />

      <table v-else>
        <thead>
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>Phone</th>
            <th>Joined</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in paginated" :key="u.id">

            <td>
              <div class="user-cell">
                <AvatarInitials :name="u.name" :seed="u.id" :size="36" :src="u.profile_photo || ''" />
                <div>
                  <strong>{{ u.name }}</strong>
                  <small>{{ u.email }}</small>
                </div>
              </div>
            </td>

            <td>
              <span class="role-badge" :class="u.role">{{ roleLabel(u.role) }}</span>
            </td>

            <td>{{ u.phone || '—' }}</td>
            <td>{{ u.joined }}</td>

            <td>
              <span class="status-badge" :class="u.status">{{ u.status }}</span>
            </td>

            <td>
              <div class="actions">
                <!-- VIEW -->
                <button class="action-btn view" title="View" @click="openView(u)">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>

                <!-- SUSPEND / ACTIVATE -->
                <button
                  class="action-btn"
                  :class="u.status === 'suspended' ? 'restore' : 'danger'"
                  :title="u.status === 'suspended' ? 'Activate' : 'Suspend'"
                  @click="toggleStatus(u)"
                >
                  <svg v-if="u.status !== 'suspended'" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
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

      <!-- Pagination -->
      <div class="pagination" v-if="!isLoading && filtered.length > 0">
        <span class="pag-info">
          Showing {{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, filtered.length) }}
          of {{ filtered.length }} users
        </span>
        <div class="pag-btns">
          <button class="pag-btn" :disabled="currentPage === 1" @click="currentPage--">← Prev</button>
          <button
            class="pag-btn"
            v-for="p in totalPages" :key="p"
            :class="{ active: p === currentPage }"
            @click="currentPage = p"
          >{{ p }}</button>
          <button class="pag-btn" :disabled="currentPage === totalPages" @click="currentPage++">Next →</button>
        </div>
      </div>
    </div>

    <!-- VIEW MODAL -->
    <BaseModal
      :model-value="!!viewUser"
      @update:model-value="v => !v && (viewUser = null)"
      width="420px"
    >
      <div class="modal-hero" v-if="viewUser">
        <AvatarInitials :name="viewUser.name" :seed="viewUser.id" :size="64" :radius="16" :src="viewUser.profile_photo || ''" />
        <h3>{{ viewUser.name }}</h3>
        <p class="modal-email">{{ viewUser.email }}</p>

        <div class="modal-grid">
          <div class="mg-item">
            <span class="mg-label">Role</span>
            <span class="role-badge" :class="viewUser.role">{{ roleLabel(viewUser.role) }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">Status</span>
            <span class="status-badge" :class="viewUser.status">{{ viewUser.status }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">Phone</span>
            <span>{{ viewUser.phone || '—' }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">Joined</span>
            <span>{{ viewUser.joined }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn-cancel" @click="viewUser = null">Close</button>
        <button
          class="btn-danger"
          v-if="viewUser && viewUser.status !== 'suspended'"
          @click="toggleStatus(viewUser); viewUser = null"
        >Suspend User</button>
        <button
          class="btn-save"
          v-else-if="viewUser"
          @click="toggleStatus(viewUser); viewUser = null"
        >Activate User</button>
      </template>
    </BaseModal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useSearch } from "../composable/usesearch"
import PageHeader from '../components/superadmin/pageheader.vue'
import StatCard from '../components/superadmin/statcard.vue'
import EmptyState from '../components/superadmin/emptystate.vue'
import BaseModal from '../components/superadmin/basemodal.vue'
import AvatarInitials from '../components/superadmin/avatarinitials.vue'

const { searchQuery } = useSearch()
const filterRole  = ref('')
const isLoading   = ref(false)
const currentPage = ref(1)
const perPage     = 10

const users   = ref([])
const viewUser = ref(null)

/* ── FETCH ── */
async function fetchUsers() {
  isLoading.value = true
  try {
    const res = await api.get('/admin/users')
    users.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error('Failed to load users:', err)
    users.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchUsers)

/* ── FILTER ── */
const filtered = computed(() => {
  currentPage.value = 1
  return users.value.filter(u => {
    const q = searchQuery.value.toLowerCase()
    const matchSearch = (u.name || '').toLowerCase().includes(q) ||
                        (u.email || '').toLowerCase().includes(q)
    const matchRole   = filterRole.value === '' || u.role === filterRole.value
    return matchSearch && matchRole
  })
})

/* ── PAGINATION ── */
const totalPages = computed(() => Math.ceil(filtered.value.length / perPage))
const paginated  = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filtered.value.slice(start, start + perPage)
})

/* ── TOGGLE STATUS ── */
async function toggleStatus(u) {
  const newStatus = u.status === 'suspended' ? 'active' : 'suspended'
  try {
    await api.patch(`/admin/users/${u.id}/status`, { status: newStatus })
    const target = users.value.find(x => x.id === u.id)
    if (target) target.status = newStatus
  } catch (err) {
    console.error('Status update failed:', err)
  }
}

/* ── VIEW MODAL ── */
function openView(u) { viewUser.value = { ...u } }

/* ── HELPERS ── */
function roleLabel(role) {
  return { donor: 'Donor', foundation_admin: 'Foundation Admin', superadmin: 'Super Admin' }[role] || role
}

const summaries = computed(() => {
  const total    = users.value.length
  const donors   = users.value.filter(u => u.role === 'donor').length
  const admins   = users.value.filter(u => u.role === 'foundation_admin').length
  const thisMonth = users.value.filter(u => {
    if (!u.created_at) return false
    return new Date(u.created_at).getMonth() === new Date().getMonth() &&
           new Date(u.created_at).getFullYear() === new Date().getFullYear()
  }).length

  return [
    { label: 'Total Users',        value: total,     bg: '#eff6ff', col: '#3b82f6', icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>` },
    { label: 'Donors',             value: donors,    bg: '#f0fdf4', col: '#059669', icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>` },
    { label: 'Foundation Admins',  value: admins,    bg: '#fefce8', col: '#ca8a04', icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3"/></svg>` },
    { label: 'New This Month',     value: thisMonth, bg: '#f5f3ff', col: '#7c3aed', icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>` },
  ]
})
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.filter-select { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; background: white; color: #0F2D52; outline: none; cursor: pointer; }

.summary-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }

.table-card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }

table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 1px solid #f1f5f9; }
thead th { padding: 14px 16px; text-align: left; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }
tbody td { padding: 12px 16px; font-size: 0.84rem; color: #475569; }

.user-cell   { display: flex; align-items: center; gap: 10px; }
.user-cell strong { display: block; color: #0F2D52; font-size: 0.86rem; }
.user-cell small  { color: #94a3b8; font-size: 0.74rem; }

.role-badge { font-size: 0.73rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.role-badge.donor            { background: #eff6ff; color: #3b82f6; }
.role-badge.foundation_admin { background: #fefce8; color: #ca8a04; }
.role-badge.superadmin       { background: #f5f3ff; color: #7c3aed; }

.status-badge { font-size: 0.73rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.status-badge.active    { background: #f0fdf4; color: #059669; }
.status-badge.inactive  { background: #f1f5f9; color: #64748b; }
.status-badge.suspended { background: #fef2f2; color: #dc2626; }

.actions    { display: flex; gap: 6px; }
.action-btn { width: 29px; height: 29px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn.view    { background: #eff6ff; color: #3b82f6; }
.action-btn.danger  { background: #fef2f2; color: #dc2626; }
.action-btn.restore { background: #f0fdf4; color: #059669; }
.action-btn:hover   { opacity: 0.75; transform: scale(1.1); }

.pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px; }
.pag-info { font-size: 0.8rem; color: #94a3b8; }
.pag-btns { display: flex; gap: 4px; flex-wrap: wrap; }
.pag-btn { padding: 6px 12px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-size: 0.8rem; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.pag-btn.active { background: #0F2D52; color: white; border-color: #0F2D52; }
.pag-btn:hover:not(.active):not(:disabled) { background: #f1f5f9; }
.pag-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Modal content (structure/overlay now handled by BaseModal) */
.modal-hero { text-align: center; padding: 28px 28px 0; }
.modal-hero .avatar-initials { margin: 0 auto 14px; }
.modal-hero h3 { font-size: 1.1rem; color: #0F2D52; margin: 0 0 4px; }
.modal-email { font-size: 0.83rem; color: #94a3b8; margin: 0 0 20px; }
.modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; background: #f8fafc; border-radius: 12px; padding: 16px; text-align: left; }
.mg-item { display: flex; flex-direction: column; gap: 5px; }
.mg-label { font-size: 0.72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }

.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-save   { padding: 9px 20px; border: none; background: #059669; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-danger { padding: 9px 20px; border: none; background: #dc2626; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
</style>