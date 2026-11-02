<template>
  <div class="page">

    <!-- HEADER -->
    <PageHeader title="Donations" subtitle="Track and manage donations to your campaigns" />

    <!-- PENDING VERIFICATION BANNER -->
    <PendingVerificationBanner v-if="!isVerified && !checkingStatus">
      Donation data will appear here once your foundation has been verified by the super admin.
    </PendingVerificationBanner>

    <template v-else>

    <!-- STATS -->
    <div class="stats-row">
      <StatCard
        v-for="s in stats" :key="s.label"
        :icon="s.icon" :value="s.value" :label="s.label"
        :bg="s.bg" :col="s.col"
      />
    </div>

    <!-- FILTERS -->
    <div class="filters">
      <div class="filter-left">
        <!-- type tabs -->
        <div class="tabs">
          <button
            v-for="t in typeTabs" :key="t.key"
            class="tab-btn" :class="{ active: activeType === t.key }"
            @click="activeType = t.key"
          >
            {{ t.label }}
            <span class="tab-count">{{ typeCount(t.key) }}</span>
          </button>
        </div>

        <!-- status filter -->
        <select v-model="filterStatus" class="filter-select">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="received">Received</option>
          <option value="cancelled">Cancelled</option>
        </select>

        <!-- campaign filter -->
        <select v-model="filterCampaign" class="filter-select">
          <option value="">All Campaigns</option>
          <option v-for="c in campaignOptions" :key="c.id" :value="c.id">
            {{ c.title }}
          </option>
        </select>
      </div>

      <div class="search-box">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="search" placeholder="Search donor or campaign..." />
      </div>
    </div>

    <!-- LOADING -->
    <EmptyState v-if="isLoading" loading message="Loading donations..." />

    <!-- EMPTY -->
    <EmptyState v-else-if="filtered.length === 0" icon="🎁" message="No donations found." />

    <!-- TABLE -->
    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>Donor</th>
            <th>Campaign</th>
            <th>Type</th>
            <th>Details</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in filtered" :key="d.id">

            <!-- DONOR -->
            <td>
              <div class="donor-cell">
                <AvatarInitials :initials="d.donor.initials" :seed="d.donor.id" :size="36" :src="d.donor.profile_photo || ''" />
                <div>
                  <strong>{{ d.donor.name }}</strong>
                  <small>{{ d.donor.email }}</small>
                </div>
              </div>
            </td>

            <!-- CAMPAIGN -->
            <td>
              <span class="campaign-name">{{ d.campaign.title }}</span>
            </td>

            <!-- TYPE -->
            <td>
              <Badge :label="typeLabel(d.type)" :tone="d.type === 'monetary' ? 'success' : 'info'" />
            </td>

            <!-- DETAILS -->
            <td>
              <div v-if="d.type === 'monetary'" class="amount">
                ₱{{ formatMoney(d.amount) }}
              </div>
              <div v-else class="item-details">
                <span class="item-name">{{ d.item_name }}</span>
                <span class="item-qty">x{{ d.item_quantity }}</span>
              </div>
            </td>

            <!-- DATE -->
            <td class="date-cell">{{ formatDate(d.donated_at) }}</td>

            <!-- STATUS -->
            <td>
              <Badge :label="statusLabel(d.status)" :tone="toneForDonationStatus(d.status)" />
              <!-- pending pick-ups: what still needs doing -->
              <template v-if="d.status === 'pending' && d.type === 'item' && d.delivery_method === 'pickup'">
                <span v-if="!d.pickup_schedule" class="sched-hint need">📅 Set pick-up day</span>
                <span v-else-if="d.pickup_schedule.request" class="sched-hint warn">⚠ Donor asked for another day</span>
                <span v-else class="sched-hint">
                  📅 {{ d.pickup_schedule.label.split(' · ')[0] }}{{ d.pickup_schedule.confirmed ? ' · ✓ confirmed' : '' }}
                </span>
              </template>
            </td>

            <!-- ACTIONS -->
            <td>
              <div class="actions">
                <button class="action-btn view" title="View" @click="openView(d)">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>

                <button
                  v-if="d.status === 'pending'"
                  class="action-btn receive"
                  title="Mark as Received"
                  @click="askReceive(d)"
                >
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </button>

                <button
                  v-if="d.status === 'pending'"
                  class="action-btn cancel"
                  title="Cancel"
                  @click="cancelDonation(d)"
                >
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                  </svg>
                </button>
              </div>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    </template>

    <!-- VIEW MODAL -->
    <FoundationModal
      v-if="viewDonation"
      title="Donation Details"
      width="540px"
      @close="viewDonation = null"
    >
      <!-- SUMMARY: what was given, its status, and what to do next -->
      <div class="dd-hero" :class="`is-${viewDonation.status}`">
        <div class="dd-hero-top">
          <span class="dd-type-icon" :class="viewDonation.type" aria-hidden="true">{{ viewDonation.type === 'monetary' ? '💰' : '📦' }}</span>
          <div class="dd-hero-main">
            <div class="dd-hero-what">
              <template v-if="viewDonation.type === 'monetary'">₱{{ formatMoney(viewDonation.amount) }}</template>
              <template v-else>{{ viewDonation.item_name }} × {{ viewDonation.item_quantity }}</template>
            </div>
            <div class="dd-hero-sub">{{ typeLabel(viewDonation.type) }} donation · {{ donationRef(viewDonation) }}</div>
          </div>
          <Badge :label="statusLabel(viewDonation.status)" :tone="toneForDonationStatus(viewDonation.status)" />
        </div>
        <div class="dd-next">
          <span class="dd-next-label">{{ nextStep(viewDonation).label }}</span>
          {{ nextStep(viewDonation).text }}
        </div>
      </div>

      <!-- DONOR -->
      <section class="dd-section">
        <h4 class="dd-title">Donor</h4>
        <div class="dd-donor">
          <AvatarInitials :initials="viewDonation.donor?.initials" :seed="viewDonation.donor?.id ?? 0" :size="44" :src="viewDonation.donor?.profile_photo || ''" />
          <div class="dd-donor-info">
            <div class="dd-donor-name">{{ viewDonation.donor?.name || 'Former donor' }}</div>
            <div class="dd-donor-contact">
              {{ viewDonation.donor?.email }}<template v-if="viewDonation.donor?.phone"> · {{ viewDonation.donor.phone }}</template>
            </div>
          </div>
          <div class="dd-donor-actions">
            <a v-if="viewDonation.donor?.email" :href="`mailto:${viewDonation.donor.email}`" class="dd-chip-btn">✉ Email</a>
            <a v-if="viewDonation.donor?.phone" :href="`tel:${viewDonation.donor.phone}`" class="dd-chip-btn">📞 Call</a>
          </div>
        </div>
      </section>

      <!-- DONATION INFO -->
      <section class="dd-section">
        <h4 class="dd-title">Donation info</h4>
        <dl class="dd-grid">
          <div class="dd-field dd-wide">
            <dt>Campaign</dt>
            <dd>{{ viewDonation.campaign?.title }}</dd>
          </div>
          <div class="dd-field">
            <dt>Date donated</dt>
            <dd>{{ formatDateTime(viewDonation.donated_at) }}</dd>
          </div>
          <div class="dd-field">
            <dt>Reference</dt>
            <dd>{{ donationRef(viewDonation) }}</dd>
          </div>
          <template v-if="viewDonation.type === 'monetary'">
            <div class="dd-field">
              <dt>Amount</dt>
              <dd class="dd-money">₱{{ formatMoney(viewDonation.amount) }}</dd>
            </div>
            <div class="dd-field">
              <dt>Paid via</dt>
              <dd>{{ viewDonation.payment_method || '—' }}</dd>
            </div>
          </template>
          <template v-else>
            <div class="dd-field">
              <dt>Item</dt>
              <dd>{{ viewDonation.item_name }}</dd>
            </div>
            <div class="dd-field">
              <dt>Quantity</dt>
              <dd>{{ viewDonation.item_quantity }}</dd>
            </div>
            <div v-if="viewDonation.item_description" class="dd-field dd-wide">
              <dt>Description</dt>
              <dd class="dd-plain">{{ viewDonation.item_description }}</dd>
            </div>
          </template>
        </dl>
      </section>

      <!-- DELIVERY (item donations) -->
      <section v-if="viewDonation.type === 'item' && viewDonation.delivery_method" class="dd-section">
        <h4 class="dd-title">Delivery</h4>
        <div class="dd-delivery">
          <span class="dd-delivery-icon" aria-hidden="true">{{ viewDonation.delivery_method === 'pickup' ? '🚚' : '🏢' }}</span>
          <div class="dd-delivery-info">
            <div class="dd-delivery-method">
              {{ viewDonation.delivery_method === 'pickup' ? 'Pick-up (you collect it)' : 'Drop-off (donor brings it)' }}
            </div>
            <template v-if="viewDonation.delivery_method === 'pickup'">
              <div v-if="viewDonation.delivery_address" class="dd-address">{{ viewDonation.delivery_address }}</div>
              <p v-if="hasPickupPoint(viewDonation)" class="pickup-point">
                📍 The donor shared their exact location<span v-if="viewDonation.pickup_accuracy"> (accurate to about {{ viewDonation.pickup_accuracy }} m)</span>.
              </p>
            </template>
            <div v-else class="dd-address muted">The donor will bring it to your foundation's address.</div>
          </div>
        </div>
        <a
          v-if="viewDonation.delivery_method === 'pickup' && pickupDirections(viewDonation)"
          :href="pickupDirections(viewDonation)"
          target="_blank"
          rel="noopener"
          class="directions-btn"
        >
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
          </svg>
          Get directions
        </a>

        <!-- PICK-UP SCHEDULE -->
        <div v-if="viewDonation.delivery_method === 'pickup'" class="schedule-box">
          <div class="schedule-head">📅 Pick-up schedule</div>
          <template v-if="viewDonation.pickup_schedule">
            <div class="schedule-when">{{ viewDonation.pickup_schedule.label }}</div>
            <p v-if="viewDonation.pickup_schedule.note" class="schedule-note">“{{ viewDonation.pickup_schedule.note }}”</p>
            <template v-if="viewDonation.status === 'pending'">
              <div v-if="viewDonation.pickup_schedule.request" class="schedule-chip warn">
                ⚠ The donor asked for another day: <strong>{{ viewDonation.pickup_schedule.request.label }}</strong>
                <span v-if="viewDonation.pickup_schedule.request.note" class="schedule-request-note">“{{ viewDonation.pickup_schedule.request.note }}”</span>
              </div>
              <div v-else-if="viewDonation.pickup_schedule.confirmed" class="schedule-chip ok">✓ The donor confirmed this day</div>
              <div v-else class="schedule-chip wait">Waiting for the donor to confirm</div>
            </template>
          </template>
          <p v-else class="schedule-empty">
            No pick-up day yet. Set one so the donor knows when you are coming.
          </p>
          <p v-if="scheduleSavedMsg" class="schedule-saved">✓ {{ scheduleSavedMsg }}</p>
          <div v-if="viewDonation.status === 'pending'" class="schedule-actions">
            <button
              v-if="viewDonation.pickup_schedule?.request"
              type="button"
              class="btn-schedule"
              @click="openSchedule(viewDonation, true)"
            >Use the donor's day</button>
            <button
              type="button"
              :class="viewDonation.pickup_schedule ? 'btn-schedule-outline' : 'btn-schedule'"
              @click="openSchedule(viewDonation)"
            >{{ viewDonation.pickup_schedule ? 'Change schedule' : 'Schedule pick-up' }}</button>
          </div>
        </div>
      </section>

      <!-- PROOF / PHOTO -->
      <section class="dd-section">
        <h4 class="dd-title">{{ viewDonation.type === 'monetary' ? 'Proof of payment' : 'Item photo' }}</h4>
        <button
          v-if="viewDonation.type === 'monetary' ? viewDonation.proof_photo : viewDonation.item_photo"
          type="button"
          class="dd-photo"
          @click="viewDonation.type === 'monetary'
            ? openImage(viewDonation.proof_photo, 'Proof of Payment')
            : openImage(viewDonation.item_photo, 'Item Photo')"
        >
          <img
            :src="getImage(viewDonation.type === 'monetary' ? viewDonation.proof_photo : viewDonation.item_photo)"
            :alt="viewDonation.type === 'monetary' ? 'Proof of payment' : 'Donated item'"
          />
          <span class="dd-photo-hint">🔍 Click to view full size</span>
        </button>
        <div v-else class="dd-empty">
          {{ viewDonation.type === 'monetary'
            ? 'No receipt was uploaded. Check your account before marking it as received.'
            : 'No photo of the item was uploaded.' }}
        </div>
      </section>

      <!-- MESSAGES -->
      <section v-if="viewDonation.notes || viewDonation.thank_you_note" class="dd-section">
        <h4 class="dd-title">Messages</h4>
        <div v-if="viewDonation.notes" class="dd-quote">
          <span class="dd-quote-who">From the donor</span>
          “{{ viewDonation.notes }}”
        </div>
        <div v-if="viewDonation.thank_you_note" class="dd-quote ours">
          <span class="dd-quote-who">Your thank-you note</span>
          “{{ viewDonation.thank_you_note }}”
        </div>
      </section>

      <!-- HISTORY -->
      <section class="dd-section">
        <h4 class="dd-title">History</h4>
        <ol class="dd-timeline">
          <li v-for="(e, i) in historyOf(viewDonation)" :key="i" :class="e.tone">
            <span class="dd-dot" aria-hidden="true" />
            <div>
              <div class="dd-ev">{{ e.text }}</div>
              <div v-if="e.when" class="dd-when">{{ e.when }}</div>
            </div>
          </li>
        </ol>
      </section>

      <template #footer>
        <button class="btn-cancel" @click="viewDonation = null">Close</button>
        <button
          v-if="viewDonation.status === 'pending'"
          class="btn-danger"
          @click="cancelDonation(viewDonation); viewDonation = null"
        >Cancel Donation</button>
        <button
          v-if="viewDonation.status === 'pending'"
          class="btn-save"
          @click="askReceive(viewDonation); viewDonation = null"
        >Mark as Received</button>
      </template>
    </FoundationModal>

    <!-- MARK AS RECEIVED: optional thank-you note to the donor -->
    <!-- SCHEDULE PICK-UP -->
    <FoundationModal v-if="scheduleTarget" title="Schedule pick-up" @close="closeSchedule">
      <p class="receive-intro">
        When will you collect
        <strong>{{ scheduleTarget.item_name }} × {{ scheduleTarget.item_quantity }}</strong>
        from <strong>{{ scheduleTarget.donor?.name || 'this donor' }}</strong>?
        They will be notified and can confirm or ask for another day.
      </p>

      <div class="receive-label">Day</div>
      <div class="sched-chips">
        <button type="button" class="sched-chip" :class="{ active: scheduleForm.date === todayStr }" @click="scheduleForm.date = todayStr">Today</button>
        <button type="button" class="sched-chip" :class="{ active: scheduleForm.date === tomorrowStr }" @click="scheduleForm.date = tomorrowStr">Tomorrow</button>
      </div>
      <CalendarPicker
        v-model="scheduleForm.date"
        class="sched-calendar"
        :min="todayStr"
        :max="maxScheduleStr"
        :marked="scheduleForm.marked"
        :marked-label="scheduleForm.markedLabel"
        accent="#2563eb"
        accent-soft="#e8f0fe"
      />

      <div class="receive-label">Time</div>
      <div class="sched-chips">
        <button
          v-for="w in scheduleWindows"
          :key="w.key"
          type="button"
          class="sched-chip"
          :class="{ active: scheduleForm.window === w.key }"
          @click="scheduleForm.window = w.key"
        >{{ w.label }}</button>
        <input
          v-if="scheduleForm.window === 'exact'"
          v-model="scheduleForm.time"
          type="time"
          class="sched-date"
          aria-label="Exact pick-up time"
        />
      </div>

      <label class="receive-label" for="schedule-note">Note to the donor <span class="optional">(optional)</span></label>
      <textarea
        id="schedule-note"
        v-model="scheduleForm.note"
        class="receive-textarea"
        maxlength="300"
        rows="2"
        placeholder="e.g. Our driver Mark will call you before arriving."
      ></textarea>
      <div class="receive-count">{{ scheduleForm.note.length }}/300</div>
      <p v-if="scheduleError" class="receive-error">{{ scheduleError }}</p>

      <template #footer>
        <button class="btn-cancel" @click="closeSchedule">Cancel</button>
        <button class="btn-save" :disabled="scheduling" @click="saveSchedule">
          {{ scheduling ? 'Saving…' : 'Save & notify donor' }}
        </button>
      </template>
    </FoundationModal>

    <FoundationModal v-if="receiveTarget" title="Mark as received" @close="receiveTarget = null">
      <p class="receive-intro">
        Confirm you received
        <strong>{{ receiveTarget.type === 'monetary' ? '₱' + formatMoney(receiveTarget.amount) : `${receiveTarget.item_name} × ${receiveTarget.item_quantity}` }}</strong>
        from <strong>{{ receiveTarget.donor?.name || 'this donor' }}</strong>.
        They will be notified.
      </p>

      <label class="receive-label" for="thank-you-note">Thank-you note <span class="optional">(optional)</span></label>
      <div class="receive-suggestions">
        <button v-for="s in thankYouSuggestions" :key="s" type="button" class="suggestion-chip" @click="thankYouNote = s">{{ s }}</button>
      </div>
      <textarea
        id="thank-you-note"
        v-model="thankYouNote"
        class="receive-textarea"
        maxlength="500"
        rows="3"
        placeholder="Write a short message to the donor…"
      ></textarea>
      <div class="receive-count">{{ thankYouNote.length }}/500</div>
      <p v-if="receiveError" class="receive-error">{{ receiveError }}</p>

      <template #footer>
        <button class="btn-cancel" @click="receiveTarget = null">Cancel</button>
        <button class="btn-save" :disabled="receiving" @click="confirmReceive">
          {{ receiving ? 'Saving…' : 'Mark as Received' }}
        </button>
      </template>
    </FoundationModal>

    <!-- IMAGE LIGHTBOX (proof of payment / item photo enlarge) -->
    <div v-if="enlargedImage" class="lightbox-overlay" @click.self="closeLightbox">
      <div class="lightbox-panel">
        <div class="lightbox-head">
          <span>{{ enlargedLabel }}</span>
          <button class="lightbox-close" @click="closeLightbox">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
        <img :src="enlargedImage" class="lightbox-img" alt="Enlarged verification photo" />
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue"
import { useRoute } from "vue-router"
import api from "@/services/api"
import PageHeader from "../components/foundation-admin/pageheader.vue"
import PendingVerificationBanner from "../components/foundation-admin/pendingverificationbanner.vue"
import StatCard from "../components/foundation-admin/statcard.vue"
import EmptyState from "../components/foundation-admin/emptystate.vue"
import AvatarInitials from "../components/foundation-admin/avatarinitials.vue"
import Badge from "../components/foundation-admin/badge.vue"
import FoundationModal from "../components/foundation-admin/basemodal.vue"
import CalendarPicker from "../components/CalendarPicker.vue"
import { mapsDirectionsUrl } from "../composable/maps.js"

/* ── PICK-UP DIRECTIONS ── exact GPS spot when the donor shared one, else the typed address */
function hasPickupPoint(d) {
  return d.pickup_latitude !== null && d.pickup_latitude !== undefined &&
    d.pickup_longitude !== null && d.pickup_longitude !== undefined
}
function pickupDirections(d) {
  return hasPickupPoint(d)
    ? mapsDirectionsUrl({ lat: d.pickup_latitude, lng: d.pickup_longitude })
    : mapsDirectionsUrl({ address: d.delivery_address })
}

const donations      = ref([])
const isLoading      = ref(false)
const search         = ref("")
const activeType     = ref("all")
const filterStatus   = ref("")
const filterCampaign = ref("")
const viewDonation   = ref(null)
const enlargedImage  = ref(null)
const enlargedLabel  = ref("")

const isVerified     = ref(false)
const checkingStatus = ref(true)

const typeTabs = [
  { key: "all",      label: "All"      },
  { key: "monetary", label: "Monetary" },
  { key: "item",     label: "Items"    },
]

/* ── FOUNDATION STATUS ── */
async function loadFoundationStatus() {
  checkingStatus.value = true
  try {
    const res = await api.get("/foundation/dashboard")
    isVerified.value = res.data?.foundation?.status === 'verified'
  } catch (err) {
    console.error("Failed to load foundation status:", err)
    isVerified.value = false
  } finally {
    checkingStatus.value = false
  }
}

/* ── FETCH ── */
async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/donations")
    donations.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error("Failed to load donations:", err)
    donations.value = []
  } finally {
    isLoading.value = false
  }
}

/* ── PICK-UP SCHEDULE ──
   Dates are Philippine calendar days (the server checks the same range). */
const route = useRoute()
const scheduleWindows = [
  { key: "morning",   label: "Morning (8 AM–12 NN)" },
  { key: "afternoon", label: "Afternoon (1–5 PM)" },
  { key: "exact",     label: "Exact time" },
]
const scheduleTarget   = ref(null)
const scheduleForm     = ref({ date: "", window: "morning", time: "", note: "", marked: "", markedLabel: "" })
const scheduling       = ref(false)
const scheduleError    = ref("")
const scheduleSavedMsg = ref("")

function manilaDay(offsetDays = 0) {
  const today = new Date().toLocaleDateString("en-CA", { timeZone: "Asia/Manila" }) // YYYY-MM-DD
  const d = new Date(`${today}T00:00:00Z`)
  d.setUTCDate(d.getUTCDate() + offsetDays)
  return d.toISOString().slice(0, 10)
}
const todayStr       = manilaDay(0)
const tomorrowStr    = manilaDay(1)
const maxScheduleStr = manilaDay(90)

// useDonorDay: start from the day the donor asked for
function openSchedule(d, useDonorDay = false) {
  const s = d.pickup_schedule
  const req = useDonorDay ? s?.request : null
  scheduleForm.value = {
    date:   req?.date || s?.date || tomorrowStr,
    window: req?.window || s?.window || "morning",
    time:   s?.time || "",
    note:   s?.note || "",
    // dot on the calendar: the day the donor asked for, else the current day
    marked:      s?.request?.date || s?.date || "",
    markedLabel: s?.request ? "Donor's requested day" : "Current pick-up day",
  }
  scheduleError.value = ""
  scheduleTarget.value = d
  viewDonation.value = null
}

function closeSchedule() {
  if (scheduling.value) return
  const d = scheduleTarget.value
  scheduleTarget.value = null
  if (d) viewDonation.value = { ...d } // back to its details
}

async function saveSchedule() {
  const f = scheduleForm.value
  scheduleError.value = ""
  if (!f.date) { scheduleError.value = "Please choose a day."; return }
  if (f.window === "exact" && !f.time) { scheduleError.value = "Please enter the exact time."; return }

  scheduling.value = true
  try {
    const d = scheduleTarget.value
    const { data } = await api.patch(`/foundation/donations/${d.id}/pickup-schedule`, {
      date: f.date,
      window: f.window,
      time: f.window === "exact" ? f.time : null,
      note: f.note.trim() || null,
    })
    const row = donations.value.find(x => x.id === d.id)
    if (row) row.pickup_schedule = data.pickup_schedule
    scheduling.value = false
    scheduleTarget.value = null
    viewDonation.value = { ...(row || d), pickup_schedule: data.pickup_schedule }
    scheduleSavedMsg.value = data.message
    setTimeout(() => { scheduleSavedMsg.value = "" }, 4000)
  } catch (err) {
    const errors = err.response?.data?.errors
    scheduleError.value = errors
      ? Object.values(errors).flat().join(" ")
      : err.response?.data?.message || "Could not save the schedule. Please try again."
    scheduling.value = false
  }
}

// opened from a notification (?view=ID): show that donation's details
function openFromQuery() {
  const viewId = Number(route.query.view)
  const target = viewId && donations.value.find(d => d.id === viewId)
  if (target) openView(target)
}

// a notification clicked while already on this page only changes the query
watch(() => route.query.view, async v => {
  if (!v || !isVerified.value) return
  await load()
  openFromQuery()
})

onMounted(async () => {
  await loadFoundationStatus()
  if (isVerified.value) {
    await load()
    openFromQuery()
  }
})

/* ── CAMPAIGN OPTIONS for filter dropdown ── */
const campaignOptions = computed(() => {
  const seen = new Set()
  return donations.value
    .filter(d => {
      if (seen.has(d.campaign.id)) return false
      seen.add(d.campaign.id)
      return true
    })
    .map(d => ({ id: d.campaign.id, title: d.campaign.title }))
})

/* ── FILTER ── */
const filtered = computed(() =>
  donations.value.filter(d => {
    const q = search.value.toLowerCase()
    const matchSearch =
      (d.donor.name   || "").toLowerCase().includes(q) ||
      (d.donor.email  || "").toLowerCase().includes(q) ||
      (d.campaign.title || "").toLowerCase().includes(q)

    const matchType     = activeType.value     === "all" || d.type     === activeType.value
    const matchStatus   = filterStatus.value   === ""    || d.status   === filterStatus.value
    const matchCampaign = filterCampaign.value === ""    || d.campaign.id === filterCampaign.value

    return matchSearch && matchType && matchStatus && matchCampaign
  })
)

function typeCount(key) {
  if (key === "all") return donations.value.length
  return donations.value.filter(d => d.type === key).length
}

/* ── STATS ── */
const stats = computed(() => {
  const monetary = donations.value.filter(d => d.type === "monetary")
  const items    = donations.value.filter(d => d.type === "item")
  const pending  = donations.value.filter(d => d.status === "pending")
  const received = donations.value.filter(d => d.status === "received")

  return [
    {
      label: "Total Raised",
      // Only money the foundation confirmed; pending / cancelled aren't raised yet
      value: "₱" + formatMoney(monetary.filter(d => d.status === "received").reduce((s, d) => s + Number(d.amount || 0), 0)),
      bg: "#ecfdf5", col: "#047857",
      icon: `<svg stroke-linecap="round" stroke-linejoin="round" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>`
    },
    {
      label: "Item Donations",
      value: items.length,
      bg: "#e8f0fe", col: "#2563eb",
      icon: `<svg stroke-linecap="round" stroke-linejoin="round" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>`
    },
    {
      label: "Pending",
      value: pending.length,
      bg: "#fef3c7", col: "#b45309",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`
    },
    {
      label: "Received",
      value: received.length,
      bg: "#ede9fe", col: "#6d28d9",
      icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`
    },
  ]
})

/* ── UPDATE STATUS ── */
async function updateStatus(d, status, thankYou = null) {
  await api.patch(`/foundation/donations/${d.id}/status`, {
    status,
    ...(thankYou ? { thank_you_note: thankYou } : {}),
  })
  const target = donations.value.find(x => x.id === d.id)
  if (target) {
    target.status = status
    if (status === 'received') target.thank_you_note = thankYou
  }
}

function cancelDonation(d) {
  updateStatus(d, "cancelled").catch(err => console.error("Status update failed:", err))
}

/* ── MARK AS RECEIVED (with an optional thank-you note) ── */
const receiveTarget = ref(null)
const thankYouNote = ref("")
const receiving = ref(false)
const receiveError = ref("")
const thankYouSuggestions = computed(() => {
  const first = (receiveTarget.value?.donor?.name || "").split(" ")[0]
  const hi = first ? `Thank you, ${first}!` : "Thank you!"
  return [
    `${hi} Your donation has been received and will go straight to our campaign.`,
    `${hi} Your generosity makes a real difference to the people we serve.`,
    `${hi} We truly appreciate your support.`,
  ]
})
function askReceive(d) {
  receiveTarget.value = d
  thankYouNote.value = ""
  receiveError.value = ""
}
async function confirmReceive() {
  receiving.value = true
  receiveError.value = ""
  try {
    await updateStatus(receiveTarget.value, "received", thankYouNote.value.trim() || null)
    receiveTarget.value = null
  } catch (err) {
    receiveError.value = err.response?.data?.message || "Could not mark this donation as received."
  } finally {
    receiving.value = false
  }
}

/* ── VIEW ── */
function openView(d) { viewDonation.value = { ...d } }

/* ── VERIFICATION IMAGE (proof of payment / item photo) ── */
// private files (receipts) already come as a full signed link
function getImage(path) {
  if (!path) return ''
  return /^https?:\/\//.test(path) ? path : `/storage/${path}`
}

/* ── DONATION DETAILS helpers ── */
// same reference the donor sees (FL-000123)
function donationRef(d) { return `FL-${String(d.id).padStart(6, '0')}` }

function formatDateTime(v) {
  if (!v) return '—'
  return new Date(v).toLocaleString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
  })
}

// One plain sentence telling the admin what to do with this donation now
function nextStep(d) {
  if (d.status === 'received') {
    return { label: 'Done', text: d.thank_you_note ? 'Received. Your thank-you note was sent to the donor.' : 'Received. The donor was notified.' }
  }
  if (d.status === 'cancelled') return { label: 'Closed', text: 'This donation was cancelled.' }
  if (d.type === 'monetary') {
    const where = d.payment_method ? `your ${d.payment_method} account` : 'your account'
    return d.proof_photo
      ? { label: 'Next step', text: `Check the receipt below. If the money arrived in ${where}, mark it as received.` }
      : { label: 'Next step', text: `No receipt was uploaded. Check ${where} before marking it as received.` }
  }
  if (d.delivery_method === 'dropoff') {
    return { label: 'Next step', text: 'The donor will bring this to your foundation. Mark it as received when it arrives.' }
  }
  const s = d.pickup_schedule
  if (!s) return { label: 'Next step', text: 'Set a pick-up day so the donor knows when you are coming.' }
  if (s.request) return { label: 'Next step', text: `The donor asked for another day (${s.request.label}). Choose a new pick-up day.` }
  if (s.confirmed) return { label: 'Next step', text: `Pick it up on ${s.label}, then mark it as received.` }
  return { label: 'Next step', text: `Pick-up set for ${s.label}. Waiting for the donor to confirm.` }
}

// What happened so far, oldest first
function historyOf(d) {
  const out = [{ text: `Donation submitted by ${d.donor?.name || 'the donor'}`, when: formatDateTime(d.donated_at) }]
  const s = d.pickup_schedule
  if (s) {
    out.push({ text: `Pick-up set for ${s.label}` })
    if (s.confirmed) out.push({ text: 'Donor confirmed the pick-up day', when: formatDateTime(s.confirmed_at), tone: 'ok' })
    if (s.request) out.push({ text: `Donor asked for another day: ${s.request.label}`, tone: 'warn' })
  }
  if (d.status === 'received') {
    out.push({ text: `Marked as received${d.thank_you_note ? ' · thank-you note sent' : ''}`, when: formatDateTime(d.updated_at), tone: 'ok' })
  } else if (d.status === 'cancelled') {
    out.push({ text: 'Cancelled', when: formatDateTime(d.updated_at), tone: 'bad' })
  } else {
    out.push({ text: 'Waiting to be marked as received', tone: 'wait' })
  }
  return out
}
function openImage(path, label) {
  enlargedImage.value = getImage(path)
  enlargedLabel.value = label
}
function closeLightbox() {
  enlargedImage.value = null
  enlargedLabel.value = ""
}

/* ── HELPERS ── */
function formatMoney(v) { return Number(v || 0).toLocaleString() }

function formatDate(d) {
  if (!d) return "—"
  return new Date(d).toLocaleDateString("en-PH", {
    year: "numeric", month: "short", day: "numeric"
  })
}

function typeLabel(t) {
  return { monetary: "Monetary", item: "Item" }[t] || t
}

function statusLabel(s) {
  return { pending: "Pending", received: "Received", cancelled: "Cancelled" }[s] || s
}

function toneForDonationStatus(s) {
  if (s === 'received')  return 'success'
  if (s === 'cancelled') return 'danger'
  return 'warning'
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px; } /* wraps: 2 per row on phones */

/* FILTERS */
.filters { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.filter-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.tabs { display: flex; gap: 4px; background: white; padding: 4px; border-radius: 12px; border: 1px solid var(--fa-border); max-width: 100%; overflow-x: auto; scrollbar-width: none; } /* scrolls inside itself on phones */
.tabs::-webkit-scrollbar { display: none; }
.tabs > * { flex-shrink: 0; white-space: nowrap; }
.tab-btn { display: flex; align-items: center; gap: 6px; padding: 7px 14px; border: none; background: transparent; color: var(--fa-muted); font-size: 0.83rem; font-weight: 500; border-radius: 8px; cursor: pointer; transition: all 0.15s; }
.tab-btn:hover  { background: #f1f5f9; color: var(--fa-text); }
.tab-btn.active { background: var(--fa-primary); color: white; }
.tab-count { font-size: 0.7rem; background: #f1f5f9; color: var(--fa-muted); padding: 1px 6px; border-radius: 20px; }
.tab-btn.active .tab-count { background: #fff; color: var(--fa-primary); }

.filter-select { padding: 9px 14px; border: 1px solid var(--fa-border); border-radius: 10px; font-size: 0.83rem; background: white; color: var(--fa-heading); outline: none; cursor: pointer; }

.search-box { display: flex; align-items: center; gap: 8px; background: white; border: 1px solid var(--fa-border); border-radius: 10px; padding: 9px 14px; color: var(--fa-muted); }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.85rem; width: 220px; color: var(--fa-heading); }

/* TABLE */
.table-card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(30,58,138,0.06); overflow-x: auto; -webkit-overflow-scrolling: touch; } /* wide table scrolls inside its box on phones, never cut off */
.table-card > table { min-width: 760px; }

table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 1px solid #f1f5f9; }
thead th { padding: 14px 16px; text-align: left; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fa-muted); }
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }
tbody td { padding: 12px 16px; font-size: 0.84rem; color: var(--fa-text-2); }

/* DONOR CELL */
.donor-cell   { display: flex; align-items: center; gap: 10px; }
.donor-cell strong { display: block; color: var(--fa-heading); font-size: 0.86rem; }
.donor-cell small  { color: var(--fa-muted); font-size: 0.74rem; }

.campaign-name { font-size: 0.83rem; color: var(--fa-heading); font-weight: 500; }

/* AMOUNT / ITEM */
.amount      { font-size: 0.95rem; font-weight: 700; color: var(--fa-success); }
.item-details { display: flex; align-items: center; gap: 6px; }
.item-name   { font-weight: 600; color: var(--fa-heading); font-size: 0.83rem; }
.item-qty    { font-size: 0.75rem; color: var(--fa-muted); background: #f1f5f9; padding: 2px 6px; border-radius: 6px; }

.date-cell { font-size: 0.8rem; color: var(--fa-muted); white-space: nowrap; }

/* ACTIONS */
.actions    { display: flex; gap: 6px; }
.action-btn { width: 29px; height: 29px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn.view    { background: var(--fa-primary-soft); color: var(--fa-primary); }
.action-btn.receive { background: #f0fdf4; color: var(--fa-success); }
.action-btn.cancel  { background: #fef2f2; color: var(--fa-danger); }
.action-btn:hover   { opacity: 0.75; transform: scale(1.1); }

/* MODAL */
/* Donation Details */
.dd-hero { border-radius: 14px; padding: 14px 16px; margin-bottom: 4px; border: 1px solid var(--fa-border); background: var(--fa-primary-tint); }
.dd-hero.is-pending   { background: var(--fa-warning-bg); border-color: var(--fa-warning-border); }
.dd-hero.is-received  { background: var(--fa-success-bg); border-color: var(--fa-success-border); }
.dd-hero.is-cancelled { background: var(--fa-danger-bg);  border-color: var(--fa-danger-border); }
.dd-hero-top { display: flex; align-items: center; gap: 12px; }
.dd-type-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; background: #fff; flex-shrink: 0; }
.dd-hero-main { flex: 1; min-width: 0; }
.dd-hero-what { font-size: 1.3rem; font-weight: 800; color: var(--fa-heading); line-height: 1.2; word-break: break-word; }
.dd-hero-sub { font-size: 0.78rem; color: var(--fa-text-2); margin-top: 3px; }
.dd-next { margin-top: 12px; padding-top: 10px; border-top: 1px dashed rgba(15, 45, 82, 0.15); font-size: 0.84rem; color: var(--fa-text); line-height: 1.5; }
.dd-next-label { display: inline-block; margin-right: 6px; padding: 1px 8px; border-radius: 20px; background: #fff; font-size: 0.7rem; font-weight: 800; color: var(--fa-heading); text-transform: uppercase; letter-spacing: 0.04em; }

.dd-section { padding: 16px 0 4px; border-bottom: 1px solid #f1f5f9; }
.dd-section:last-of-type { border-bottom: none; }
.dd-title { margin: 0 0 10px; font-size: 0.72rem; font-weight: 800; color: var(--fa-muted); text-transform: uppercase; letter-spacing: 0.06em; }

.dd-donor { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding-bottom: 12px; }
.dd-donor-info { flex: 1; min-width: 160px; }
.dd-donor-name { font-size: 0.95rem; font-weight: 700; color: var(--fa-heading); }
.dd-donor-contact { font-size: 0.8rem; color: var(--fa-muted); margin-top: 2px; word-break: break-all; }
.dd-donor-actions { display: flex; gap: 6px; }
.dd-chip-btn { padding: 6px 12px; border-radius: 20px; border: 1px solid var(--fa-primary-border); background: #fff; color: var(--fa-primary); font-size: 0.78rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
.dd-chip-btn:hover { background: var(--fa-primary-soft); }

.dd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; margin: 0; padding-bottom: 12px; }
.dd-field { min-width: 0; }
.dd-wide { grid-column: 1 / -1; }
.dd-field dt { font-size: 0.74rem; color: var(--fa-muted); margin-bottom: 2px; }
.dd-field dd { margin: 0; font-size: 0.88rem; font-weight: 700; color: var(--fa-heading); word-break: break-word; }
.dd-field dd.dd-plain { font-weight: 500; color: var(--fa-text); line-height: 1.5; }
.dd-money { color: var(--fa-success) !important; font-size: 1rem !important; }

.dd-delivery { display: flex; gap: 12px; align-items: flex-start; }
.dd-delivery-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--fa-primary-tint); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.dd-delivery-info { flex: 1; min-width: 0; }
.dd-delivery-method { font-size: 0.88rem; font-weight: 700; color: var(--fa-heading); }
.dd-address { margin-top: 3px; font-size: 0.84rem; color: var(--fa-text); line-height: 1.5; }
.dd-address.muted { color: var(--fa-muted); }
.dd-section .schedule-box { margin-bottom: 12px; }
.dd-section .directions-btn + .schedule-box, .dd-delivery + .schedule-box { margin-top: 12px; }

.dd-photo { display: block; position: relative; width: 100%; padding: 0; margin-bottom: 12px; border: 1px solid var(--fa-border); border-radius: 12px; overflow: hidden; background: #f8fafc; cursor: zoom-in; }
.dd-photo img { display: block; width: 100%; max-height: 220px; object-fit: contain; background: #f8fafc; }
.dd-photo-hint { position: absolute; right: 8px; bottom: 8px; padding: 4px 10px; border-radius: 20px; background: rgba(15, 45, 82, 0.75); color: #fff; font-size: 0.72rem; font-weight: 700; }
.dd-empty { margin-bottom: 12px; padding: 12px 14px; border-radius: 10px; border: 1px dashed var(--fa-border); background: #f8fafc; font-size: 0.82rem; color: var(--fa-muted); }

.dd-quote { margin-bottom: 10px; padding: 10px 14px; border-radius: 10px; background: #f8fafc; border-left: 3px solid var(--fa-border); font-size: 0.86rem; color: var(--fa-text); line-height: 1.55; font-style: italic; }
.dd-quote.ours { background: var(--fa-success-bg); border-left-color: var(--fa-success); }
.dd-quote-who { display: block; margin-bottom: 2px; font-size: 0.7rem; font-style: normal; font-weight: 800; color: var(--fa-muted); text-transform: uppercase; letter-spacing: 0.04em; }

.dd-timeline { list-style: none; margin: 0; padding: 0 0 8px; }
.dd-timeline li { position: relative; display: flex; gap: 12px; padding-bottom: 12px; }
.dd-timeline li:not(:last-child)::before { content: ''; position: absolute; left: 5px; top: 14px; bottom: -2px; width: 2px; background: var(--fa-border); }
.dd-dot { width: 12px; height: 12px; margin-top: 3px; border-radius: 50%; background: var(--fa-primary); flex-shrink: 0; box-shadow: 0 0 0 3px var(--fa-primary-soft); }
.dd-timeline li.ok .dd-dot   { background: var(--fa-success); box-shadow: 0 0 0 3px var(--fa-success-bg); }
.dd-timeline li.warn .dd-dot { background: var(--fa-warning); box-shadow: 0 0 0 3px var(--fa-warning-bg); }
.dd-timeline li.bad .dd-dot  { background: var(--fa-danger);  box-shadow: 0 0 0 3px var(--fa-danger-bg); }
.dd-timeline li.wait .dd-dot { background: #fff; box-shadow: inset 0 0 0 2px var(--fa-muted); }
.dd-ev { font-size: 0.84rem; font-weight: 600; color: var(--fa-heading); }
.dd-timeline li.wait .dd-ev { color: var(--fa-muted); font-weight: 500; }
.dd-when { font-size: 0.74rem; color: var(--fa-muted); margin-top: 1px; }

@media (max-width: 520px) {
  .dd-grid { grid-template-columns: 1fr; }
}

.pickup-point { margin: 10px 0 0; font-size: 0.8rem; color: var(--fa-success); font-weight: 600; }
.directions-btn {
  display: inline-flex; align-items: center; gap: 7px; margin-top: 12px;
  padding: 9px 16px; border-radius: 10px; background: var(--fa-primary); color: #fff;
  font-size: 0.84rem; font-weight: 700; text-decoration: none;
}
.directions-btn:hover { background: var(--fa-primary-hover); }
/* Pick-up schedule (details) */
.schedule-box { margin-top: 14px; padding: 14px; border-radius: 12px; background: var(--fa-primary-tint); border: 1px solid var(--fa-primary-border); }
.schedule-head { font-size: 0.76rem; font-weight: 800; color: var(--fa-heading); text-transform: uppercase; letter-spacing: 0.04em; }
.schedule-when { margin-top: 6px; font-size: 0.98rem; font-weight: 800; color: var(--fa-heading); }
.schedule-note { margin: 4px 0 0; font-size: 0.82rem; color: var(--fa-text-2); font-style: italic; }
.schedule-empty { margin: 6px 0 0; font-size: 0.84rem; color: var(--fa-text-2); }
.schedule-chip { margin-top: 10px; padding: 7px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; line-height: 1.45; }
.schedule-chip.ok   { background: var(--fa-success-bg); color: var(--fa-success); border: 1px solid var(--fa-success-border); }
.schedule-chip.wait { background: #fff; color: var(--fa-text-2); border: 1px solid var(--fa-border); }
.schedule-chip.warn { background: var(--fa-warning-bg); color: var(--fa-warning); border: 1px solid var(--fa-warning-border); }
.schedule-request-note { display: block; margin-top: 2px; font-style: italic; font-weight: 500; }
.schedule-saved { margin: 10px 0 0; font-size: 0.8rem; font-weight: 700; color: var(--fa-success); }
.schedule-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.btn-schedule, .btn-schedule-outline {
  padding: 8px 14px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; cursor: pointer;
}
.btn-schedule { border: none; background: var(--fa-primary); color: #fff; }
.btn-schedule:hover { background: var(--fa-primary-hover); }
.btn-schedule-outline { border: 1.5px solid var(--fa-primary-border); background: #fff; color: var(--fa-primary); }
.btn-schedule-outline:hover { background: var(--fa-primary-soft); }

/* Pick-up schedule (window) */
.sched-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; }
.sched-chip {
  padding: 7px 12px; border-radius: 20px; border: 1.5px solid var(--fa-border); background: #fff;
  font-size: 0.8rem; font-weight: 600; color: var(--fa-text-2); cursor: pointer;
}
.sched-chip.active { border-color: var(--fa-primary); background: var(--fa-primary-soft); color: var(--fa-primary); }
.sched-date {
  padding: 6px 10px; border-radius: 10px; border: 1.5px solid var(--fa-border);
  font-size: 0.82rem; color: var(--fa-text); font-family: inherit;
}
.sched-date:focus { outline: none; border-color: var(--fa-primary); box-shadow: 0 0 0 3px var(--fa-primary-soft); }
.sched-calendar { margin: -4px 0 16px; }

/* Pick-up hint under the status in the table */
.sched-hint { display: block; margin-top: 5px; font-size: 0.72rem; font-weight: 700; color: var(--fa-text-2); white-space: nowrap; }
.sched-hint.need { color: var(--fa-primary); }
.sched-hint.warn { color: var(--fa-warning); }
.btn-cancel { padding: 9px 18px; border: 1px solid var(--fa-border); background: white; color: var(--fa-muted); border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-save   { padding: 9px 20px; border: none; background: #047857; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-danger { padding: 9px 20px; border: none; background: #dc2626; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }

/* IMAGE LIGHTBOX */
.lightbox-overlay {
  position: fixed; inset: 0; z-index: 300;
  background: rgba(15,23,42,0.75);
  backdrop-filter: blur(3px);
  display: flex; align-items: center; justify-content: center;
  padding: 32px;
}
.lightbox-panel {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  max-width: 90vw; max-height: 90vh;
  box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  display: flex; flex-direction: column;
}
.lightbox-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; border-bottom: 1px solid var(--fa-border);
  background: #f8fafc;
}
.lightbox-head span { font-size: 0.78rem; font-weight: 600; color: var(--fa-text-2); }
.lightbox-close {
  width: 26px; height: 26px; border-radius: 7px;
  border: 1px solid var(--fa-border); background: transparent;
  color: var(--fa-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.lightbox-close:hover { background: #f1f5f9; color: var(--fa-heading); }
.lightbox-img {
  display: block;
  max-width: 90vw; max-height: calc(90vh - 49px);
  object-fit: contain;
}

/* Mark as received: optional thank-you note */
.receive-intro { font-size: 0.88rem; color: var(--fa-text, #334155); line-height: 1.55; margin: 0 0 16px; }
.receive-label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--fa-heading); margin-bottom: 8px; }
.receive-label .optional { font-weight: 500; color: var(--fa-muted); }
.receive-suggestions { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
.suggestion-chip {
  text-align: left; font-size: 0.8rem; line-height: 1.4; color: var(--fa-primary);
  background: var(--fa-primary-tint); border: 1px solid var(--fa-primary-border);
  border-radius: 10px; padding: 7px 10px; cursor: pointer; font-family: inherit;
}
.suggestion-chip:hover { background: var(--fa-primary-soft); }
.receive-textarea {
  width: 100%; box-sizing: border-box; font-family: inherit; font-size: 0.88rem;
  border: 1.5px solid var(--fa-primary-border); border-radius: 10px; padding: 10px 12px; resize: vertical;
}
.receive-textarea:focus { outline: none; border-color: var(--fa-primary); box-shadow: 0 0 0 3px var(--fa-primary-soft); }
.receive-count { text-align: right; font-size: 0.74rem; color: var(--fa-muted); margin-top: 4px; }
.receive-error { color: #b91c1c; font-size: 0.82rem; margin: 8px 0 0; }
</style>