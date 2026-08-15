<template>
  <div class="page">

    <PageHeader title="Campaigns">
      <template #default>{{ campaigns.length }} total campaigns on the platform</template>
      <template #actions>
        <select v-model="filterStatus" class="filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="paused">Paused</option>
        </select>
      </template>
    </PageHeader>

    <!-- Loading -->
    <EmptyState v-if="isLoading" loading loading-message="Loading campaigns..." />

    <!-- Empty -->
    <EmptyState v-else-if="filtered.length === 0" message="No campaigns found." />

    <div v-else class="campaigns-grid">

      <div class="campaign-card" v-for="c in filtered" :key="c.id">

        <div class="campaign-header">
          <div class="campaign-icon">
            <img v-if="c.cover_photo_url" :src="c.cover_photo_url" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:12px;" />
            <span v-else>📢</span>
          </div>

          <span class="camp-status" :class="c.status">
            {{ c.status }}
          </span>
        </div>

        <h3>{{ c.title }}</h3>

        <p class="camp-by">
          by <strong>{{ c.foundation?.name || 'Unknown Foundation' }}</strong>
        </p>

        <p class="camp-desc">
          {{ c.description }}
        </p>

        <!-- PROGRESS -->
        <div class="camp-progress">
          <div class="prog-top">
            <span class="prog-raised">
              ₱{{ formatMoney(c.raised) }}
            </span>

            <span class="prog-goal">
              of ₱{{ formatMoney(c.goal) }}
            </span>
          </div>

          <div class="prog-bar">
            <div
              class="prog-fill"
              :style="{
                width: getPercent(c) + '%',
                background: '#059669'
              }"
            ></div>
          </div>

          <div class="prog-pct">
            {{ getPercent(c) }}% funded
          </div>
        </div>

        <!-- META -->
        <div class="camp-meta">
          <div class="meta-item">
            👥 {{ c.donors_count || 0 }} donors
          </div>

          <div class="meta-item">
            📅 Ends {{ c.end_date || 'N/A' }}
          </div>
        </div>

        <!-- ACTIONS -->
        <div class="camp-actions">
          <button class="btn-view" @click="openView(c)">View Details</button>
        </div>

      </div>

    </div>

    <!-- VIEW MODAL -->
    <BaseModal
      :model-value="!!viewCampaign"
      @update:model-value="v => !v && (viewCampaign = null)"
      width="580px"
      max-height="88vh"
    >
      <template #header>
        <div class="modal-header">
          <div class="modal-cover">
            <img v-if="viewCampaign?.cover_photo_url" :src="viewCampaign.cover_photo_url" alt="" />
            <span v-else>📢</span>
          </div>
          <div class="modal-heading">
            <h3>{{ viewCampaign?.title }}</h3>
            <p class="modal-by">by {{ viewCampaign?.foundation?.name || 'Unknown Foundation' }}</p>
          </div>
          <button class="btn-icon-close" @click="viewCampaign = null">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </template>

      <template v-if="viewCampaign">
        <div class="modal-grid">
          <div class="mg-item">
            <span class="mg-label">Status</span>
            <span class="vm-status" :class="viewCampaign.status"><span class="dot"></span>{{ viewCampaign.status }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">Type</span>
            <span>{{ viewCampaign.type || '—' }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">Start Date</span>
            <span>{{ viewCampaign.start_date || '—' }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">End Date</span>
            <span>{{ viewCampaign.end_date || '—' }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">Donors</span>
            <span>{{ viewCampaign.donors_count || 0 }}</span>
          </div>
          <div class="mg-item">
            <span class="mg-label">Delivery Methods</span>
            <span>{{ (viewCampaign.accepted_delivery_methods || []).join(', ') || '—' }}</span>
          </div>
          <div class="mg-item mg-item-full" v-if="viewCampaign.status === 'paused' && viewCampaign.pause_reason">
            <span class="mg-label">Pause Reason</span>
            <span>{{ viewCampaign.pause_reason }}</span>
          </div>
          <div class="mg-item mg-item-full">
            <span class="mg-label">Description</span>
            <span>{{ viewCampaign.description || '—' }}</span>
          </div>
        </div>

        <div class="modal-progress">
          <div class="prog-top">
            <span class="prog-raised">₱{{ formatMoney(viewCampaign.raised) }}</span>
            <span class="prog-goal">of ₱{{ formatMoney(viewCampaign.goal) }}</span>
          </div>
          <div class="prog-bar">
            <div class="prog-fill" :style="{ width: getPercent(viewCampaign) + '%', background: '#059669' }"></div>
          </div>
          <div class="prog-pct">{{ getPercent(viewCampaign) }}% funded</div>
        </div>

        <div v-if="viewCampaign.photos?.length" class="modal-photos">
          <span class="mg-label">Campaign Photos</span>
          <div class="photos-grid">
            <img v-for="p in viewCampaign.photos" :key="p.id" :src="p.url" :alt="p.caption || ''" />
          </div>
        </div>
      </template>

      <template #footer>
        <button class="btn-cancel" @click="viewCampaign = null">Close</button>
      </template>
    </BaseModal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useSearch } from "../composable/usesearch"
import PageHeader from '../components/superadmin/pageheader.vue'
import EmptyState from '../components/superadmin/emptystate.vue'
import BaseModal from '../components/superadmin/basemodal.vue'

const { searchQuery } = useSearch()
const filterStatus = ref('')
const campaigns = ref([])
const isLoading = ref(false)
const viewCampaign = ref(null)

/* FETCH REAL DATA */
async function fetchCampaigns() {
  isLoading.value = true
  try {
    const res = await api.get('/admin/campaigns')
    campaigns.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error('Failed to load campaigns:', err)
    campaigns.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchCampaigns)

/* FILTER */
const filtered = computed(() => {
  return campaigns.value.filter(c => {
    const matchSearch =
      c.title?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.foundation?.name?.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchStatus =
      filterStatus.value === '' || c.status === filterStatus.value

    return matchSearch && matchStatus
  })
})

/* VIEW */
function openView(c) { viewCampaign.value = { ...c } }

/* HELPERS */
function getPercent(c) {
  if (!c.goal || !c.raised) return 0
  return Math.min(Math.round((c.raised / c.goal) * 100), 100)
}

function formatMoney(value) {
  if (!value) return '0'
  return Number(value).toLocaleString()
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.filter-select { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; background: white; color: #0F2D52; outline: none; cursor: pointer; }

.campaigns-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }

.campaign-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); transition: transform 0.2s, box-shadow 0.2s; }
.campaign-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(15,45,82,0.1); }

.campaign-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }

.campaign-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f1f5f9; }

.camp-status { font-size: 0.73rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.camp-status.active { background: #f0fdf4; color: #059669; }
.camp-status.completed { background: #f1f5f9; color: #64748b; }
.camp-status.paused { background: #fefce8; color: #ca8a04; }

.campaign-card h3 { font-size: 0.92rem; color: #0F2D52; margin: 0 0 4px; line-height: 1.3; }
.camp-by { font-size: 0.78rem; color: #94a3b8; margin: 0 0 8px; }
.camp-by strong { color: #475569; }
.camp-desc { font-size: 0.8rem; color: #64748b; line-height: 1.5; margin: 0 0 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.camp-progress { margin-bottom: 14px; }
.prog-top { display: flex; align-items: baseline; gap: 6px; margin-bottom: 7px; }
.prog-raised { font-size: 1.05rem; font-weight: 800; color: #0F2D52; }
.prog-goal { font-size: 0.78rem; color: #94a3b8; }
.prog-bar { height: 7px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 99px; transition: width 1s ease; }
.prog-pct { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }

.camp-meta { display: flex; gap: 16px; margin-bottom: 14px; border-top: 1px solid #f8fafc; padding-top: 12px; }
.meta-item { display: flex; align-items: center; gap: 5px; font-size: 0.78rem; color: #64748b; }

.camp-actions { display: flex; gap: 7px; }
.btn-view { flex: 1; padding: 8px; border: 1px solid #e2e8f0; background: white; color: #0F2D52; font-size: 0.8rem; font-weight: 600; border-radius: 9px; cursor: pointer; transition: all 0.2s; }
.btn-view:hover { background: #0F2D52; color: white; border-color: #0F2D52; }

/* ══════════════════════════════
   VIEW DETAILS MODAL (elegant)
══════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap');

.modal-header {
  display: flex; align-items: center; gap: 16px; padding: 26px 28px;
  background: linear-gradient(135deg, #0A2242, #0F2D52 60%, #163A66);
  position: relative; overflow: hidden;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}
.modal-header::after {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(220px 120px at 90% 0%, rgba(173,127,39,0.35), transparent 70%);
}
.modal-cover {
  width: 56px; height: 56px; min-width: 56px; border-radius: 14px;
  background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; overflow: hidden; position: relative;
  box-shadow: inset 0 0 0 1.5px rgba(255,255,255,0.25);
}
.modal-cover img { width: 100%; height: 100%; object-fit: cover; }
.modal-heading { position: relative; min-width: 0; }
.modal-header h3 {
  font-family: 'Fraunces', Georgia, serif; font-weight: 600; font-size: 1.15rem; color: white;
  margin: 0 0 4px; line-height: 1.3; letter-spacing: -0.01em;
}
.modal-by { font-size: 0.8rem; color: rgba(255,255,255,0.65); margin: 0; }
.btn-icon-close {
  margin-left: auto; width: 30px; height: 30px; border-radius: 9px; border: 1px solid rgba(255,255,255,0.25);
  background: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: white; position: relative; flex-shrink: 0; transition: background 0.15s;
}
.btn-icon-close:hover { background: rgba(255,255,255,0.18); }

.modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: #F8FAFC; border-radius: 14px; padding: 18px; margin-bottom: 20px; font-family: 'Inter', sans-serif; }
.mg-item { display: flex; flex-direction: column; gap: 5px; }
.mg-item-full { grid-column: span 2; }
.mg-label { font-size: 0.68rem; color: #93A0B4; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
.mg-item span:not(.mg-label) { font-size: 0.87rem; color: #0F2D52; font-weight: 500; }

.vm-status {
  display: inline-flex; align-items: center; gap: 6px; font-size: 0.73rem; font-weight: 600;
  padding: 4px 11px 4px 9px; border-radius: 20px; text-transform: capitalize; align-self: flex-start;
  border: 1px solid transparent;
}
.vm-status .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.vm-status.active    { background: #F0FBF5; color: #0F7A47; border-color: #D6F0E1; }
.vm-status.active .dot    { background: #17A863; }
.vm-status.completed { background: #F1F5F9; color: #475569; border-color: #E2E8F0; }
.vm-status.completed .dot { background: #64748B; }
.vm-status.paused    { background: #F6EDD9; color: #8A6416; border-color: #ECD9AF; }
.vm-status.paused .dot    { background: #AD7F27; }

.modal-progress {
  margin-bottom: 20px; background: #FBFCFE; border: 1px solid #E7EAF1; border-radius: 14px; padding: 16px 18px;
  font-family: 'Inter', sans-serif;
}
.modal-progress .prog-top { display: flex; align-items: baseline; gap: 6px; margin-bottom: 8px; }
.modal-progress .prog-raised { font-family: 'Fraunces', Georgia, serif; font-size: 1.15rem; font-weight: 700; color: #0F2D52; }
.modal-progress .prog-goal { font-size: 0.78rem; color: #93A0B4; }
.modal-progress .prog-bar { height: 7px; background: #EDF0F5; border-radius: 99px; overflow: hidden; }
.modal-progress .prog-fill { height: 100%; border-radius: 99px; transition: width 1s ease; background: linear-gradient(90deg, #AD7F27, #C9A24E) !important; }
.modal-progress .prog-pct { font-size: 0.76rem; color: #4B5A72; margin-top: 6px; }

.modal-photos { margin-bottom: 4px; font-family: 'Inter', sans-serif; }
.modal-photos .photos-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
.modal-photos img {
  width: 100%; height: 84px; object-fit: cover; border-radius: 10px; cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s; border: 1px solid #E7EAF1;
}
.modal-photos img:hover { transform: scale(1.03); box-shadow: 0 8px 18px -8px rgba(10,34,66,0.35); }

.btn-cancel {
  padding: 10px 20px; border: 1px solid #E7EAF1; background: white; color: #4B5A72;
  border-radius: 11px; font-size: 0.87rem; font-weight: 600; cursor: pointer; transition: border-color 0.15s;
  font-family: 'Inter', sans-serif;
}
.btn-cancel:hover { border-color: #AD7F27; color: #0F2D52; }
</style>