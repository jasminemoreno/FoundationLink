<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">

      <div class="modal-head">
        <h3>Donate to {{ campaign?.title }}</h3>
        <button class="btn-close" @click="$emit('close')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <!-- CAMPAIGN INFO -->
      <div class="camp-info">
        <img v-if="campaign.cover_photo" :src="getImage(campaign.cover_photo)" class="camp-thumb" alt="" />
        <div>
          <div class="camp-name">{{ campaign.title }}</div>
          <div class="camp-foundation">{{ campaign.foundation?.name }}</div>
        </div>
      </div>

      <!-- TYPE SELECTOR — only for 'both' campaigns -->
      <div class="type-select" v-if="campaign.type === 'both'">
        <button class="type-btn" :class="{ active: form.type === 'monetary' }" @click="form.type = 'monetary'">
          💰 Monetary
        </button>
        <button class="type-btn" :class="{ active: form.type === 'item' }" @click="form.type = 'item'">
          📦 Item
        </button>
        <button class="type-btn" :class="{ active: form.type === 'both' }" @click="form.type = 'both'">
          💰📦 Both
        </button>
      </div>

      <!-- MONETARY FORM -->
      <div v-if="form.type === 'monetary' || form.type === 'both'" class="form-section">
        <div class="section-label" v-if="form.type === 'both'">💰 Monetary Donation</div>
        <div class="quick-amounts">
          <button
            v-for="amt in quickAmounts" :key="amt"
            class="quick-btn"
            :class="{ active: form.amount == amt }"
            @click="form.amount = amt"
          >₱{{ amt.toLocaleString() }}</button>
        </div>
        <div class="form-group">
          <label>Custom Amount (₱)</label>
          <input v-model="form.amount" type="number" placeholder="Enter amount" min="1" />
        </div>

        <!-- PAYMENT METHOD SELECTOR -->
        <div class="form-group">
          <label>Payment Method <span class="req">*</span></label>

          <div v-if="!campaign.accepted_payment_methods?.length" class="payment-empty-hint">
            This foundation hasn't set up a payment method yet. Please contact them directly.
          </div>

          <div v-else class="payment-options">
            <button
              v-for="pm in campaign.accepted_payment_methods" :key="pm.payment_method_id || pm.id"
              type="button"
              class="payment-btn"
              :class="{ active: form.payment_method_id === (pm.payment_method_id || pm.id) }"
              @click="form.payment_method_id = pm.payment_method_id || pm.id"
            >
              <div class="payment-icon">{{ pm.icon || '💳' }}</div>
              <div class="payment-info">
                <div class="payment-title">{{ pm.name }}</div>
              </div>
              <div class="payment-check" v-if="form.payment_method_id === (pm.payment_method_id || pm.id)">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </div>
            </button>
          </div>

          <!-- Show selected payment method's account details -->
          <div v-if="selectedPaymentAccount" class="account-box">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <div>
              <div class="account-label">Send payment to:</div>
              <div class="account-value">
                {{ selectedPaymentAccount.account_name }} — {{ selectedPaymentAccount.account_number }}
              </div>
            </div>
          </div>

          <!-- PROOF PHOTO UPLOAD for monetary -->
          <div v-if="form.payment_method_id" class="form-group" style="margin-top: 12px;">
            <label>Proof of Payment <span class="req">*</span></label>
            <div class="upload-box" @click="$refs.proofInput.click()">
              <img v-if="proofPreview" :src="proofPreview" class="upload-preview" alt="proof" />
              <div v-else class="upload-placeholder">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21 15 16 10 5 21"/>
                </svg>
                <span>Upload screenshot of payment receipt</span>
              </div>
            </div>
            <input ref="proofInput" type="file" accept="image/*" style="display:none" @change="onProofFile" />
          </div>
        </div>
      </div>

      <!-- DIVIDER -->
      <div v-if="form.type === 'both'" class="section-divider" />

      <!-- ITEM FORM -->
      <div v-if="form.type === 'item' || form.type === 'both'" class="form-section">
        <div class="section-label" v-if="form.type === 'both'">📦 Item Donation</div>
        <div class="form-group">
          <label>Item Name <span class="req">*</span></label>
          <input v-model="form.item_name" type="text" placeholder="e.g. Canned goods, Clothes" />
        </div>
        <div class="form-group">
          <label>Quantity <span class="req">*</span></label>
          <input v-model="form.item_quantity" type="number" placeholder="e.g. 10" min="1" />
        </div>
        <div class="form-group">
          <label>Description</label>
          <input v-model="form.item_description" type="text" placeholder="Brief description (optional)" />
        </div>

        <!-- ITEM PHOTO UPLOAD -->
        <div class="form-group">
          <label>Photo of Item(s) <span class="req">*</span></label>
          <div class="upload-box" @click="$refs.itemPhotoInput.click()">
            <img v-if="itemPhotoPreview" :src="itemPhotoPreview" class="upload-preview" alt="item" />
            <div v-else class="upload-placeholder">
              <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Upload a photo of the item(s)</span>
            </div>
          </div>
          <input ref="itemPhotoInput" type="file" accept="image/*" style="display:none" @change="onItemPhotoFile" />
        </div>
      </div>

      <!-- DELIVERY METHOD — only when donating items -->
      <div v-if="form.type === 'item' || form.type === 'both'" class="form-section">
        <div class="section-label">🚚 Item Delivery</div>

        <div class="delivery-options">
          <button
            v-if="allowsDelivery('dropoff')"
            type="button"
            class="delivery-btn"
            :class="{ active: form.delivery_method === 'dropoff' }"
            @click="form.delivery_method = 'dropoff'"
          >
            <div class="delivery-icon">🏢</div>
            <div class="delivery-info">
              <div class="delivery-title">Drop Off</div>
              <div class="delivery-desc">You personally bring items to the foundation</div>
            </div>
            <div class="delivery-check" v-if="form.delivery_method === 'dropoff'">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
          </button>

          <button
            v-if="allowsDelivery('pickup')"
            type="button"
            class="delivery-btn"
            :class="{ active: form.delivery_method === 'pickup' }"
            @click="form.delivery_method = 'pickup'"
          >
            <div class="delivery-icon">🏠</div>
            <div class="delivery-info">
              <div class="delivery-title">Pick Up</div>
              <div class="delivery-desc">Foundation will collect items from your location</div>
            </div>
            <div class="delivery-check" v-if="form.delivery_method === 'pickup'">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
          </button>
        </div>

        <!-- Drop Off: show foundation address -->
        <div v-if="form.delivery_method === 'dropoff' && foundationAddress" class="address-box">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
          <div>
            <div class="address-label">Drop off at:</div>
            <div class="address-value">{{ foundationAddress }}</div>
          </div>
        </div>

        <!-- Pick Up: ask for donor's address -->
        <div v-if="form.delivery_method === 'pickup'" class="form-group" style="margin-top: 12px;">
          <label>Your Pick-up Address <span class="req">*</span></label>
          <input
            v-model="form.delivery_address"
            type="text"
            placeholder="Enter your full address for pick-up"
          />
        </div>
      </div>

      <!-- NOTES -->
      <div class="form-group">
        <label>Message (optional)</label>
        <textarea v-model="form.notes" placeholder="Add a note to the foundation..."></textarea>
      </div>

      <p v-if="errorMsg" class="error-text">{{ errorMsg }}</p>

      <div class="modal-actions">
        <button class="btn-cancel" @click="$emit('close')">Cancel</button>
        <button class="btn-donate" @click="submit" :disabled="saving">
          <span v-if="!saving">Submit Donation</span>
          <span v-else class="spinner-sm" />
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

const props = defineProps({ campaign: Object })
const emit  = defineEmits(['close', 'donated'])

const saving   = ref(false)
const errorMsg = ref('')
const quickAmounts = [100, 250, 500, 1000, 2500, 5000]

const proofPreview     = ref(null)
const itemPhotoPreview = ref(null)

const form = reactive({
  type:               props.campaign.type === 'item' ? 'item' : 'monetary',
  amount:             '',
  payment_method_id:  null,
  proof_photo:        null,
  item_name:          '',
  item_quantity:      '',
  item_description:   '',
  item_photo:         null,
  delivery_method:    '',
  delivery_address:   '',
  notes:              '',
})

// Find full account details for the selected payment method
const selectedPaymentAccount = computed(() => {
  if (!form.payment_method_id || !props.campaign.accepted_payment_methods) return null
  return props.campaign.accepted_payment_methods.find(
    pm => (pm.payment_method_id || pm.id) === form.payment_method_id
  )
})

// Check if campaign allows a specific delivery method
function allowsDelivery(method) {
  const methods = props.campaign.accepted_delivery_methods
  if (!methods || methods.length === 0) return true
  return methods.includes(method)
}

// Build the foundation's address for drop-off info
const foundationAddress = computed(() => {
  const f = props.campaign.foundation
  if (!f) return ''
  return [f.street, f.barangay, f.city_municipality, f.province]
    .filter(Boolean)
    .join(', ')
})

function onProofFile(e) {
  const f = e.target.files[0]
  if (!f) return
  form.proof_photo = f
  proofPreview.value = URL.createObjectURL(f)
}

function onItemPhotoFile(e) {
  const f = e.target.files[0]
  if (!f) return
  form.item_photo = f
  itemPhotoPreview.value = URL.createObjectURL(f)
}

async function submit() {
  errorMsg.value = ''

  if ((form.type === 'monetary' || form.type === 'both') && !form.amount) {
    errorMsg.value = 'Please enter a monetary amount.'; return
  }
  if ((form.type === 'monetary' || form.type === 'both') && !form.payment_method_id) {
    errorMsg.value = 'Please select a payment method.'; return
  }
  if ((form.type === 'monetary' || form.type === 'both') && !form.proof_photo) {
    errorMsg.value = 'Please upload proof of payment.'; return
  }
  if ((form.type === 'item' || form.type === 'both') && !form.item_name) {
    errorMsg.value = 'Please enter an item name.'; return
  }
  if ((form.type === 'item' || form.type === 'both') && !form.item_quantity) {
    errorMsg.value = 'Please enter a quantity.'; return
  }
  if ((form.type === 'item' || form.type === 'both') && !form.item_photo) {
    errorMsg.value = 'Please upload a photo of the item(s).'; return
  }
  if ((form.type === 'item' || form.type === 'both') && !form.delivery_method) {
    errorMsg.value = 'Please choose a delivery method for your items.'; return
  }
  if (form.delivery_method === 'pickup' && !form.delivery_address.trim()) {
    errorMsg.value = 'Please enter your pick-up address.'; return
  }

  saving.value = true
  try {
    const fd = new FormData()
    fd.append('campaign_id', props.campaign.id)
    fd.append('type', form.type)

    if (form.type === 'monetary' || form.type === 'both') {
      fd.append('amount', form.amount)
      fd.append('payment_method_id', form.payment_method_id)
      if (form.proof_photo) fd.append('proof_photo', form.proof_photo)
    }

    if (form.type === 'item' || form.type === 'both') {
      fd.append('item_name', form.item_name)
      fd.append('item_quantity', form.item_quantity)
      if (form.item_description) fd.append('item_description', form.item_description)
      if (form.item_photo) fd.append('item_photo', form.item_photo)
      fd.append('delivery_method', form.delivery_method)
      fd.append(
        'delivery_address',
        form.delivery_method === 'pickup' ? form.delivery_address : (foundationAddress.value || '')
      )
    }

    if (form.notes) fd.append('notes', form.notes)

    await api.post('/donor/donate', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    emit('donated')
  } catch (err) {
    const errors = err.response?.data?.errors
    errorMsg.value = errors
      ? Object.values(errors).flat().join(' · ')
      : err.response?.data?.message || 'Failed to submit donation.'
  } finally {
    saving.value = false
  }
}

function getImage(path) { return path ? `http://127.0.0.1:8000/storage/${path}` : '' }
</script>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 200; padding: 24px; }
.modal { background: white; border-radius: 20px; padding: 28px; width: 460px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }

.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
.modal-head h3 { font-size: 1rem; font-weight: 800; color: #0F2D52; margin: 0; }
.btn-close { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: transparent; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; }
.btn-close:hover { background: #f1f5f9; }

.camp-info { display: flex; align-items: center; gap: 12px; background: #f0fdf4; border-radius: 12px; padding: 12px; margin-bottom: 18px; }
.camp-thumb { width: 56px; height: 48px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
.camp-name  { font-size: 0.9rem; font-weight: 700; color: #0F2D52; }
.camp-foundation { font-size: 0.75rem; color: #1a8a52; font-weight: 600; margin-top: 2px; }

.type-select { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 18px; }
.type-btn { padding: 10px 6px; border: 2px solid #e2e8f0; background: white; border-radius: 10px; font-size: 0.82rem; font-weight: 700; color: #64748b; cursor: pointer; transition: all 0.15s; }
.type-btn:hover  { border-color: #1a8a52; color: #1a8a52; }
.type-btn.active { border-color: #1a8a52; background: #f0fdf4; color: #1a8a52; }

.section-label { font-size: 0.78rem; font-weight: 800; color: #1a8a52; background: #f0fdf4; padding: 6px 10px; border-radius: 8px; display: inline-block; margin-bottom: 12px; }
.section-divider { border: none; border-top: 1.5px dashed #e2e8f0; margin: 4px 0 16px; }

.form-section { margin-bottom: 16px; }

.quick-amounts { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 14px; }
.quick-btn { padding: 10px 8px; border: 2px solid #e2e8f0; background: white; border-radius: 10px; font-size: 0.83rem; font-weight: 700; color: #64748b; cursor: pointer; transition: all 0.15s; }
.quick-btn:hover  { border-color: #1a8a52; color: #1a8a52; }
.quick-btn.active { border-color: #1a8a52; background: #1a8a52; color: white; }

.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 700; color: #475569; margin-bottom: 6px; }
.req { color: #dc2626; }
.form-group input, .form-group textarea {
  width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s;
}
.form-group input:focus, .form-group textarea:focus { border-color: #1a8a52; box-shadow: 0 0 0 3px rgba(26,138,82,0.1); }
.form-group textarea { height: 80px; resize: none; }

/* PAYMENT METHOD */
.payment-options { display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; }
.payment-btn {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 12px; border: 2px solid #e2e8f0;
  background: white; border-radius: 10px;
  cursor: pointer; transition: all 0.15s; text-align: left; width: 100%;
}
.payment-btn:hover  { border-color: #1a8a52; }
.payment-btn.active { border-color: #1a8a52; background: #f0fdf4; }
.payment-icon { font-size: 1.25rem; flex-shrink: 0; }
.payment-info { flex: 1; }
.payment-title { font-size: 0.85rem; font-weight: 700; color: #0F2D52; }
.payment-check {
  width: 20px; height: 20px; border-radius: 50%;
  background: #1a8a52; color: white;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.payment-empty-hint {
  font-size: 0.8rem; color: #92400e;
  background: #fefce8; border: 1px solid #fde68a;
  border-radius: 10px; padding: 10px 14px; line-height: 1.5;
}

.account-box {
  display: flex; align-items: flex-start; gap: 8px;
  background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 10px; padding: 12px 14px;
  font-size: 0.82rem; color: #475569;
  margin-top: 6px;
}
.account-label { font-size: 0.72rem; color: #1a8a52; font-weight: 700; margin-bottom: 2px; }
.account-value { font-weight: 600; color: #0F2D52; }

/* UPLOAD */
.upload-box { border: 2px dashed #e2e8f0; border-radius: 12px; overflow: hidden; cursor: pointer; transition: border-color 0.2s; min-height: 70px; }
.upload-box:hover { border-color: #1a8a52; }
.upload-preview { width: 100%; height: 140px; object-fit: cover; display: block; }
.upload-placeholder { height: 90px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; color: #94a3b8; font-size: 0.8rem; text-align: center; padding: 0 12px; }

/* DELIVERY */
.delivery-options { display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; }
.delivery-btn {
  display: flex; align-items: center; gap: 12px;
  padding: 14px; border: 2px solid #e2e8f0;
  background: white; border-radius: 12px;
  cursor: pointer; transition: all 0.15s;
  text-align: left; width: 100%;
}
.delivery-btn:hover  { border-color: #1a8a52; }
.delivery-btn.active { border-color: #1a8a52; background: #f0fdf4; }

.delivery-icon { font-size: 1.4rem; flex-shrink: 0; line-height: 1; }
.delivery-info { flex: 1; }
.delivery-title { font-size: 0.88rem; font-weight: 800; color: #0F2D52; margin-bottom: 2px; }
.delivery-desc  { font-size: 0.75rem; color: #64748b; line-height: 1.4; }

.delivery-check {
  width: 22px; height: 22px; border-radius: 50%;
  background: #1a8a52; color: white;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.address-box {
  display: flex; align-items: flex-start; gap: 8px;
  background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 10px; padding: 12px 14px;
  font-size: 0.82rem; color: #475569;
  margin-top: 4px;
}
.address-label { font-size: 0.72rem; color: #1a8a52; font-weight: 700; margin-bottom: 2px; }
.address-value { font-weight: 600; color: #0F2D52; }

.error-text { font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 12px; }

.modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.btn-cancel { flex: 1; padding: 12px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; }
.btn-donate { flex: 2; padding: 12px; border: none; background: #1a8a52; color: white; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 140px; }
.btn-donate:hover:not(:disabled) { background: #157042; }
.btn-donate:disabled { opacity: 0.65; cursor: not-allowed; }
.spinner-sm { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>