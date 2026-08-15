<template>
  <div class="updates-panel">

    <div class="panel-head">
      <div class="panel-head-left">
        <h3 class="panel-title">Campaign Updates</h3>
        <span v-if="updates.length" class="updates-count-chip">{{ updates.length }}</span>
      </div>
      <button
        v-if="campaign?.status === 'completed' && !showForm"
        class="post-btn"
        @click="showForm = true"
      >
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Post Update
      </button>
    </div>

    <p v-if="campaign?.status !== 'completed'" class="hint-text">
      Updates can be posted once this campaign is marked as completed.
    </p>

    <!-- POST FORM (create new) -->
    <form v-if="showForm" @submit.prevent="submitUpdate" class="update-form">

      <div class="field">
        <label>Title</label>
        <input v-model="form.title" type="text" class="field-input" placeholder="e.g. Goal reached! Here's what happened" />
      </div>

      <div class="field">
        <label>Details</label>
        <textarea v-model="form.content" class="field-input field-textarea" placeholder="Describe how the donations were used..."></textarea>
      </div>

      <div class="field">
        <label>Photos</label>
        <input type="file" accept="image/*" multiple class="field-input" @change="handlePhotos" />
        <p v-if="form.photos.length" class="file-count">{{ form.photos.length }} photo(s) selected</p>
      </div>

      <p v-if="errorMsg" class="error-text">{{ errorMsg }}</p>

      <div class="btn-row">
        <button type="button" class="cancel-btn" @click="cancelForm">Cancel</button>
        <button type="submit" class="submit-btn" :disabled="isLoading">
          <span v-if="!isLoading">Post Update</span>
          <span v-else class="spinner" />
        </button>
      </div>

    </form>

    <!-- UPDATES LIST -->
    <div v-if="isLoadingList" class="empty-state">
      <div class="spinner-dark" />
    </div>

    <div v-else-if="updates.length === 0 && !showForm" class="empty-state">
      <span class="empty-icon">📋</span>
      <p>No updates posted yet.</p>
    </div>

    <div v-else class="updates-list">
      <div v-for="u in updates" :key="u.id" class="update-card">

        <!-- VIEW MODE -->
        <template v-if="editingId !== u.id">
          <div class="update-head">
            <h4>{{ u.title }}</h4>
            <div class="update-head-right">
              <span class="update-date">{{ formatDate(u.created_at) }}</span>
              <button class="icon-btn" title="Edit" @click="startEdit(u)">✎</button>
              <button class="icon-btn danger" title="Delete" @click="confirmDeleteUpdate(u)">×</button>
            </div>
          </div>
          <p v-if="u.content" class="update-content">{{ u.content }}</p>
          <div v-if="u.photos && u.photos.length" class="update-photos">
            <img
              v-for="(photo, i) in u.photos"
              :key="i"
              :src="photoUrl(photo)"
              class="update-photo"
              @click="openPhoto(photo)"
            />
          </div>

          <!-- REACTION FOOTER -->
          <div class="reaction-footer">
            <button
              class="reaction-pill"
              :class="{ disabled: !(u.reactions_count > 0) }"
              :disabled="!(u.reactions_count > 0)"
              @click="openReactors(u)"
            >
              <span class="reaction-heart">❤️</span>
              <span class="reaction-text">
                {{ u.reactions_count > 0
                  ? u.reactions_count + (u.reactions_count === 1 ? ' donor reacted' : ' donors reacted')
                  : 'No reactions yet' }}
              </span>
            </button>
          </div>
        </template>

        <!-- EDIT MODE -->
        <form v-else @submit.prevent="saveEdit(u)" class="update-form edit-form">

          <div class="field">
            <label>Title</label>
            <input v-model="editForm.title" type="text" class="field-input" />
          </div>

          <div class="field">
            <label>Details</label>
            <textarea v-model="editForm.content" class="field-input field-textarea"></textarea>
          </div>

          <div class="field" v-if="editForm.existingPhotos.length">
            <label>Current Photos</label>
            <div class="update-photos">
              <div
                v-for="(photo, i) in editForm.existingPhotos"
                :key="i"
                class="edit-photo-wrap"
              >
                <img :src="photoUrl(photo)" class="update-photo" />
                <button type="button" class="remove-photo-btn" @click="removeExistingPhoto(i)">×</button>
              </div>
            </div>
          </div>

          <div class="field">
            <label>Add Photos</label>
            <input type="file" accept="image/*" multiple class="field-input" @change="handleEditPhotos" />
            <p v-if="editForm.newPhotos.length" class="file-count">{{ editForm.newPhotos.length }} new photo(s) selected</p>
          </div>

          <p v-if="editErrorMsg" class="error-text">{{ editErrorMsg }}</p>

          <div class="btn-row">
            <button type="button" class="cancel-btn" @click="cancelEdit">Cancel</button>
            <button type="submit" class="submit-btn" :disabled="isEditSaving">
              <span v-if="!isEditSaving">Save Changes</span>
              <span v-else class="spinner" />
            </button>
          </div>

        </form>

      </div>
    </div>

    <!-- WHO REACTED MODAL -->
    <div class="modal-overlay" v-if="reactorsTarget" @click.self="reactorsTarget = null">
      <div class="modal-sm reactors-modal">
        <div class="reactors-head">
          <h4>
            <span class="reactors-heart">❤️</span>
            Reactions
          </h4>
          <button class="modal-close-btn" @click="reactorsTarget = null">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
        <p class="reactors-subtitle">{{ reactorsTarget.title }}</p>

        <div v-if="isLoadingReactors" class="empty-state">
          <div class="spinner-dark" />
        </div>

        <div v-else-if="reactors.length === 0" class="empty-state small">
          <span class="empty-icon">💭</span>
          <p>No reactions yet.</p>
        </div>

        <div v-else class="reactors-list">
          <div v-for="r in reactors" :key="r.id" class="reactor-row">
            <div class="reactor-avatar">
              <img v-if="r.profile_photo" :src="photoUrl(r.profile_photo)" alt="" />
              <span v-else>{{ initials(r.name) }}</span>
            </div>
            <div class="reactor-info">
              <span class="reactor-name">{{ r.name }}</span>
              <span class="reactor-time">{{ r.reacted_at }}</span>
            </div>
            <span class="reactor-heart-small">❤️</span>
          </div>
        </div>
      </div>
    </div>

    <!-- DELETE CONFIRM -->
    <div class="modal-overlay" v-if="deleteTarget" @click.self="deleteTarget = null">
      <div class="modal-sm">
        <h4>Delete Update</h4>
        <p>Are you sure you want to delete "<strong>{{ deleteTarget.title }}</strong>"? This cannot be undone.</p>
        <div class="btn-row">
          <button class="cancel-btn" @click="deleteTarget = null">Cancel</button>
          <button class="danger-btn" @click="doDeleteUpdate" :disabled="isDeleting">
            <span v-if="!isDeleting">Delete</span>
            <span v-else class="spinner" />
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue"
import api from "@/services/api"

const props = defineProps({
  campaign: { type: Object, required: true }
})

const showForm = ref(false)
const isLoading = ref(false)
const isLoadingList = ref(false)
const errorMsg = ref("")
const updates = ref([])

const form = reactive({
  title: "",
  content: "",
  photos: []
})

function handlePhotos(e) {
  form.photos = Array.from(e.target.files || [])
}

function cancelForm() {
  showForm.value = false
  form.title = ""
  form.content = ""
  form.photos = []
  errorMsg.value = ""
}

async function fetchUpdates() {
  if (!props.campaign?.id) return
  isLoadingList.value = true
  try {
    const res = await api.get(`/foundation/campaigns/${props.campaign.id}/updates`)
    updates.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error("Failed to load updates:", err)
  } finally {
    isLoadingList.value = false
  }
}

async function submitUpdate() {
  errorMsg.value = ""

  if (!form.title) {
    errorMsg.value = "Please add a title for this update."
    return
  }

  isLoading.value = true

  try {
    const formData = new FormData()
    formData.append("title", form.title)
    if (form.content) formData.append("content", form.content)
    form.photos.forEach(photo => formData.append("photos[]", photo))

    const res = await api.post(
      `/foundation/campaigns/${props.campaign.id}/updates`,
      formData,
      { headers: { "Content-Type": "multipart/form-data" } }
    )

    updates.value.unshift({ ...res.data.update, reactions_count: 0 })
    cancelForm()

  } catch (err) {
    console.error("Failed to post update:", err)
    errorMsg.value = err.response?.data?.message || "Failed to post update. Please try again."
  } finally {
    isLoading.value = false
  }
}

/* ══════════════════════════════
   EDIT
══════════════════════════════ */
const editingId = ref(null)
const isEditSaving = ref(false)
const editErrorMsg = ref("")

const editForm = reactive({
  title: "",
  content: "",
  existingPhotos: [],
  newPhotos: []
})

function startEdit(u) {
  showForm.value = false
  editingId.value = u.id
  editErrorMsg.value = ""
  editForm.title = u.title
  editForm.content = u.content || ""
  editForm.existingPhotos = Array.isArray(u.photos) ? [...u.photos] : []
  editForm.newPhotos = []
}

function cancelEdit() {
  editingId.value = null
  editErrorMsg.value = ""
  editForm.newPhotos = []
}

function handleEditPhotos(e) {
  editForm.newPhotos = Array.from(e.target.files || [])
}

function removeExistingPhoto(i) {
  editForm.existingPhotos.splice(i, 1)
}

async function saveEdit(u) {
  editErrorMsg.value = ""

  if (!editForm.title) {
    editErrorMsg.value = "Please add a title for this update."
    return
  }

  isEditSaving.value = true

  try {
    const formData = new FormData()
    formData.append("title", editForm.title)
    if (editForm.content) formData.append("content", editForm.content)
    editForm.existingPhotos.forEach(p => formData.append("existing_photos[]", p))
    editForm.newPhotos.forEach(photo => formData.append("photos[]", photo))

    const res = await api.post(
      `/foundation/campaigns/${props.campaign.id}/updates/${u.id}`,
      formData,
      { headers: { "Content-Type": "multipart/form-data" } }
    )

    const idx = updates.value.findIndex(item => item.id === u.id)
    if (idx !== -1) updates.value[idx] = { ...updates.value[idx], ...res.data.update }

    editingId.value = null

  } catch (err) {
    console.error("Failed to edit update:", err)
    editErrorMsg.value = err.response?.data?.message || "Failed to save changes. Please try again."
  } finally {
    isEditSaving.value = false
  }
}

/* ══════════════════════════════
   DELETE
══════════════════════════════ */
const deleteTarget = ref(null)
const isDeleting = ref(false)

function confirmDeleteUpdate(u) {
  deleteTarget.value = u
}

async function doDeleteUpdate() {
  if (!deleteTarget.value) return
  isDeleting.value = true
  try {
    await api.delete(`/foundation/campaigns/${props.campaign.id}/updates/${deleteTarget.value.id}`)
    updates.value = updates.value.filter(item => item.id !== deleteTarget.value.id)
    deleteTarget.value = null
  } catch (err) {
    console.error("Failed to delete update:", err)
  } finally {
    isDeleting.value = false
  }
}

/* ══════════════════════════════
   WHO REACTED
══════════════════════════════ */
const reactorsTarget = ref(null)
const reactors = ref([])
const isLoadingReactors = ref(false)

async function openReactors(u) {
  reactorsTarget.value = u
  reactors.value = []
  isLoadingReactors.value = true
  try {
    const res = await api.get(`/foundation/campaigns/${props.campaign.id}/updates/${u.id}/reactors`)
    reactors.value = res.data?.donors || []
  } catch (err) {
    console.error("Failed to load reactors:", err)
  } finally {
    isLoadingReactors.value = false
  }
}

function initials(name) {
  if (!name) return "?"
  return name
    .split(" ")
    .map(w => w[0])
    .join("")
    .toUpperCase()
    .slice(0, 2)
}

function formatDate(d) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" })
}

function photoUrl(path) {
  return `/storage/${path}`
}

function openPhoto(path) {
  window.open(photoUrl(path), "_blank")
}

onMounted(fetchUpdates)
</script>

<style scoped>
.updates-panel {
  background: white;
  border-radius: 16px;
  border: 1px solid #e8edf3;
  padding: 22px 24px;
  margin-top: 20px;
  box-shadow: 0 1px 6px rgba(15,45,82,0.04);
}

.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.panel-head-left { display: flex; align-items: center; gap: 9px; }

.panel-title { font-size: 0.98rem; font-weight: 800; color: #0F2D52; margin: 0; letter-spacing: -0.01em; }

.updates-count-chip {
  background: #eff6ff; color: #3b82f6; font-size: 0.72rem; font-weight: 700;
  padding: 2px 9px; border-radius: 20px;
}

.post-btn {
  display: flex; align-items: center; gap: 6px;
  background: #0F2D52;
  color: white;
  border: none;
  border-radius: 9px;
  padding: 9px 17px;
  font-size: 0.81rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
}
.post-btn:hover { background: #1a3f6b; transform: translateY(-1px); }

.hint-text { font-size: 0.81rem; color: #94a3b8; margin: 10px 0 0; }

/* FORM */
.update-form {
  margin-top: 16px;
  padding: 18px;
  background: #f8fafc;
  border: 1px solid #e8edf3;
  border-radius: 12px;
}

.field { margin-bottom: 13px; }

label { font-size: 0.82rem; font-weight: 700; display: block; margin-bottom: 6px; color: #1e293b; }

.field-input {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 9px;
  outline: none;
  font-size: 0.85rem;
  font-family: inherit;
  box-sizing: border-box;
  transition: border-color 0.15s;
}
.field-input:focus { border-color: #0F2D52; box-shadow: 0 0 0 3px rgba(15,45,82,0.08); }
.field-textarea { height: 80px; resize: none; }

.file-count { font-size: 0.76rem; color: #94a3b8; margin-top: 5px; }

.error-text {
  font-size: 0.8rem;
  font-weight: 600;
  color: #cc3333;
  background: #fff5f5;
  border: 1px solid #ffd0d0;
  border-radius: 9px;
  padding: 9px 13px;
  margin-bottom: 10px;
}

.btn-row { display: flex; gap: 10px; }

.cancel-btn {
  flex: 1;
  padding: 10px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 9px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.15s;
}
.cancel-btn:hover { background: #e2e8f0; }

.submit-btn {
  flex: 2;
  padding: 10px;
  background: #0F2D52;
  color: white;
  border: none;
  border-radius: 9px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.submit-btn:hover:not(:disabled) { background: #1a3f6b; }
.submit-btn:disabled { opacity: 0.65; cursor: not-allowed; }

.danger-btn {
  flex: 2;
  padding: 10px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 9px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.danger-btn:hover:not(:disabled) { background: #b91c1c; }
.danger-btn:disabled { opacity: 0.65; cursor: not-allowed; }

/* LIST */
.empty-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 28px; color: #94a3b8; font-size: 0.85rem; gap: 8px;
}
.empty-state.small { padding: 18px; }
.empty-icon { font-size: 1.7rem; }

.updates-list { margin-top: 16px; display: flex; flex-direction: column; gap: 14px; }

.update-card {
  border: 1px solid #f0f3f7;
  border-radius: 12px;
  padding: 16px 18px;
  background: #fafbfd;
  transition: box-shadow 0.2s;
}
.update-card:hover { box-shadow: 0 2px 10px rgba(15,45,82,0.05); }

.update-head { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; }
.update-head h4 { margin: 0; font-size: 0.9rem; font-weight: 700; color: #0F2D52; }
.update-head-right { display: flex; align-items: center; gap: 6px; }
.update-date { font-size: 0.72rem; color: #94a3b8; white-space: nowrap; }

.icon-btn {
  border: none;
  background: #f1f5f9;
  color: #475569;
  width: 23px;
  height: 23px;
  border-radius: 7px;
  font-size: 0.75rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.icon-btn:hover { background: #e2e8f0; }
.icon-btn.danger { color: #dc2626; }
.icon-btn.danger:hover { background: #fef2f2; }

.update-content { font-size: 0.83rem; color: #475569; margin: 9px 0 0; line-height: 1.65; }

.update-photos { display: flex; gap: 8px; margin-top: 11px; flex-wrap: wrap; }
.update-photo {
  width: 90px; height: 90px; object-fit: cover; border-radius: 9px;
  cursor: pointer; border: 1px solid #e2e8f0; transition: transform 0.15s;
}
.update-photo:hover { transform: scale(1.04); }

.edit-photo-wrap { position: relative; }
.remove-photo-btn {
  position: absolute; top: -6px; right: -6px;
  width: 18px; height: 18px; border-radius: 50%;
  border: none; background: #dc2626; color: white;
  font-size: 0.7rem; cursor: pointer; line-height: 1;
  display: flex; align-items: center; justify-content: center;
}

.edit-form { margin-top: 12px; }

/* REACTION FOOTER */
.reaction-footer {
  margin-top: 13px;
  padding-top: 12px;
  border-top: 1px solid #f0f3f7;
}
.reaction-pill {
  display: inline-flex; align-items: center; gap: 8px;
  border: 1px solid #fbcfe8;
  background: #fdf2f8;
  padding: 7px 14px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 0.79rem;
  font-weight: 600;
  color: #be185d;
  transition: all 0.15s;
}
.reaction-pill:hover:not(.disabled) { background: #fce7f3; border-color: #f9a8d4; transform: translateY(-1px); }
.reaction-pill.disabled {
  cursor: default;
  background: #f8fafc;
  border-color: #e8edf3;
  color: #94a3b8;
}
.reaction-heart { font-size: 0.95rem; }

/* WHO REACTED MODAL */
.reactors-modal { width: 380px; padding: 22px; }
.reactors-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.reactors-head h4 { display: flex; align-items: center; gap: 7px; margin: 0; font-size: 1rem; color: #0F2D52; }
.reactors-heart { font-size: 1rem; }
.reactors-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0 0 14px; }

.modal-close-btn {
  width: 26px; height: 26px; border-radius: 7px; border: 1px solid #e2e8f0;
  background: transparent; display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #64748b; transition: background 0.15s;
}
.modal-close-btn:hover { background: #f1f5f9; }

.reactors-list {
  display: flex; flex-direction: column; gap: 4px;
  max-height: 320px; overflow-y: auto;
}

.reactor-row {
  display: flex; align-items: center; gap: 11px;
  padding: 9px 6px;
  border-radius: 9px;
  transition: background 0.15s;
}
.reactor-row:hover { background: #f8fafc; }

.reactor-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: #0F2D52; color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; font-weight: 700;
  flex-shrink: 0; overflow: hidden;
}
.reactor-avatar img { width: 100%; height: 100%; object-fit: cover; }

.reactor-info { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.reactor-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.reactor-time { font-size: 0.71rem; color: #94a3b8; }
.reactor-heart-small { font-size: 0.85rem; flex-shrink: 0; }

/* DELETE MODAL */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,45,82,0.4);
  backdrop-filter: blur(3px);
  display: flex; align-items: center; justify-content: center; z-index: 100; padding: 24px;
}
.modal-sm {
  background: white; border-radius: 16px; padding: 22px; width: 380px;
  box-shadow: 0 20px 60px rgba(15,45,82,0.2);
}
.modal-sm h4 { margin: 0 0 10px; color: #0F2D52; font-size: 1rem; }
.modal-sm p { font-size: 0.85rem; color: #475569; margin: 0 0 18px; line-height: 1.5; }

/* SPINNERS */
.spinner {
  width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.35);
  border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite;
}
.spinner-dark {
  width: 24px; height: 24px; border: 3px solid #e2e8f0;
  border-top-color: #0F2D52; border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>