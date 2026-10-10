<template>
  <div class="page">

    <!-- HEADER -->
    <PageHeader title="Campaigns" subtitle="Manage your foundation's campaigns">
      <button
        class="btn-add"
        @click="openCreate"
        :disabled="!isVerified"
        :title="!isVerified ? 'Your foundation must be verified before creating campaigns' : ''"
      >
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        New Campaign
      </button>
    </PageHeader>

    <!-- PENDING VERIFICATION BANNER -->
    <PendingVerificationBanner v-if="!isVerified && !checkingStatus">
      You'll be able to create and manage campaigns once your foundation has been verified by the super admin.
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
      <div class="tabs">
        <button
          v-for="t in tabs" :key="t.key"
          class="tab-btn" :class="{ active: activeTab === t.key }"
          @click="activeTab = t.key"
        >
          {{ t.label }}
          <span class="tab-count">{{ tabCount(t.key) }}</span>
        </button>
      </div>
      <div class="search-box">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="search" placeholder="Search campaigns..." />
      </div>
    </div>

    <!-- LOADING -->
    <EmptyState v-if="isLoading" loading message="Loading campaigns..." />

    <!-- EMPTY -->
    <EmptyState v-else-if="filtered.length === 0" icon="📋" message="No campaigns found.">
      <button class="btn-add" @click="openCreate">Create your first campaign</button>
    </EmptyState>

    <!-- GRID -->
    <div v-else class="grid">
      <div class="card" v-for="c in filtered" :key="c.id">

        <!-- COVER -->
        <div class="cover">
          <img v-if="c.cover_photo" :src="getImage(c.cover_photo)" alt="" />
          <div v-else class="cover-placeholder">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
          </div>

          <span class="badge" :class="c.status">{{ statusLabel(c.status) }}</span>

          <span v-if="c.photos?.length" class="photo-chip">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
            +{{ c.photos.length }}
          </span>

          <div class="card-actions-overlay">
            <button class="ov-btn" title="Edit" @click="openEdit(c)">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
            <button v-if="c.status === 'active'" class="ov-btn pause" title="Pause" @click="openPauseModal(c)">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>
              </svg>
            </button>
            <button v-if="c.status === 'paused'" class="ov-btn resume" title="Resume" @click="resumeCampaign(c)">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polygon points="5 3 19 12 5 21 5 3"/>
              </svg>
            </button>
            <button
              v-if="c.status === 'active' || c.status === 'paused'"
              class="ov-btn complete" title="Mark Complete"
              @click="openCompleteModal(c)"
            >
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </button>
            <button class="ov-btn danger" title="Delete" @click="confirmDelete(c)">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14H6L5 6"/>
                <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- BODY -->
        <div class="body">
          <div class="card-meta">
            <Badge :label="c.type" tone="info" />
            <span class="date">{{ formatDate(c.created_at) }}</span>
          </div>

          <div class="card-meta" v-if="c.category">
            <Badge :label="`${c.category.icon || '🏷️'} ${c.category.name}`" tone="purple" />
          </div>

          <h3 class="title">{{ c.title }}</h3>
          <p class="desc">{{ c.description || 'No description.' }}</p>

          <!-- PAUSE REASON -->
          <div v-if="c.status === 'paused' && c.pause_reason" class="pause-reason">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ c.pause_reason }}</span>
          </div>

          <!-- DELIVERY BADGES on card -->
          <div v-if="(c.type === 'item' || c.type === 'both') && c.accepted_delivery_methods?.length" class="card-delivery">
            <span
              v-if="c.accepted_delivery_methods.includes('dropoff')"
              class="delivery-badge dropoff"
            >🏢 Drop Off</span>
            <span
              v-if="c.accepted_delivery_methods.includes('pickup')"
              class="delivery-badge pickup"
            >🏠 Pick Up</span>
          </div>

          <!-- DATES -->
          <div class="dates-row" v-if="c.start_date || c.end_date">
            <div class="date-item" v-if="c.start_date">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
              <span>{{ formatDate(c.start_date) }}</span>
            </div>
            <span v-if="c.start_date && c.end_date" class="date-arrow">→</span>
            <div class="date-item" v-if="c.end_date">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
              <span>{{ formatDate(c.end_date) }}</span>
            </div>
          </div>

          <!-- PROGRESS -->
          <div v-if="c.type !== 'item'" class="progress-wrap">
            <div class="progress-row">
              <span class="raised">₱{{ formatMoney(c.current_amount) }}</span>
              <span class="goal">of ₱{{ formatMoney(c.goal_amount) }}</span>
            </div>
            <div class="bar">
              <div class="fill" :style="{ width: percent(c) + '%' }"></div>
            </div>
            <div class="progress-foot">
              <span class="pct">{{ percent(c) }}% funded</span>
              <span class="donors">{{ c.donors_count ?? 0 }} donors</span>
            </div>
          </div>

          <button class="view-btn" @click="openView(c)">View Details</button>
        </div>
      </div>
    </div>

    </template>

    <!-- CREATE / EDIT MODAL -->
    <FoundationModal
      v-if="showForm"
      :title="editTarget ? 'Edit Campaign' : 'New Campaign'"
      width="520px"
      @close="showForm = false"
    >
      <div class="form-group">
        <label>Title <span class="req">*</span></label>
        <input v-model="form.title" type="text" placeholder="Campaign title" />
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea v-model="form.description" placeholder="What is this campaign about?"></textarea>
      </div>

      <div class="form-group">
        <label>Category</label>
        <select v-model="form.category_id">
          <option :value="null">No category</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.icon }} {{ cat.name }}
          </option>
        </select>
        <p class="field-hint">
          This can be different from your foundation's category — pick whatever fits this specific campaign.
        </p>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Type</label>
          <select v-model="form.type" @change="onTypeChange">
            <option value="monetary">Monetary</option>
            <option value="item">Item Donation</option>
            <option value="both">Both</option>
          </select>
        </div>
        <div class="form-group" v-if="form.type !== 'item'">
          <label>Goal Amount (₱)</label>
          <input v-model="form.goal_amount" type="number" placeholder="0" min="0" />
        </div>
      </div>

      <div class="form-group" v-if="form.type === 'item' || form.type === 'both'">
        <label>
          Accepted Delivery Methods
          <span class="req">*</span>
        </label>
        <p class="field-hint">Choose how donors can send item donations to your foundation. You can allow both.</p>

        <div class="delivery-options">

          <label
            class="delivery-opt"
            :class="{ active: form.accepted_delivery_methods.includes('dropoff') }"
          >
            <input
              type="checkbox"
              value="dropoff"
              v-model="form.accepted_delivery_methods"
              style="display:none"
            />
            <div class="delivery-opt-icon">🏢</div>
            <div class="delivery-opt-body">
              <div class="delivery-opt-title">Drop Off</div>
              <div class="delivery-opt-desc">Donor personally brings items to your foundation's address</div>
            </div>
            <div class="delivery-check" v-if="form.accepted_delivery_methods.includes('dropoff')">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
          </label>

          <label
            class="delivery-opt"
            :class="{ active: form.accepted_delivery_methods.includes('pickup') }"
          >
            <input
              type="checkbox"
              value="pickup"
              v-model="form.accepted_delivery_methods"
              style="display:none"
            />
            <div class="delivery-opt-icon">🏠</div>
            <div class="delivery-opt-body">
              <div class="delivery-opt-title">Pick Up</div>
              <div class="delivery-opt-desc">Your foundation will collect items from the donor's location</div>
            </div>
            <div class="delivery-check" v-if="form.accepted_delivery_methods.includes('pickup')">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
          </label>

        </div>
      </div>

      <div class="form-group" v-if="form.type === 'monetary' || form.type === 'both'">
        <label>
          Accepted Payment Methods
          <span class="req">*</span>
        </label>

        <div v-if="!loadingPayments && paymentAccounts.length === 0" class="payment-empty-hint">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          You haven't set up any payment accounts yet.
          <router-link
            :to="{ path: '/foundation/setting', query: { section: 'payment' } }"
            class="payment-setup-link">Set up payment accounts</router-link>
          before donors can donate money.
        </div>

        <p v-else class="field-hint">Choose which of your configured payment accounts donors can use.</p>

        <EmptyState v-if="loadingPayments" loading compact />

        <div v-else-if="paymentAccounts.length" class="payment-options">
          <label
            v-for="pm in paymentAccounts" :key="pm.payment_method_id"
            class="payment-opt"
            :class="{ active: form.accepted_payment_methods.includes(pm.payment_method_id) }"
          >
            <input
              type="checkbox"
              :value="pm.payment_method_id"
              v-model="form.accepted_payment_methods"
              style="display:none"
            />
            <div class="payment-opt-icon">{{ pm.icon || '💳' }}</div>
            <div class="payment-opt-body">
              <div class="payment-opt-title">{{ pm.name }}</div>
              <div class="payment-opt-desc">{{ pm.account_name }} — {{ pm.account_number }}</div>
            </div>
            <div class="delivery-check" v-if="form.accepted_payment_methods.includes(pm.payment_method_id)">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
          </label>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Start Date</label>
          <input v-model="form.start_date" type="date" />
        </div>
        <div class="form-group">
          <label>End Date</label>
          <input v-model="form.end_date" type="date" />
        </div>
      </div>

      <div class="form-group">
        <label>Cover Photo</label>
        <div class="upload-area" @click="$refs.coverInput.click()">
          <img v-if="previewCover" :src="previewCover" class="upload-preview" alt="cover" />
          <div v-else class="upload-placeholder">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
            <span>Click to upload cover photo</span>
          </div>
        </div>
        <input ref="coverInput" type="file" accept="image/*" style="display:none" @change="onCoverFile" />
      </div>

      <div class="form-group">
        <label>
          Follow-up Photos
          <span class="label-hint">Supporting images for this campaign</span>
        </label>

        <div v-if="existingPhotos.length" class="photo-grid">
          <div
            v-for="p in existingPhotos" :key="p.id"
            class="photo-thumb"
            :class="{ 'marked-delete': photosToDelete.includes(p.id) }"
          >
            <img :src="getImage(p.photo_path)" alt="" />
            <div class="thumb-caption">{{ p.caption || '' }}</div>
            <button
              class="thumb-del"
              @click="toggleDeletePhoto(p.id)"
              :title="photosToDelete.includes(p.id) ? 'Undo' : 'Remove'"
            >{{ photosToDelete.includes(p.id) ? '↩' : '×' }}</button>
          </div>
        </div>

        <div v-if="newPhotoPreviews.length" class="photo-grid" style="margin-top:10px">
          <div v-for="(p, i) in newPhotoPreviews" :key="i" class="photo-thumb">
            <img :src="p.url" alt="" />
            <input v-model="newPhotoCaptions[i]" class="caption-input" placeholder="Caption (optional)" />
            <button class="thumb-del" @click="removeNewPhoto(i)">×</button>
          </div>
        </div>

        <button type="button" class="btn-upload-more" @click="$refs.photosInput.click()">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Photos
        </button>
        <input ref="photosInput" type="file" accept="image/*" multiple style="display:none" @change="onPhotosFile" />
      </div>

      <p v-if="formError" class="error-text">{{ formError }}</p>

      <template #footer>
        <button class="btn-cancel" @click="showForm = false">Cancel</button>
        <button class="btn-save" @click="save" :disabled="saving">
          <span v-if="!saving">{{ editTarget ? 'Save Changes' : 'Create Campaign' }}</span>
          <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </FoundationModal>

    <!-- VIEW MODAL -->
    <FoundationModal
      v-if="viewTarget"
      :title="viewTarget.title"
      width="640px"
      @close="viewTarget = null"
    >
      <!-- COVER with overlaid status/type pills -->
      <div class="vd-cover" v-if="viewTarget.cover_photo">
        <img :src="getImage(viewTarget.cover_photo)" alt="" />
        <div class="vd-cover-gradient"></div>
        <div class="vd-cover-badges">
          <span class="vd-pill vd-status" :class="viewTarget.status">
            <span class="vd-status-dot"></span>{{ statusLabel(viewTarget.status) }}
          </span>
          <span class="vd-pill vd-type">{{ viewTarget.type }}</span>
        </div>
      </div>

      <!-- No-cover fallback: pills sit above content instead -->
      <div class="vd-header-noCover" v-else>
        <span class="vd-pill vd-status" :class="viewTarget.status">
          <span class="vd-status-dot"></span>{{ statusLabel(viewTarget.status) }}
        </span>
        <span class="vd-pill vd-type">{{ viewTarget.type }}</span>
        <span v-if="viewTarget.category" class="vd-pill vd-category">
          {{ viewTarget.category.icon || '🏷️' }} {{ viewTarget.category.name }}
        </span>
      </div>

      <!-- Category pill under the cover when a cover exists -->
      <div class="vd-category-row" v-if="viewTarget.cover_photo && viewTarget.category">
        <span class="vd-pill vd-category">
          {{ viewTarget.category.icon || '🏷️' }} {{ viewTarget.category.name }}
        </span>
      </div>

      <!-- FUNDING STATS -->
      <template v-if="viewTarget.type !== 'item'">
        <div class="vd-stats">
          <div class="vd-stat">
            <span class="vd-stat-label">Raised</span>
            <span class="vd-stat-value raised">₱{{ formatMoney(viewTarget.current_amount) }}</span>
          </div>
          <div class="vd-stat-divider"></div>
          <div class="vd-stat">
            <span class="vd-stat-label">Goal</span>
            <span class="vd-stat-value">₱{{ formatMoney(viewTarget.goal_amount) }}</span>
          </div>
          <div class="vd-stat-divider"></div>
          <div class="vd-stat">
            <span class="vd-stat-label">Funded</span>
            <span class="vd-stat-value pct">{{ percent(viewTarget) }}%</span>
          </div>
        </div>
        <div class="vd-progress-bar">
          <div class="vd-progress-fill" :style="{ width: percent(viewTarget) + '%' }"></div>
        </div>
      </template>

      <!-- INFO LIST -->
      <div class="vd-info-list">
        <div class="vd-info-row">
          <span class="vd-info-icon">📅</span>
          <span class="vd-info-label">Duration</span>
          <span class="vd-info-value">
            {{ formatDate(viewTarget.start_date) || '—' }} → {{ formatDate(viewTarget.end_date) || 'Ongoing' }}
          </span>
        </div>

        <div class="vd-info-row" v-if="viewTarget.type === 'item' || viewTarget.type === 'both'">
          <span class="vd-info-icon">📦</span>
          <span class="vd-info-label">Delivery</span>
          <span class="vd-info-value">
            <span class="vd-tag-row" v-if="viewTarget.accepted_delivery_methods?.length">
              <span
                v-if="viewTarget.accepted_delivery_methods.includes('dropoff')"
                class="vd-tag dropoff"
              >🏢 Drop Off</span>
              <span
                v-if="viewTarget.accepted_delivery_methods.includes('pickup')"
                class="vd-tag pickup"
              >🏠 Pick Up</span>
            </span>
            <span v-else class="vd-muted">None specified</span>
          </span>
        </div>

        <div class="vd-info-row" v-if="viewTarget.type === 'monetary' || viewTarget.type === 'both'">
          <span class="vd-info-icon">💳</span>
          <span class="vd-info-label">Payment</span>
          <span class="vd-info-value">
            <span class="vd-tag-row" v-if="viewTarget.accepted_payment_methods?.length">
              <span
                v-for="pmId in viewTarget.accepted_payment_methods" :key="pmId"
                class="vd-tag payment"
              >{{ paymentMethodName(pmId) }}</span>
            </span>
            <span v-else class="vd-muted">None specified</span>
          </span>
        </div>

        <div class="vd-info-row" v-if="viewTarget.pause_reason">
          <span class="vd-info-icon">⏸️</span>
          <span class="vd-info-label">Pause Reason</span>
          <span class="vd-info-value vd-pause-text">{{ viewTarget.pause_reason }}</span>
        </div>

        <div class="vd-info-row vd-info-row-desc">
          <span class="vd-info-icon">📝</span>
          <span class="vd-info-label">Description</span>
          <span class="vd-info-value">{{ viewTarget.description || '—' }}</span>
        </div>
      </div>

      <div v-if="viewTarget.photos?.length" class="vd-section">
        <div class="vd-section-label">Follow-up Photos</div>
        <div class="view-photo-grid">
          <div
            v-for="p in viewTarget.photos" :key="p.id"
            class="view-photo"
            @click="lightbox = getImage(p.photo_path)"
          >
            <img :src="getImage(p.photo_path)" alt="" />
            <div class="view-photo-overlay">
              <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </div>
            <div v-if="p.caption" class="view-photo-caption">{{ p.caption }}</div>
          </div>
        </div>
      </div>

      <!-- CAMPAIGN UPDATES (post-completion progress reports) -->
      <CampaignUpdatesPanel :campaign="viewTarget" />

      <template #footer>
        <button class="btn-cancel" @click="viewTarget = null">Close</button>
        <button
          v-if="viewTarget.status === 'active'"
          class="btn-pause-confirm"
          @click="openPauseModal(viewTarget); viewTarget = null"
        >Pause</button>
        <button
          v-if="viewTarget.status === 'paused'"
          class="btn-resume"
          @click="resumeCampaign(viewTarget); viewTarget = null"
        >Resume</button>
        <button
          v-if="viewTarget.status === 'active' || viewTarget.status === 'paused'"
          class="btn-complete"
          @click="openCompleteModal(viewTarget); viewTarget = null"
        >Mark Complete</button>
        <button class="btn-save" @click="openEdit(viewTarget); viewTarget = null">Edit</button>
      </template>
    </FoundationModal>

    <!-- PAUSE MODAL -->
    <FoundationModal
      v-if="showPauseModal"
      title="Pause Campaign"
      width="440px"
      @close="showPauseModal = false"
    >
      <p class="pause-modal-desc">
        Pausing <strong>{{ pauseTarget?.title }}</strong> will temporarily hide the donate
        button from donors. Please provide a reason so donors know what's happening.
      </p>
      <div class="form-group">
        <label>Reason for Pausing <span class="req">*</span></label>
        <textarea
          v-model="pauseReason"
          placeholder="e.g. We are currently sorting donations and will resume shortly..."
        ></textarea>
      </div>
      <p v-if="pauseError" class="error-text">{{ pauseError }}</p>

      <template #footer>
        <button class="btn-cancel" @click="showPauseModal = false">Cancel</button>
        <button class="btn-pause-confirm" @click="confirmPause" :disabled="pauseSaving">
          <span v-if="!pauseSaving">Pause Campaign</span>
          <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </FoundationModal>

    <!-- MARK COMPLETE CONFIRM -->
    <FoundationModal
      v-if="completeTarget"
      title="Mark Campaign as Complete"
      width="440px"
      @close="completeTarget = null"
    >
      <div class="complete-modal-body">
        <div class="complete-icon">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <p class="complete-modal-text">
          Mark <strong>{{ completeTarget?.title }}</strong> as completed?
          This will close the campaign to further donations and <strong>cannot be undone</strong>.
        </p>
      </div>

      <template #footer>
        <button class="btn-cancel" @click="completeTarget = null">Cancel</button>
        <button class="btn-complete-confirm" @click="confirmComplete" :disabled="completing">
          <span v-if="!completing">Mark Complete</span>
          <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </FoundationModal>

    <!-- DELETE CONFIRM -->
    <FoundationModal
      v-if="deleteTarget"
      title="Delete Campaign"
      width="440px"
      @close="deleteTarget = null"
    >
      <p style="font-size:0.88rem;color:#475569;margin:0;">
        Are you sure you want to delete <strong>{{ deleteTarget.title }}</strong>? This cannot be undone.
      </p>

      <template #footer>
        <button class="btn-cancel" @click="deleteTarget = null">Cancel</button>
        <button class="btn-danger" @click="deleteCampaign" :disabled="deleting">
          <span v-if="!deleting">Delete</span>
          <Spinner v-else :size="16" color="#fff" track="rgba(255,255,255,0.35)" />
        </button>
      </template>
    </FoundationModal>

    <!-- LIGHTBOX -->
    <Lightbox v-model="lightbox" />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"
import CampaignUpdatesPanel from "../components/foundation-admin/campaignupdate.vue"
import PageHeader from "../components/foundation-admin/pageheader.vue"
import PendingVerificationBanner from "../components/foundation-admin/pendingverificationbanner.vue"
import StatCard from "../components/foundation-admin/statcard.vue"
import EmptyState from "../components/foundation-admin/emptystate.vue"
import Badge from "../components/foundation-admin/badge.vue"
import FoundationModal from "../components/foundation-admin/basemodal.vue"
import Lightbox from "../components/foundation-admin/lightbox.vue"
import Spinner from "../components/foundation-admin/spinner.vue"

const campaigns    = ref([])
const search       = ref("")
const activeTab    = ref("all")
const isLoading    = ref(false)
const showForm     = ref(false)
const editTarget   = ref(null)
const viewTarget   = ref(null)
const deleteTarget = ref(null)
const saving       = ref(false)
const deleting     = ref(false)
const formError    = ref("")
const lightbox     = ref(null)

const previewCover     = ref(null)
const newPhotoPreviews = ref([])
const newPhotoCaptions = ref([])
const existingPhotos   = ref([])
const photosToDelete   = ref([])

const coverInput  = ref(null)
const photosInput = ref(null)

const showPauseModal = ref(false)
const pauseTarget    = ref(null)
const pauseReason    = ref("")
const pauseError     = ref("")
const pauseSaving    = ref(false)

const completeTarget = ref(null)
const completing     = ref(false)

const isVerified     = ref(false)
const checkingStatus = ref(true)

const categories = ref([])

const form = ref({
  title: "", description: "", category_id: null, type: "monetary",
  goal_amount: "", start_date: "", end_date: "",
  cover_photo: null,
  accepted_delivery_methods: [],
  accepted_payment_methods: []
})

const paymentAccounts = ref([])
const loadingPayments = ref(false)

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

async function loadCategories() {
  try {
    const res = await api.get('/categories')
    categories.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error('Failed to load categories:', err)
    categories.value = []
  }
}

async function loadPaymentAccounts() {
  loadingPayments.value = true
  try {
    const res = await api.get("/foundation/payment-accounts")
    paymentAccounts.value = (Array.isArray(res.data) ? res.data : [])
      .filter(m => m.is_configured)
  } catch (err) {
    console.error(err)
    paymentAccounts.value = []
  } finally {
    loadingPayments.value = false
  }
}

const tabs = [
  { key: "all",       label: "All"       },
  { key: "active",    label: "Active"    },
  { key: "paused",    label: "Paused"    },
  { key: "completed", label: "Completed" },
  { key: "draft",     label: "Draft"     },
]

async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/campaigns")
    campaigns.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error("Failed:", err)
    campaigns.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadFoundationStatus()
  loadCategories()
  if (isVerified.value) {
    load()
    loadPaymentAccounts()
  }
})

const filtered = computed(() =>
  campaigns.value
    .filter(c => activeTab.value === "all" || c.status === activeTab.value)
    .filter(c => (c.title || "").toLowerCase().includes(search.value.toLowerCase()))
)

function tabCount(key) {
  if (key === "all") return campaigns.value.length
  return campaigns.value.filter(c => c.status === key).length
}

const stats = computed(() => [
  { label: "Total",
    value: campaigns.value.length,
    bg: "#eff6ff", col: "#3b82f6",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>` },
  { label: "Active",
    value: campaigns.value.filter(c => c.status === "active").length,
    bg: "#f0fdf4", col: "#059669",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>` },
  { label: "Paused",
    value: campaigns.value.filter(c => c.status === "paused").length,
    bg: "#fefce8", col: "#ca8a04",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>` },
  { label: "Completed",
    value: campaigns.value.filter(c => c.status === "completed").length,
    bg: "#f5f3ff", col: "#7c3aed",
    icon: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>` },
])

function onTypeChange() {
  if (form.value.type === 'monetary') {
    form.value.accepted_delivery_methods = []
  }
  if (form.value.type === 'item') {
    form.value.accepted_payment_methods = []
  }
}

function openCreate() {
  if (!isVerified.value) return
  editTarget.value       = null
  form.value             = {
    title: "", description: "", category_id: null, type: "monetary",
    goal_amount: "", start_date: "", end_date: "",
    cover_photo: null,
    accepted_delivery_methods: [],
    accepted_payment_methods: []
  }
  previewCover.value     = null
  newPhotoPreviews.value = []
  newPhotoCaptions.value = []
  existingPhotos.value   = []
  photosToDelete.value   = []
  formError.value        = ""
  showForm.value         = true
}

function openEdit(c) {
  editTarget.value = c
  form.value = {
    title:       c.title,
    description: c.description || "",
    category_id: c.category_id || null,
    type:        c.type || "monetary",
    goal_amount: c.goal_amount || "",
    start_date:  c.start_date ? c.start_date.substring(0, 10) : "",
    end_date:    c.end_date   ? c.end_date.substring(0, 10)   : "",
    cover_photo: null,
    accepted_delivery_methods: Array.isArray(c.accepted_delivery_methods)
      ? [...c.accepted_delivery_methods]
      : [],
    accepted_payment_methods: Array.isArray(c.accepted_payment_methods)
      ? [...c.accepted_payment_methods]
      : []
  }
  previewCover.value     = c.cover_photo ? getImage(c.cover_photo) : null
  existingPhotos.value   = [...(c.photos || [])]
  newPhotoPreviews.value = []
  newPhotoCaptions.value = []
  photosToDelete.value   = []
  formError.value        = ""
  showForm.value         = true
}

function openView(c)      { viewTarget.value   = c }
function confirmDelete(c) { deleteTarget.value = c }

function openPauseModal(c) {
  pauseTarget.value    = c
  pauseReason.value    = ""
  pauseError.value     = ""
  showPauseModal.value = true
}

async function confirmPause() {
  pauseError.value = ""
  if (!pauseReason.value.trim()) {
    pauseError.value = "Please provide a reason for pausing."
    return
  }
  pauseSaving.value = true
  try {
    await api.patch(`/foundation/campaigns/${pauseTarget.value.id}/pause`, {
      pause_reason: pauseReason.value
    })
    const target = campaigns.value.find(x => x.id === pauseTarget.value.id)
    if (target) {
      target.status       = 'paused'
      target.pause_reason = pauseReason.value
    }
    showPauseModal.value = false
  } catch (err) {
    pauseError.value = err.response?.data?.message || "Failed to pause campaign."
  } finally {
    pauseSaving.value = false
  }
}

async function resumeCampaign(c) {
  try {
    await api.patch(`/foundation/campaigns/${c.id}/resume`)
    const target = campaigns.value.find(x => x.id === c.id)
    if (target) { target.status = 'active'; target.pause_reason = null }
  } catch (err) { console.error(err) }
}

function openCompleteModal(c) {
  completeTarget.value = c
}

async function confirmComplete() {
  if (!completeTarget.value) return
  completing.value = true
  try {
    await api.patch(`/foundation/campaigns/${completeTarget.value.id}/complete`)
    const target = campaigns.value.find(x => x.id === completeTarget.value.id)
    if (target) { target.status = 'completed'; target.pause_reason = null }
    completeTarget.value = null
  } catch (err) {
    console.error(err)
  } finally {
    completing.value = false
  }
}

function onCoverFile(e) {
  const f = e.target.files[0]
  if (!f) return
  form.value.cover_photo = f
  previewCover.value = URL.createObjectURL(f)
}

function onPhotosFile(e) {
  Array.from(e.target.files).forEach(f => {
    newPhotoPreviews.value.push({ url: URL.createObjectURL(f), file: f })
    newPhotoCaptions.value.push("")
  })
  e.target.value = ""
}

function removeNewPhoto(i) {
  newPhotoPreviews.value.splice(i, 1)
  newPhotoCaptions.value.splice(i, 1)
}

function toggleDeletePhoto(id) {
  const idx = photosToDelete.value.indexOf(id)
  if (idx === -1) photosToDelete.value.push(id)
  else photosToDelete.value.splice(idx, 1)
}

async function save() {
  formError.value = ""
  if (!form.value.title.trim()) {
    formError.value = "Title is required."
    return
  }

  if ((form.value.type === 'item' || form.value.type === 'both') &&
      form.value.accepted_delivery_methods.length === 0) {
    formError.value = "Please select at least one accepted delivery method for item donations."
    return
  }

  if ((form.value.type === 'monetary' || form.value.type === 'both') &&
      form.value.accepted_payment_methods.length === 0) {
    formError.value = "Please select at least one accepted payment method for monetary donations."
    return
  }

  saving.value = true
  try {
    const fd = new FormData()
    fd.append("title",       form.value.title.trim())
    fd.append("description", form.value.description || "")
    if (form.value.category_id) fd.append("category_id", form.value.category_id)
    fd.append("type",        form.value.type)
    fd.append("goal_amount", form.value.goal_amount || 0)

    if (form.value.start_date) fd.append("start_date", form.value.start_date)
    if (form.value.end_date)   fd.append("end_date",   form.value.end_date)
    if (form.value.cover_photo instanceof File) fd.append("cover_photo", form.value.cover_photo)

    if (form.value.type === 'item' || form.value.type === 'both') {
      form.value.accepted_delivery_methods.forEach(m => {
        fd.append("accepted_delivery_methods[]", m)
      })
    }

    if (form.value.type === 'monetary' || form.value.type === 'both') {
      form.value.accepted_payment_methods.forEach(id => {
        fd.append("accepted_payment_methods[]", id)
      })
    }

    newPhotoPreviews.value.forEach((p, i) => {
      fd.append("photos[]",   p.file)
      fd.append("captions[]", newPhotoCaptions.value[i] || "")
    })

    photosToDelete.value.forEach(id => fd.append("delete_photos[]", id))

    if (editTarget.value) {
      await api.post(`/foundation/campaigns/${editTarget.value.id}`, fd)
    } else {
      await api.post("/foundation/campaigns", fd)
    }

    await load()
    showForm.value = false
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      formError.value = Object.values(errors).flat().join(" · ")
    } else {
      formError.value = err.response?.data?.message || "Failed to save campaign."
    }
  } finally {
    saving.value = false
  }
}

async function deleteCampaign() {
  deleting.value = true
  try {
    await api.delete(`/foundation/campaigns/${deleteTarget.value.id}`)
    campaigns.value = campaigns.value.filter(c => c.id !== deleteTarget.value.id)
    deleteTarget.value = null
  } catch (err) {
    console.error(err)
  } finally {
    deleting.value = false
  }
}

function paymentMethodName(id) {
  const found = paymentAccounts.value.find(p => p.payment_method_id === id)
  return found ? `${found.icon || '💳'} ${found.name}` : 'Payment method'
}

function percent(c) {
  if (!c.goal_amount) return 0
  return Math.min(Math.round((c.current_amount / c.goal_amount) * 100), 100)
}

function getImage(path) {
  if (!path) return ""
  return `/storage/${path}`
}

function formatMoney(v) { return Number(v || 0).toLocaleString() }

function formatDate(d) {
  if (!d) return null
  const date = new Date(d)
  if (isNaN(date)) return d
  return date.toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" })
}

function statusLabel(s) {
  return { active: "Active", completed: "Completed", draft: "Draft", cancelled: "Cancelled", paused: "Paused" }[s] || s
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.btn-add { display: flex; align-items: center; gap: 7px; padding: 10px 18px; background: #0F2D52; color: white; border: none; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-add:hover:not(:disabled) { background: #1a3f6b; }
.btn-add:disabled { opacity: 0.5; cursor: not-allowed; }

.stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }

.filters { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.tabs { display: flex; gap: 4px; background: white; padding: 4px; border-radius: 12px; border: 1px solid #e2e8f0; }
.tab-btn { display: flex; align-items: center; gap: 6px; padding: 7px 14px; border: none; background: transparent; color: #64748b; font-size: 0.83rem; font-weight: 500; border-radius: 8px; cursor: pointer; transition: all 0.15s; }
.tab-btn:hover  { background: #f1f5f9; color: #1e293b; }
.tab-btn.active { background: #0F2D52; color: white; }
.tab-count { font-size: 0.7rem; background: #f1f5f9; color: #64748b; padding: 1px 6px; border-radius: 20px; }
.tab-btn.active .tab-count { background: rgba(255,255,255,0.2); color: white; }

.search-box { display: flex; align-items: center; gap: 8px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 14px; color: #94a3b8; }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.85rem; width: 200px; color: #0F2D52; }

.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

.card { background: white; border-radius: 18px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(15,45,82,0.06); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
.card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(15,45,82,0.12); }

.cover { position: relative; height: 168px; background: #f1f5f9; overflow: hidden; }
.cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
.card:hover .cover img { transform: scale(1.04); }
.cover-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #cbd5e1; }

.badge { position: absolute; top: 12px; left: 12px; padding: 4px 10px; border-radius: 99px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: white; }
.badge.active    { background: #0284c7; }
.badge.completed { background: #059669; }
.badge.draft     { background: #64748b; }
.badge.cancelled { background: #dc2626; }
.badge.paused    { background: #ca8a04; }

.photo-chip { position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.55); color: white; font-size: 0.68rem; font-weight: 600; padding: 3px 8px; border-radius: 20px; display: flex; align-items: center; gap: 4px; }

.card-actions-overlay { position: absolute; top: 10px; right: 10px; display: flex; gap: 5px; opacity: 0; transition: opacity 0.2s; }
.card:hover .card-actions-overlay { opacity: 1; }
.ov-btn { width: 28px; height: 28px; border-radius: 8px; border: none; background: rgba(255,255,255,0.9); color: #0F2D52; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.ov-btn:hover          { background: white; transform: scale(1.1); }
.ov-btn.pause:hover    { background: #fefce8; color: #ca8a04; }
.ov-btn.resume:hover   { background: #f0fdf4; color: #059669; }
.ov-btn.complete:hover { background: #f5f3ff; color: #7c3aed; }
.ov-btn.danger         { color: #dc2626; }
.ov-btn.danger:hover   { background: #fef2f2; }

.body { padding: 16px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.card-meta { display: flex; justify-content: space-between; align-items: center; }
.date { font-size: 0.72rem; color: #94a3b8; }

.title { font-size: 0.95rem; font-weight: 700; color: #0F2D52; margin: 0; }
.desc  { font-size: 0.8rem; color: #64748b; line-height: 1.5; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.pause-reason { display: flex; align-items: flex-start; gap: 6px; background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 8px 10px; font-size: 0.75rem; color: #92400e; line-height: 1.4; }

.card-delivery { display: flex; gap: 6px; flex-wrap: wrap; }
.delivery-badge { font-size: 0.7rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.delivery-badge.dropoff { background: #f0fdf4; color: #059669; }
.delivery-badge.pickup  { background: #eff6ff; color: #3b82f6; }

.dates-row { display: flex; align-items: center; gap: 6px; background: #f8fafc; border-radius: 8px; padding: 7px 10px; }
.date-item { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: #475569; }
.date-arrow { font-size: 0.75rem; color: #94a3b8; }

.progress-wrap { background: #f8fafc; border-radius: 10px; padding: 10px 12px; }
.progress-row  { display: flex; align-items: baseline; gap: 4px; margin-bottom: 6px; }
.raised { font-size: 0.95rem; font-weight: 700; color: #0F2D52; }
.goal   { font-size: 0.78rem; color: #94a3b8; }
.bar    { height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-bottom: 5px; }
.fill   { height: 100%; background: linear-gradient(90deg, #0F2D52, #3b82f6); border-radius: 99px; transition: width 0.5s ease; }
.progress-foot { display: flex; justify-content: space-between; }
.pct    { font-size: 0.72rem; font-weight: 600; color: #0F2D52; }
.donors { font-size: 0.72rem; color: #94a3b8; }

.view-btn { margin-top: auto; padding: 10px; border: none; border-radius: 10px; background: #0F2D52; color: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.view-btn:hover { background: #1a3f6b; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
.req { color: #dc2626; }
.label-hint { font-size: 0.75rem; font-weight: 400; color: #94a3b8; margin-left: 6px; }
.field-hint { font-size: 0.78rem; color: #94a3b8; margin: -4px 0 12px; line-height: 1.5; }

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: 0.87rem; outline: none; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus { border-color: #0F2D52; box-shadow: 0 0 0 3px rgba(15,45,82,0.08); }
.form-group textarea { height: 90px; resize: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

.delivery-options { display: flex; flex-direction: column; gap: 10px; }

.delivery-opt {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.15s;
  background: white;
  user-select: none;
}
.delivery-opt:hover  { border-color: #0F2D52; background: #f8fafc; }
.delivery-opt.active { border-color: #0F2D52; background: #f0f6ff; }

.delivery-opt-icon { font-size: 1.5rem; flex-shrink: 0; line-height: 1; }
.delivery-opt-body { flex: 1; }
.delivery-opt-title { font-size: 0.88rem; font-weight: 700; color: #0F2D52; margin-bottom: 2px; }
.delivery-opt-desc  { font-size: 0.75rem; color: #64748b; line-height: 1.4; }

.delivery-check {
  width: 22px; height: 22px; border-radius: 50%;
  background: #0F2D52; color: white;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.payment-options { display: flex; flex-direction: column; gap: 10px; }

.payment-opt {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.15s;
  background: white;
  user-select: none;
}
.payment-opt:hover  { border-color: #0F2D52; background: #f8fafc; }
.payment-opt.active { border-color: #0F2D52; background: #f0f6ff; }

.payment-opt-icon { font-size: 1.5rem; flex-shrink: 0; line-height: 1; }
.payment-opt-body { flex: 1; min-width: 0; }
.payment-opt-title { font-size: 0.88rem; font-weight: 700; color: #0F2D52; margin-bottom: 2px; }
.payment-opt-desc  { font-size: 0.75rem; color: #64748b; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.payment-empty-hint {
  display: flex; align-items: flex-start; gap: 8px;
  background: #fefce8; border: 1px solid #fde68a;
  border-radius: 10px; padding: 12px 14px;
  font-size: 0.8rem; color: #92400e; line-height: 1.6;
}
.payment-empty-hint svg { flex-shrink: 0; margin-top: 2px; }
.payment-setup-link { color: #0F2D52; font-weight: 700; text-decoration: underline; }

.upload-area { border: 2px dashed #e2e8f0; border-radius: 12px; overflow: hidden; cursor: pointer; transition: border-color 0.2s; min-height: 80px; }
.upload-area:hover { border-color: #0F2D52; }
.upload-preview { width: 100%; height: 160px; object-fit: cover; display: block; }
.upload-placeholder { height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: #94a3b8; font-size: 0.83rem; }

.photo-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.photo-thumb { position: relative; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; aspect-ratio: 4/3; background: #f8fafc; }
.photo-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.thumb-del { position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; border-radius: 50%; border: none; background: rgba(0,0,0,0.55); color: white; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; }
.thumb-caption { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.45); color: white; font-size: 0.65rem; padding: 3px 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.marked-delete { opacity: 0.4; outline: 2px solid #dc2626; }
.caption-input { position: absolute; bottom: 0; left: 0; right: 0; border: none; background: rgba(0,0,0,0.55); color: white; font-size: 0.68rem; padding: 4px 6px; outline: none; width: 100%; box-sizing: border-box; }
.caption-input::placeholder { color: rgba(255,255,255,0.6); }

.btn-upload-more { display: flex; align-items: center; gap: 6px; margin-top: 10px; padding: 8px 14px; border: 1px dashed #cbd5e1; background: transparent; color: #64748b; border-radius: 8px; font-size: 0.82rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
.btn-upload-more:hover { border-color: #0F2D52; color: #0F2D52; background: #f8fafc; }

.error-text { font-size: 0.82rem; color: #cc3333; background: #fff5f5; border: 1px solid #ffd0d0; border-radius: 8px; padding: 9px 13px; margin-bottom: 4px; }

.btn-cancel { padding: 9px 18px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-save   { padding: 9px 20px; border: none; background: #0F2D52; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 100px; }
.btn-save:hover:not(:disabled) { background: #1a3f6b; }
.btn-save:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-danger { padding: 9px 20px; border: none; background: #dc2626; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 80px; }
.btn-danger:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-pause-confirm { padding: 9px 18px; border: none; background: #ca8a04; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; min-width: 130px; }
.btn-pause-confirm:hover:not(:disabled) { background: #b45309; }
.btn-pause-confirm:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-resume { padding: 9px 18px; border: none; background: #059669; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-resume:hover { background: #047857; }
.btn-complete { padding: 9px 18px; border: none; background: #7c3aed; color: white; border-radius: 10px; font-size: 0.87rem; font-weight: 600; cursor: pointer; }
.btn-complete:hover { background: #6d28d9; }

.pause-modal-desc { font-size: 0.85rem; color: #475569; margin: 0 0 16px; line-height: 1.6; }

/* MARK COMPLETE MODAL */
.complete-modal-body { display: flex; gap: 14px; align-items: flex-start; }
.complete-icon {
  width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
  background: #f5f3ff; color: #7c3aed;
  display: flex; align-items: center; justify-content: center;
}
.complete-modal-text { font-size: 0.87rem; color: #475569; line-height: 1.65; margin: 0; padding-top: 6px; }
.complete-modal-text strong { color: #1e293b; }

.btn-complete-confirm {
  padding: 9px 20px; border: none; background: #7c3aed; color: white; border-radius: 10px;
  font-size: 0.87rem; font-weight: 600; cursor: pointer;
  display: flex; align-items: center; justify-content: center; min-width: 130px;
  transition: background 0.2s;
}
.btn-complete-confirm:hover:not(:disabled) { background: #6d28d9; }
.btn-complete-confirm:disabled { opacity: 0.65; cursor: not-allowed; }

/* ══════════════════════════════
   VIEW DETAILS MODAL — professional redesign
══════════════════════════════ */
.vd-cover { position: relative; height: 220px; border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.vd-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
.vd-cover-gradient { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 45%, rgba(0,0,0,0.55) 100%); }
.vd-cover-badges { position: absolute; bottom: 12px; left: 12px; display: flex; gap: 8px; }

.vd-header-noCover { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.vd-category-row { margin: -8px 0 16px; }

.vd-pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
.vd-status { color: white; }
.vd-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.vd-status.active    { background: #0284c7; }
.vd-status.completed { background: #059669; }
.vd-status.draft     { background: #64748b; }
.vd-status.cancelled { background: #dc2626; }
.vd-status.paused    { background: #ca8a04; }
.vd-type     { background: rgba(255,255,255,0.92); color: #0F2D52; }
.vd-header-noCover .vd-type { background: #eff6ff; color: #3b82f6; }
.vd-category { background: #f5f3ff; color: #7c3aed; }

.vd-stats { display: flex; align-items: center; background: #f8fafc; border-radius: 14px; padding: 16px 20px; margin-bottom: 10px; }
.vd-stat { flex: 1; display: flex; flex-direction: column; gap: 3px; text-align: center; }
.vd-stat-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.vd-stat-value { font-size: 1.05rem; font-weight: 800; color: #0F2D52; }
.vd-stat-value.raised { color: #059669; }
.vd-stat-value.pct { color: #3b82f6; }
.vd-stat-divider { width: 1px; height: 32px; background: #e2e8f0; }

.vd-progress-bar { height: 8px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-bottom: 20px; }
.vd-progress-fill { height: 100%; background: linear-gradient(90deg, #0F2D52, #3b82f6); border-radius: 99px; transition: width 0.5s ease; }

.vd-info-list { display: flex; flex-direction: column; background: white; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; margin-bottom: 18px; }
.vd-info-row { display: grid; grid-template-columns: 28px 100px 1fr; align-items: start; gap: 10px; padding: 13px 16px; border-bottom: 1px solid #f1f5f9; }
.vd-info-row:last-child { border-bottom: none; }
.vd-info-icon { font-size: 0.95rem; line-height: 1.4; }
.vd-info-label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.03em; padding-top: 2px; }
.vd-info-value { font-size: 0.86rem; color: #1e293b; line-height: 1.5; }
.vd-info-row-desc .vd-info-value { white-space: pre-wrap; }
.vd-pause-text { color: #92400e; }
.vd-muted { color: #94a3b8; font-size: 0.83rem; }

.vd-tag-row { display: flex; gap: 6px; flex-wrap: wrap; }
.vd-tag { font-size: 0.72rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.vd-tag.dropoff { background: #f0fdf4; color: #059669; }
.vd-tag.pickup  { background: #eff6ff; color: #3b82f6; }
.vd-tag.payment { background: #f5f3ff; color: #7c3aed; }

.vd-section { margin-bottom: 18px; }
.vd-section-label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; }

.view-photo-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.view-photo { position: relative; border-radius: 10px; overflow: hidden; aspect-ratio: 4/3; cursor: pointer; background: #f1f5f9; }
.view-photo img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s; }
.view-photo:hover img { transform: scale(1.05); }
.view-photo-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0); display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.view-photo:hover .view-photo-overlay { background: rgba(0,0,0,0.3); }
.view-photo-caption { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.5); color: white; font-size: 0.68rem; padding: 4px 8px; }
</style>