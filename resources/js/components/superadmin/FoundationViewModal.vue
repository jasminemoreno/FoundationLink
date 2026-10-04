<template>
  <div v-if="modelValue" class="modal-overlay" @click.self="close">

    <div class="modal-card">

      <!-- HEADER -->
      <div class="modal-top">
        <div class="modal-title-group">
          <div class="modal-title-row">
            <span class="modal-title">{{ foundation?.name }}</span>
            <span class="f-badge" :class="foundation?.status">
              {{ statusLabel(foundation?.status) }}
            </span>
          </div>
          <span class="modal-sub">
            Submitted {{ foundation?.submitted }} · Foundation ID #{{ foundation?.id }}
          </span>
        </div>
        <button class="btn-icon-close" @click="close" aria-label="Close">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <!-- BODY -->
      <div class="modal-body">

        <!-- FOUNDATION DETAILS -->
        <section>
          <div class="section-label">Foundation details</div>
          <div class="info-grid">

            <div class="info-item">
              <div class="info-item-label">Street / Purok</div>
              <div class="info-item-val">{{ foundation?.street || '—' }}</div>
            </div>

            <div class="info-item">
              <div class="info-item-label">Barangay</div>
              <div class="info-item-val">{{ foundation?.barangay || '—' }}</div>
            </div>

            <div class="info-item">
              <div class="info-item-label">City / Municipality</div>
              <div class="info-item-val">{{ foundation?.city_municipality || '—' }}</div>
            </div>

            <div class="info-item">
              <div class="info-item-label">Province</div>
              <div class="info-item-val">{{ foundation?.province || '—' }}</div>
            </div>

            <div class="info-item">
  <div class="info-item-label">Category</div>
  <div class="info-item-val">{{ foundation?.category?.name || '—' }}</div>
</div>

            <div class="info-item">
              <div class="info-item-label">Status</div>
              <div class="info-item-val">{{ statusLabel(foundation?.status) }}</div>
            </div>

            <div class="info-item info-item-full">
              <div class="info-item-label">Description</div>
              <div class="info-item-val info-desc">{{ foundation?.description || 'No description provided.' }}</div>
            </div>

          </div>
        </section>

        <!-- ADMIN -->
        <section>
          <div class="section-label">Foundation admin</div>
          <div class="admin-row">
            <div class="admin-avatar">{{ adminInitials }}</div>
            <div>
              <div class="admin-name">
                {{ foundation?.user?.first_name }} {{ foundation?.user?.last_name }}
              </div>
              <div class="admin-contacts">
                <span class="admin-contact">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                  </svg>
                  {{ foundation?.user?.email || '—' }}
                </span>
                <span class="admin-contact">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.61 5 2 2 0 0 1 3.6 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.6a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.5 18v1.42"/>
                  </svg>
                  {{ foundation?.user?.phone || '—' }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- IDENTITY DOCS -->
        <section>
          <div class="section-label">
            Identity documents
            <span v-if="foundation?.identity_documents?.[0]?.document_type" class="section-tag">
              {{ foundation.identity_documents[0].document_type }}
            </span>
          </div>
          <div class="doc-grid">
            <div
              v-for="doc in foundation?.identity_documents"
              :key="doc.id"
              class="doc-img-wrap"
              @click="preview(getImage(doc.file_path))"
            >
              <img :src="getImage(doc.file_path)" :alt="doc.file_name" />
              <div class="doc-overlay">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </div>
            </div>
            <div v-if="!foundation?.identity_documents?.length" class="doc-placeholder">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="7" width="20" height="15" rx="2"/>
                <path d="M16 3H8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"/>
              </svg>
              No documents uploaded
            </div>
          </div>
        </section>

        <!-- LEGITIMACY DOCS -->
        <section>
          <div class="section-label">
            Legitimacy documents
            <span v-if="foundation?.legitimacy_documents?.[0]?.document_type" class="section-tag">
              {{ foundation.legitimacy_documents[0].document_type }}
            </span>
          </div>
          <div class="doc-grid">
            <div
              v-for="doc in foundation?.legitimacy_documents"
              :key="doc.id"
              class="doc-img-wrap"
              @click="preview(getImage(doc.file_path))"
            >
              <img :src="getImage(doc.file_path)" :alt="doc.file_name" />
              <div class="doc-overlay">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </div>
            </div>
            <div v-if="!foundation?.legitimacy_documents?.length" class="doc-placeholder">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              No documents uploaded
            </div>
          </div>
        </section>

      </div>

      <!-- FOOTER -->
      <div class="modal-footer">
        <button class="btn-close-modal" @click="close">Close</button>
        <template v-if="foundation?.status === 'pending'">
          <button class="btn-reject-modal" @click="$emit('reject', foundation); close()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            Reject
          </button>
          <button class="btn-approve-modal" @click="$emit('approve', foundation); close()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Approve
          </button>
        </template>
      </div>

    </div>

    <!-- LIGHTBOX -->
    <div v-if="previewImg" class="lightbox" @click="previewImg = null">
      <img :src="previewImg" alt="Document preview" />
      <button class="lightbox-close" @click.stop="previewImg = null" aria-label="Close preview">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from "vue"

const props = defineProps({
  modelValue: Boolean,
  foundation: Object
})

const emit = defineEmits(["update:modelValue", "approve", "reject"])

const previewImg = ref(null)

function close() {
  emit("update:modelValue", false)
}

function preview(src) {
  previewImg.value = src
}

function getImage(path) {
  if (!path) return ""
  return `http://127.0.0.1:8000/storage/${path}`
}

function statusLabel(s) {
  if (s === 'pending')  return 'Pending'
  if (s === 'approved') return 'Approved'
  if (s === 'rejected') return 'Rejected'
  return s || ''
}

const adminInitials = computed(() => {
  const u = props.foundation?.user
  if (!u) return 'A'
  return ((u.first_name?.[0] || '') + (u.last_name?.[0] || '')).toUpperCase() || 'A'
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  padding: 24px;
}

.modal-card {
  width: 100%;
  max-width: 660px;
  background: #fff;
  border-radius: 16px;
  border: 0.5px solid #e2e8f0;
  overflow: hidden;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

/* HEADER */
.modal-top {
  padding: 18px 24px 16px;
  border-bottom: 0.5px solid #e2e8f0;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-shrink: 0;
}

.modal-title-group { display: flex; flex-direction: column; gap: 5px; }

.modal-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-title {
  font-size: 17px;
  font-weight: 500;
  color: #1e293b;
}

.modal-sub {
  font-size: 12px;
  color: #94a3b8;
}

.f-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
  flex-shrink: 0;
}
.f-badge.pending  { background: #fefce8; color: #92400e; }
.f-badge.approved { background: #f0fdf4; color: #065f46; }
.f-badge.rejected { background: #fef2f2; color: #991b1b; }

.btn-icon-close {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 0.5px solid #e2e8f0;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
  flex-shrink: 0;
  transition: background 0.12s;
}
.btn-icon-close:hover { background: #f1f5f9; }

/* BODY */
.modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.section-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #94a3b8;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-tag {
  font-size: 10px;
  font-weight: 500;
  background: #f1f5f9;
  color: #64748b;
  padding: 2px 8px;
  border-radius: 20px;
  letter-spacing: 0;
  text-transform: none;
}

/* INFO GRID */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.info-item {
  background: #f8fafc;
  border-radius: 8px;
  padding: 10px 14px;
}
.info-item-full { grid-column: span 2; }

.info-item-label {
  font-size: 11px;
  color: #94a3b8;
  margin-bottom: 3px;
}

.info-item-val {
  font-size: 13px;
  color: #1e293b;
  font-weight: 500;
}

.info-desc {
  font-weight: 400;
  font-size: 12px;
  line-height: 1.55;
  color: #475569;
}

/* ADMIN */
.admin-row {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  border-radius: 10px;
  padding: 12px 14px;
}

.admin-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #E6F1FB;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 500;
  color: #0C447C;
  flex-shrink: 0;
}

.admin-name {
  font-size: 13px;
  font-weight: 500;
  color: #1e293b;
}

.admin-contacts {
  display: flex;
  gap: 14px;
  margin-top: 4px;
  flex-wrap: wrap;
}

.admin-contact {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #64748b;
}

/* DOCS */
.doc-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.doc-img-wrap {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  border: 0.5px solid #e2e8f0;
  aspect-ratio: 4/3;
  cursor: pointer;
  background: #f8fafc;
}

.doc-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.2s;
}
.doc-img-wrap:hover img { transform: scale(1.03); }

.doc-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.doc-img-wrap:hover .doc-overlay { background: rgba(0,0,0,0.3); }

.doc-placeholder {
  grid-column: span 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 28px;
  border-radius: 8px;
  border: 0.5px dashed #cbd5e1;
  color: #94a3b8;
  font-size: 12px;
}

/* FOOTER */
.modal-footer {
  padding: 14px 24px;
  border-top: 0.5px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  flex-shrink: 0;
}

.btn-close-modal {
  padding: 8px 16px;
  border-radius: 8px;
  border: 0.5px solid #cbd5e1;
  background: transparent;
  font-size: 13px;
  font-weight: 500;
  color: #1e293b;
  cursor: pointer;
  transition: background 0.12s;
}
.btn-close-modal:hover { background: #f1f5f9; }

.btn-reject-modal {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 8px;
  border: none;
  background: #fef2f2;
  color: #991b1b;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.12s;
}
.btn-reject-modal:hover { background: #fee2e2; }

.btn-approve-modal {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border-radius: 8px;
  border: none;
  background: #0F2D52;
  color: #fff;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.12s;
}
.btn-approve-modal:hover { background: #185FA5; }

/* LIGHTBOX */
.lightbox {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.88);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.lightbox img {
  max-width: 88%;
  max-height: 88vh;
  border-radius: 10px;
  object-fit: contain;
}

.lightbox-close {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(255,255,255,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #fff;
  transition: background 0.12s;
}
.lightbox-close:hover { background: rgba(255,255,255,0.25); }
</style>