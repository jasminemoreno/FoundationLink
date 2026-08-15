<template>
  <div class="page">

    <PageHeader title="Categories" subtitle="Manage campaign categories across the platform">
      <template #actions>
        <button class="btn-add" @click="openAddModal">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Category
        </button>
      </template>
    </PageHeader>

    <!-- EMPTY STATE -->
    <EmptyState v-if="categories.length === 0" message="No categories yet. Create one to get started." />

    <!-- GRID -->
    <div v-else class="categories-grid">
      <div class="cat-card" v-for="cat in categories" :key="cat.id">

        <div class="cat-top">
          <div class="cat-icon"
               :style="{ background: (cat.color || '#0F2D52') + '18', color: cat.color || '#0F2D52' }">
            {{ cat.icon || '📁' }}
          </div>

          <div class="cat-menu">
            <button class="menu-btn edit" @click="openEditModal(cat)" title="Edit">✏️</button>
            <button class="menu-btn del"  @click="remove(cat.id)"    title="Delete">🗑️</button>
          </div>
        </div>

        <h3>{{ cat.name }}</h3>
        <p>{{ cat.description || 'No description' }}</p>

        <div class="cat-stats">
          <div class="cs">
            <span class="cs-val">{{ cat.campaigns_count ?? 0 }}</span>
            <span class="cs-label">Campaigns</span>
          </div>
          <div class="cs">
            <span class="cs-val">{{ cat.foundations_count ?? 0 }}</span>
            <span class="cs-label">Foundations</span>
          </div>
          <div class="cs">
            <span class="cs-val">₱{{ formatMoney(cat.total_raised ?? 0) }}</span>
            <span class="cs-label">Raised</span>
          </div>
        </div>

        <div class="cat-bar-wrap">
          <div class="cat-bar">
            <div class="cat-bar-fill"
                 :style="{ width: (cat.percentage ?? 0) + '%', background: cat.color || '#0F2D52' }">
            </div>
          </div>
          <span class="cat-pct">{{ cat.percentage ?? 0 }}% of total</span>
        </div>

        <span class="cat-status" :class="cat.is_active ? 'active' : 'inactive'">
          {{ cat.is_active ? 'Active' : 'Inactive' }}
        </span>

      </div>
    </div>

    <!-- ── ADD MODAL ── -->
    <BaseModal v-model="showAddModal" width="440px">
      <template #header>
        <h3 class="modal-title">Add New Category</h3>
      </template>

      <div class="form-group">
        <label>Category Name</label>
        <input v-model="newCat.name" type="text" placeholder="e.g. Education" />
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea v-model="newCat.description" placeholder="What is this category about?"></textarea>
      </div>

      <div class="form-group">
        <label>Color</label>
        <input v-model="newCat.color" type="color" />
      </div>

      <p v-if="addError" class="error-text">{{ addError }}</p>

      <template #footer>
        <button class="btn-cancel" @click="showAddModal = false">Cancel</button>
        <button class="btn-save" @click="addCategory" :disabled="addLoading">
          <span v-if="!addLoading">Save Category</span>
          <Spinner v-else :size="16" color="white" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </BaseModal>

    <!-- ── EDIT MODAL ── -->
    <BaseModal v-model="showEditModal" width="440px">
      <template #header>
        <h3 class="modal-title">Edit Category</h3>
      </template>

      <div class="form-group">
        <label>Category Name</label>
        <input v-model="editCat.name" type="text" />
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea v-model="editCat.description"></textarea>
      </div>

      <div class="form-group">
        <label>Color</label>
        <input v-model="editCat.color" type="color" />
      </div>

      <div class="form-group">
        <label>Status</label>
        <select v-model="editCat.is_active" class="select-input">
          <option :value="true">Active</option>
          <option :value="false">Inactive</option>
        </select>
      </div>

      <p v-if="editError" class="error-text">{{ editError }}</p>

      <template #footer>
        <button class="btn-cancel" @click="showEditModal = false">Cancel</button>
        <button class="btn-save" @click="saveEdit" :disabled="editLoading">
          <span v-if="!editLoading">Save Changes</span>
          <Spinner v-else :size="16" color="white" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </BaseModal>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import PageHeader from '../components/superadmin/pageheader.vue'
import EmptyState from '../components/superadmin/emptystate.vue'
import BaseModal from '../components/superadmin/basemodal.vue'
import Spinner from '../components/superadmin/Spinner.vue'

const categories = ref([])

/* ── ADD ── */
const showAddModal = ref(false)
const addLoading   = ref(false)
const addError     = ref('')
const newCat = ref({ name: '', description: '', color: '#0F2D52' })

function openAddModal() {
  newCat.value = { name: '', description: '', color: '#0F2D52' }
  addError.value = ''
  showAddModal.value = true
}

/* ── EDIT ── */
const showEditModal = ref(false)
const editLoading   = ref(false)
const editError     = ref('')
const editCat = ref({ id: null, name: '', description: '', color: '#0F2D52', is_active: true })

function openEditModal(cat) {
  editCat.value = {
    id:          cat.id,
    name:        cat.name,
    description: cat.description || '',
    color:       cat.color || '#0F2D52',
    is_active:   cat.is_active ?? true
  }
  editError.value = ''
  showEditModal.value = true
}

/* ── FETCH ── */
async function fetchCategories() {
  try {
    const res = await api.get('/admin/categories')
    categories.value = Array.isArray(res.data)
      ? res.data
      : (res.data?.data || [])
  } catch (err) {
    console.error('Failed to load categories:', err)
    categories.value = []
  }
}

onMounted(fetchCategories)

/* ── ADD CATEGORY ── */
async function addCategory() {
  addError.value = ''
  if (!newCat.value.name.trim()) {
    addError.value = 'Category name is required.'
    return
  }

  addLoading.value = true
  try {
    const res = await api.post('/admin/categories', newCat.value)
    const created = res.data?.category || res.data
    categories.value.unshift(created)
    showAddModal.value = false
  } catch (err) {
    addError.value = err.response?.data?.message || 'Failed to add category.'
  } finally {
    addLoading.value = false
  }
}

/* ── SAVE EDIT ── */
async function saveEdit() {
  editError.value = ''
  if (!editCat.value.name.trim()) {
    editError.value = 'Category name is required.'
    return
  }

  editLoading.value = true
  try {
    const res = await api.put(`/admin/categories/${editCat.value.id}`, {
      name:        editCat.value.name,
      description: editCat.value.description,
      color:       editCat.value.color,
      is_active:   editCat.value.is_active
    })

    const updated = res.data?.category || res.data

    // update in-place so UI refreshes instantly
    const idx = categories.value.findIndex(c => c.id === editCat.value.id)
    if (idx !== -1) categories.value[idx] = { ...categories.value[idx], ...updated }

    showEditModal.value = false
  } catch (err) {
    editError.value = err.response?.data?.message || 'Failed to save changes.'
  } finally {
    editLoading.value = false
  }
}

/* ── DELETE ── */
async function remove(id) {
  if (!confirm('Are you sure you want to delete this category?')) return
  try {
    await api.delete(`/admin/categories/${id}`)
    categories.value = categories.value.filter(c => c.id !== id)
  } catch (err) {
    console.error('Delete error:', err)
  }
}

/* ── FORMAT ── */
function formatMoney(val) {
  return Number(val || 0).toLocaleString()
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.btn-add { display: flex; align-items: center; gap: 7px; padding: 10px 18px; background: #0F2D52; color: white; border: none; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-add:hover { background: #1a3f6b; }

.categories-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }

.cat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); transition: transform 0.2s, box-shadow 0.2s; }
.cat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(15,45,82,0.1); }

.cat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
.cat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }

.cat-menu { display: flex; gap: 4px; }
.menu-btn { width: 28px; height: 28px; border: none; background: transparent; cursor: pointer; border-radius: 8px; font-size: 0.8rem; transition: background 0.2s; }
.menu-btn:hover      { background: #f1f5f9; }
.menu-btn.edit:hover { background: #eff6ff; }
.menu-btn.del:hover  { background: #fef2f2; }

.cat-card h3 { font-size: 0.95rem; color: #0F2D52; margin: 0 0 6px; }
.cat-card p  { font-size: 0.8rem; color: #64748b; line-height: 1.5; margin: 0 0 16px; }

.cat-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 14px; background: #f8fafc; border-radius: 10px; padding: 10px; }
.cs { text-align: center; }
.cs-val   { display: block; font-size: 0.95rem; font-weight: 700; color: #0F2D52; }
.cs-label { font-size: 0.68rem; color: #94a3b8; }

.cat-bar-wrap { margin-bottom: 14px; }
.cat-bar      { height: 6px; background: #f1f5f9; border-radius: 99px; overflow: hidden; margin-bottom: 4px; }
.cat-bar-fill { height: 100%; border-radius: 99px; }
.cat-pct      { font-size: 0.73rem; color: #94a3b8; }

.cat-status         { font-size: 0.74rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.cat-status.active   { background: #f0fdf4; color: #059669; }
.cat-status.inactive { background: #f1f5f9; color: #64748b; }

/* ── Modal content (structure/overlay now handled by BaseModal) ── */
.modal-title { font-size: 1.05rem; color: #0F2D52; margin: 0; padding: 28px 28px 0; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
.form-group input,
.form-group textarea,
.select-input {
  width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box;
  transition: border-color 0.2s; font-family: inherit;
}
.form-group input:focus,
.form-group textarea:focus,
.select-input:focus { border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.12); }
.form-group textarea       { height: 80px; resize: none; }
.form-group input[type="color"] { height: 44px; cursor: pointer; padding: 4px 8px; }
.select-input { appearance: none; cursor: pointer; }

.error-text { font-size: 0.82rem; font-weight: 600; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 4px; }

.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-save   { padding: 9px 20px; border: none; background: #0F2D52; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 110px; }
.btn-save:hover:not(:disabled) { background: #1a3f6b; }
.btn-save:disabled { opacity: 0.65; cursor: not-allowed; }
</style>