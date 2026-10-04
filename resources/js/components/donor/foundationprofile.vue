<template>
  <div class="page">

    <!-- LOADING -->
    <div v-if="isLoading" class="loading-state"><Spinner :size="40" /></div>

    <EmptyState
      v-else-if="!foundation"
      icon="" message="Foundation not found."
      action-label="← Go Back" action-to="/donor/dashboard" :card="false"
    />

    <div v-else>

      <!-- COVER -->
      <div class="cover-section">
        <div
          class="cover-bg"
          :style="foundation.cover_photo
            ? `background-image: url(${getImage(foundation.cover_photo)})`
            : 'background: linear-gradient(135deg, #0e5c36, #1a8a52)'"
        ></div>
        <div class="cover-overlay"></div>

        <div class="cover-content">
          <button class="btn-back-top" @click="$router.back()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Back
          </button>

          <div class="foundation-header">
            <div class="fh-logo">
              <img v-if="foundation.logo" :src="getImage(foundation.logo)" :alt="foundation.name" />
              <span v-else>{{ foundation.initials }}</span>
            </div>
            <div class="fh-info">
              <h1 class="fh-name">{{ foundation.name }}</h1>
              <div class="fh-meta">
                <span v-if="foundation.category" class="fh-category">{{ foundation.category }}</span>
                <span v-if="foundation.city_municipality" class="fh-location">
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                  </svg>
                  {{ foundation.city_municipality }}{{ foundation.province ? ', ' + foundation.province : '' }}
                </span>
                <span class="fh-verified">✓ Verified</span>
                <span v-if="followersCount > 0" class="fh-followers">
                  {{ followersCount }} {{ followersCount === 1 ? 'follower' : 'followers' }}
                </span>
              </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="fh-actions">

              <!-- LIKE BUTTON -->
              <button
                class="btn-like"
                :class="{ liked: isLiked }"
                :disabled="likeLoading"
                @click="toggleLike"
              >
                <svg v-if="!isLiked" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <svg v-else width="15" height="15" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span v-if="!likeLoading">{{ likesCount }}</span>
                <span v-else class="spinner-sm" />
              </button>

              <!-- FOLLOW BUTTON -->
              <button
                class="btn-follow"
                :class="{ following: isFollowing }"
                :disabled="followLoading"
                @click="toggleFollow"
              >
                <svg v-if="!isFollowing" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <svg v-else width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span v-if="!followLoading">{{ isFollowing ? 'Following' : 'Follow' }}</span>
                <span v-else class="spinner-sm" />
              </button>

            </div>
          </div>
        </div>
      </div>

      <div class="container">

        <!-- STATS -->
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-val">{{ foundation.total_campaigns }}</div>
            <div class="stat-label">Total Campaigns</div>
          </div>
          <div class="stat-card">
            <div class="stat-val green">{{ foundation.active_campaigns }}</div>
            <div class="stat-label">Active Now</div>
          </div>
          <div class="stat-card">
            <div class="stat-val">{{ foundation.completed_campaigns }}</div>
            <div class="stat-label">Completed</div>
          </div>
          <div class="stat-card">
            <div class="stat-val green">₱{{ formatMoney(foundation.total_raised) }}</div>
            <div class="stat-label">Total Raised</div>
          </div>
          <div class="stat-card">
            <div class="stat-val">{{ formatDate(foundation.member_since) }}</div>
            <div class="stat-label">Member Since</div>
          </div>
        </div>

        <!-- ABOUT -->
        <div class="panel" v-if="foundation.description">
          <div class="panel-head">
            <h3 class="panel-title">About</h3>
          </div>
          <div class="panel-body">
            <p class="about-text">{{ foundation.description }}</p>
            <div class="about-address" v-if="foundation.full_address">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
              </svg>
              {{ foundation.full_address }}
            </div>
          </div>
        </div>

        <!-- CAMPAIGNS -->
        <div class="panel" style="margin-top: 20px;">
          <div class="panel-head">
            <h3 class="panel-title">Campaigns</h3>
            <div class="camp-tabs">
              <button
                v-for="t in campTabs" :key="t.key"
                class="ct-btn"
                :class="{ active: campTab === t.key }"
                @click="campTab = t.key"
              >
                {{ t.label }}
                <span class="ct-count">{{ campCount(t.key) }}</span>
              </button>
            </div>
          </div>

          <div class="panel-body">

            <!-- EMPTY -->
            <EmptyState
              v-if="filteredCampaigns.length === 0"
              icon="📋" :message="`No ${campTab === 'all' ? '' : campTab} campaigns yet.`" :card="false"
            />

            <!-- CAMPAIGN LIST -->
            <div v-else class="camp-list">
              <div class="camp-item" v-for="c in filteredCampaigns" :key="c.id">

                <div class="camp-item-main">
                  <!-- COVER -->
                  <div class="ci-cover">
                    <img v-if="c.cover_photo" :src="getImage(c.cover_photo)" alt="" />
                    <div v-else class="ci-cover-placeholder">📢</div>
                    <span class="ci-status" :class="c.status">{{ statusLabel(c.status) }}</span>
                  </div>

                  <!-- INFO -->
                  <div class="ci-info">
                    <div class="ci-type-row">
                      <span class="ci-type" :class="c.type">{{ c.type }}</span>
                      <span class="ci-dates" v-if="c.start_date">
                        {{ formatDate(c.start_date) }}
                        <span v-if="c.end_date"> → {{ formatDate(c.end_date) }}</span>
                      </span>
                    </div>

                    <h4 class="ci-title">{{ c.title }}</h4>
                    <p class="ci-desc">{{ c.description }}</p>

                    <!-- PAUSE REASON -->
                    <div v-if="c.status === 'paused' && c.pause_reason" class="ci-pause">
                      ⏸ {{ c.pause_reason }}
                    </div>

                    <!-- PROGRESS -->
                    <div v-if="c.type !== 'item' && c.goal_amount > 0" class="ci-progress">
                      <div class="cp-info">
                        <span class="cp-raised">₱{{ formatMoney(c.current_amount) }}</span>
                        <span class="cp-goal">of ₱{{ formatMoney(c.goal_amount) }}</span>
                        <span class="cp-pct">{{ c.percent }}%</span>
                      </div>
                      <div class="cp-bar">
                        <div
                          class="cp-fill"
                          :style="{ width: c.percent + '%', background: progressColor(c.percent) }"
                        ></div>
                      </div>
                    </div>

                    <!-- PHOTOS -->
                    <div v-if="c.photos?.length" class="ci-photos">
                      <div
                        v-for="p in c.photos.slice(0, 4)" :key="p.id"
                        class="ci-photo"
                        @click="lightbox = getImage(p.photo_path)"
                      >
                        <img :src="getImage(p.photo_path)" alt="" />
                        <div
                          v-if="c.photos.length > 4 && c.photos.indexOf(p) === 3"
                          class="ci-photo-more"
                        >+{{ c.photos.length - 4 }}</div>
                      </div>
                    </div>
                  </div>

                  <!-- ACTIONS -->
                  <div class="ci-action">
                    <button class="btn-view" @click="openDetails(c)">View Details</button>
                    <button
                      v-if="c.status === 'active'"
                      class="btn-donate"
                      @click="openDonate(c)"
                    >Donate Now</button>
                    <span v-else-if="c.status === 'paused'" class="ci-paused-label">Temporarily Paused</span>
                    <span v-else-if="c.status === 'completed'" class="ci-completed-label">✓ Completed</span>
                  </div>
                </div>

                <!-- UPDATES FOR THIS CAMPAIGN -->
                <div v-if="c.updates?.length" class="ci-updates">
                  <button class="ci-updates-toggle" @click="toggleUpdates(c.id)">
                    <span>📰 {{ c.updates.length }} update{{ c.updates.length === 1 ? '' : 's' }}</span>
                    <span class="ci-updates-arrow">{{ isExpanded(c.id) ? '▲' : '▼' }}</span>
                  </button>

                  <div v-if="isExpanded(c.id)" class="ci-updates-body">
                    <article class="mini-update" v-for="u in c.updates" :key="u.id">
                      <h5 class="mu-title">{{ u.title }}</h5>
                      <span class="mu-date">{{ u.posted_at }}</span>
                      <p v-if="u.content" class="mu-desc">{{ u.content }}</p>

                      <div v-if="u.photos?.length" class="mu-hero" @click="lightbox = getImage(u.photos[0])">
                        <img :src="getImage(u.photos[0])" alt="" />
                      </div>
                      <div v-if="u.photos?.length > 1" class="mu-thumb-strip">
                        <div v-for="(photo, i) in u.photos.slice(1, 5)" :key="i" class="mu-thumb" @click="lightbox = getImage(photo)">
                          <img :src="getImage(photo)" alt="" />
                          <div v-if="u.photos.length > 5 && i === 3" class="mu-thumb-more">+{{ u.photos.length - 5 }}</div>
                        </div>
                      </div>

                      <ReactionButton :liked="!!u.my_reaction" :count="u.total_reactions" @toggle="toggleReaction(u)" />
                    </article>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>

      </div>
    </div>

    <!-- VIEW DETAILS MODAL -->
    <CampaignDetailsModal
      v-if="detailsTarget"
      :campaign="detailsTarget"
      @close="detailsTarget = null"
      @donate="onDonateFromDetails"
    />

    <!-- DONATE MODAL -->
    <DonateModal
      v-if="donateTarget"
      :campaign="donateTarget"
      @close="donateTarget = null"
      @donated="donateTarget = null"
    />

    <!-- LIGHTBOX -->
    <Lightbox v-model="lightbox" />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../services/api.js'
import DonateModal from '../../components/donor/donatemodal.vue'
import CampaignDetailsModal from '../../components/donor/ViewDetail.vue'
import Spinner from '../../components/donor/spinner.vue'
import EmptyState from '../../components/donor/emptystate.vue'
import Lightbox from '../../components/donor/lightbox.vue'
import ReactionButton from '../../components/donor/reactionbutton.vue'
import { getImage, formatMoney, formatDate } from '../../composable/donor.js'

const route       = useRoute()
const isLoading   = ref(false)
const foundation  = ref(null)
const donateTarget = ref(null)
const detailsTarget = ref(null)
const lightbox    = ref(null)
const campTab     = ref('all')
const expandedUpdates = ref([])

const isFollowing    = ref(false)
const followersCount = ref(0)
const followLoading  = ref(false)

const isLiked     = ref(false)
const likesCount  = ref(0)
const likeLoading = ref(false)

const campTabs = [
  { key: 'all',       label: 'All'       },
  { key: 'active',    label: 'Active'    },
  { key: 'completed', label: 'Completed' },
  { key: 'paused',    label: 'Paused'    },
]

async function load() {
  isLoading.value = true
  try {
    const res = await api.get(`/donor/foundations/${route.params.id}`)
    foundation.value = res.data
  } catch (err) {
    console.error(err)
    foundation.value = null
  } finally {
    isLoading.value = false
  }
}

async function loadFollowStatus() {
  try {
    const res = await api.get(`/donor/foundations/${route.params.id}/follow-status`)
    isFollowing.value = !!res.data.following
    followersCount.value = res.data.followers_count ?? 0
    isLiked.value = !!res.data.liked
    likesCount.value = res.data.likes_count ?? 0
  } catch (err) {
    console.error(err)
  }
}

async function toggleFollow() {
  if (followLoading.value) return
  followLoading.value = true
  try {
    if (isFollowing.value) {
      const res = await api.delete(`/donor/foundations/${route.params.id}/follow`)
      isFollowing.value = false
      followersCount.value = res.data.followers_count ?? Math.max(0, followersCount.value - 1)
    } else {
      const res = await api.post(`/donor/foundations/${route.params.id}/follow`)
      isFollowing.value = true
      followersCount.value = res.data.followers_count ?? followersCount.value + 1
    }
  } catch (err) {
    console.error(err)
  } finally {
    followLoading.value = false
  }
}

async function toggleLike() {
  if (likeLoading.value) return
  likeLoading.value = true
  try {
    if (isLiked.value) {
      const res = await api.delete(`/donor/foundations/${route.params.id}/like`)
      isLiked.value = false
      likesCount.value = res.data.likes_count ?? Math.max(0, likesCount.value - 1)
    } else {
      const res = await api.post(`/donor/foundations/${route.params.id}/like`)
      isLiked.value = true
      likesCount.value = res.data.likes_count ?? likesCount.value + 1
    }
  } catch (err) {
    console.error(err)
  } finally {
    likeLoading.value = false
  }
}

async function toggleReaction(u) {
  // optimistic update
  u.my_reaction = !u.my_reaction
  u.total_reactions += u.my_reaction ? 1 : -1

  try {
    const res = await api.post(`/donor/campaigns/${u.campaign_id}/updates/${u.id}/react`)
    u.my_reaction = res.data.reacted
    u.total_reactions = res.data.total_reactions
  } catch (err) {
    // revert on failure
    u.my_reaction = !u.my_reaction
    u.total_reactions += u.my_reaction ? 1 : -1
    console.error(err)
  }
}

function isExpanded(campaignId) {
  return expandedUpdates.value.includes(campaignId)
}

function toggleUpdates(campaignId) {
  if (isExpanded(campaignId)) {
    expandedUpdates.value = expandedUpdates.value.filter(id => id !== campaignId)
  } else {
    expandedUpdates.value = [...expandedUpdates.value, campaignId]
  }
}

onMounted(() => {
  load()
  loadFollowStatus()
})

const filteredCampaigns = computed(() => {
  if (!foundation.value?.campaigns) return []
  if (campTab.value === 'all') return foundation.value.campaigns
  return foundation.value.campaigns.filter(c => c.status === campTab.value)
})

function campCount(key) {
  if (!foundation.value?.campaigns) return 0
  if (key === 'all') return foundation.value.campaigns.length
  return foundation.value.campaigns.filter(c => c.status === key).length
}

function withFoundation(c) {
  return {
    ...c,
    foundation: {
      id:   foundation.value.id,
      name: foundation.value.name,
      logo: foundation.value.logo,
    }
  }
}

function openDonate(c) {
  donateTarget.value = withFoundation(c)
}

function openDetails(c) {
  detailsTarget.value = withFoundation(c)
}

// Called when the user clicks "Donate Now" from inside the details modal
function onDonateFromDetails(c) {
  detailsTarget.value = null
  donateTarget.value  = c
}

function statusLabel(s) {
  return { active: 'Active', completed: 'Completed', draft: 'Draft', cancelled: 'Cancelled', paused: 'Paused' }[s] || s
}

function progressColor(pct) {
  if (pct >= 80) return '#059669'
  if (pct >= 50) return '#0e5c36'
  return '#1a8a52'
}
</script>

<style scoped>
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 40px; }

.loading-state { display: flex; justify-content: center; padding: 80px; }

/* COVER */
.cover-section { position: relative; min-height: 280px; overflow: hidden; padding-bottom: 8px; }
.cover-bg      { position: absolute; inset: 0; background-size: cover; background-position: center; }
.cover-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.6) 100%); }

.cover-content { position: relative; z-index: 2; min-height: 280px; padding: 20px 32px; display: flex; flex-direction: column; justify-content: space-between; max-width: 1200px; margin: 0 auto; box-sizing: border-box; }

.btn-back-top { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; align-self: flex-start; transition: background 0.2s; }
.btn-back-top:hover { background: rgba(255,255,255,0.25); }

.foundation-header { display: flex; align-items: center; gap: 20px; }

.fh-logo { width: 80px; height: 80px; border-radius: 20px; background: white; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 900; color: #0e5c36; border: 3px solid white; overflow: hidden; flex-shrink: 0; }
.fh-logo img { width: 100%; height: 100%; object-fit: cover; }

.fh-info { flex: 1; min-width: 0; }
.fh-name { font-size: 1.6rem; font-weight: 900; color: white; margin: 0 0 8px; }
.fh-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

.fh-category { font-size: 0.78rem; font-weight: 700; background: rgba(255,255,255,0.2); color: white; padding: 3px 10px; border-radius: 20px; }
.fh-location  { display: flex; align-items: center; gap: 4px; font-size: 0.8rem; color: rgba(255,255,255,0.85); }
.fh-verified  { font-size: 0.78rem; font-weight: 700; background: #f0fdf4; color: #059669; padding: 3px 10px; border-radius: 20px; }
.fh-followers { font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.85); }

/* ACTION BUTTONS WRAPPER */
.fh-actions {
  display: flex;
  align-items: stretch;
  gap: 10px;
  flex-shrink: 0;
  align-self: flex-end;
}

/* LIKE BUTTON */
.btn-like {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  height: 42px;
  min-width: 64px;
  padding: 0 16px;
  border: 1.5px solid rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(8px);
  color: white;
  border-radius: 12px;
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s, color 0.2s, border-color 0.2s;
  flex-shrink: 0;
  white-space: nowrap;
  box-sizing: border-box;
}
.btn-like:hover:not(:disabled) { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
.btn-like:disabled { opacity: 0.7; cursor: default; transform: none; }
.btn-like.liked {
  background: #fdf2f8;
  color: #db2777;
  border-color: #fbcfe8;
}
.btn-like.liked:hover:not(:disabled) { background: #fce7f3; }

/* FOLLOW BUTTON */
.btn-follow {
  display: flex; align-items: center; justify-content: center; gap: 7px;
  height: 42px;
  min-width: 110px;
  padding: 0 20px;
  border: 1.5px solid rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(8px);
  color: white;
  border-radius: 12px;
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s, color 0.2s, border-color 0.2s;
  flex-shrink: 0;
  white-space: nowrap;
  box-sizing: border-box;
}
.btn-follow:hover:not(:disabled) { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
.btn-follow:disabled { opacity: 0.7; cursor: default; transform: none; }
.btn-follow.following {
  background: white;
  color: #0e5c36;
  border-color: white;
}
.btn-follow.following:hover:not(:disabled) { background: #f0fdf4; }

.spinner-sm {
  width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
.btn-follow.following .spinner-sm { border-color: rgba(14,92,54,0.3); border-top-color: #0e5c36; }
.btn-like.liked .spinner-sm { border-color: rgba(219,39,119,0.3); border-top-color: #db2777; }

/* CONTAINER */
.container { max-width: 1200px; margin: 0 auto; padding: 24px 24px; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(5,1fr); gap: 12px; margin-bottom: 20px; }
.stat-card { background: white; border-radius: 14px; padding: 16px; text-align: center; box-shadow: 0 2px 8px rgba(0,80,40,0.08); }
.stat-val  { font-size: 1.1rem; font-weight: 900; color: #0F2D52; margin-bottom: 4px; }
.stat-val.green { color: #059669; }
.stat-label { font-size: 0.72rem; color: #94a3b8; }

/* PANEL */
.panel { background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,80,40,0.08); overflow: hidden; }
.panel-head { display: flex; justify-content: space-between; align-items: center; padding: 16px 22px 12px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px; }
.panel-title { font-size: 0.95rem; font-weight: 800; color: #0F2D52; margin: 0; }
.panel-body  { padding: 22px; }

/* ABOUT */
.about-text    { font-size: 0.9rem; color: #475569; line-height: 1.7; margin: 0 0 12px; }
.about-address { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #94a3b8; }

/* CAMP TABS */
.camp-tabs { display: flex; gap: 4px; background: #f8fafc; padding: 4px; border-radius: 10px; }
.ct-btn { display: flex; align-items: center; gap: 5px; padding: 6px 12px; border: none; background: transparent; color: #64748b; font-size: 0.8rem; font-weight: 600; border-radius: 7px; cursor: pointer; transition: all 0.15s; }
.ct-btn:hover  { background: white; color: #1a8a52; }
.ct-btn.active { background: #1a8a52; color: white; }
.ct-count { font-size: 0.68rem; background: rgba(255,255,255,0.2); padding: 1px 5px; border-radius: 20px; }
.ct-btn:not(.active) .ct-count { background: #e2e8f0; color: #64748b; }

/* CAMP LIST */
.camp-list { display: flex; flex-direction: column; gap: 20px; }

.camp-item { background: #f8fafc; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; transition: border-color 0.2s; }
.camp-item:hover { border-color: #1a8a52; }

.camp-item-main { display: grid; grid-template-columns: 200px 1fr auto; gap: 20px; }

/* CI COVER */
.ci-cover { position: relative; height: 100%; min-height: 160px; background: #f0fdf4; overflow: hidden; }
.ci-cover img { width: 100%; height: 100%; object-fit: cover; }
.ci-cover-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; min-height: 160px; }

.ci-status { position: absolute; top: 10px; left: 10px; font-size: 0.68rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; text-transform: uppercase; color: white; }
.ci-status.active    { background: #059669; }
.ci-status.completed { background: #3b82f6; }
.ci-status.paused    { background: #ca8a04; }
.ci-status.draft     { background: #64748b; }
.ci-status.cancelled { background: #dc2626; }

/* CI INFO */
.ci-info { padding: 16px 0; flex: 1; }

.ci-type-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.ci-type { font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: capitalize; }
.ci-type.monetary { background: #f0fdf4; color: #059669; }
.ci-type.item     { background: #eff6ff; color: #3b82f6; }
.ci-type.both     { background: #f5f3ff; color: #7c3aed; }
.ci-dates { font-size: 0.72rem; color: #94a3b8; }

.ci-title { font-size: 1rem; font-weight: 800; color: #0F2D52; margin: 0 0 6px; }
.ci-desc  { font-size: 0.82rem; color: #64748b; line-height: 1.5; margin: 0 0 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.ci-pause { font-size: 0.78rem; color: #92400e; background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 6px 10px; margin-bottom: 10px; }

/* CI PROGRESS */
.ci-progress { margin-bottom: 12px; }
.cp-info  { display: flex; align-items: baseline; gap: 6px; margin-bottom: 5px; }
.cp-raised { font-size: 0.95rem; font-weight: 800; color: #0e5c36; }
.cp-goal  { font-size: 0.75rem; color: #94a3b8; }
.cp-pct   { font-size: 0.72rem; font-weight: 700; color: #1a8a52; margin-left: auto; }
.cp-bar   { height: 7px; background: #dcfce7; border-radius: 99px; overflow: hidden; }
.cp-fill  { height: 100%; border-radius: 99px; transition: width 0.5s; }

/* CI PHOTOS */
.ci-photos { display: grid; grid-template-columns: repeat(4,1fr); gap: 6px; }
.ci-photo  { position: relative; aspect-ratio: 1; border-radius: 8px; overflow: hidden; cursor: pointer; background: #e2e8f0; }
.ci-photo img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s; }
.ci-photo:hover img { transform: scale(1.06); }
.ci-photo-more { position: absolute; inset: 0; background: rgba(0,0,0,0.5); color: white; font-size: 1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }

/* CI ACTION */
.ci-action { padding: 16px; display: flex; flex-direction: column; gap: 8px; align-items: stretch; justify-content: center; min-width: 150px; }
.btn-view { padding: 10px 18px; border: 1.5px solid #e2e8f0; background: white; color: #0F2D52; border-radius: 10px; font-size: 0.85rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: border-color 0.2s, background 0.2s; }
.btn-view:hover { border-color: #1a8a52; background: #f0fdf4; }
.btn-donate { padding: 10px 18px; border: none; background: #1a8a52; color: white; border-radius: 10px; font-size: 0.85rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background 0.2s; }
.btn-donate:hover { background: #157042; }
.ci-paused-label    { font-size: 0.78rem; font-weight: 700; color: #ca8a04; background: #fefce8; padding: 6px 12px; border-radius: 10px; white-space: nowrap; text-align: center; }
.ci-completed-label { font-size: 0.78rem; font-weight: 700; color: #059669; background: #f0fdf4; padding: 6px 12px; border-radius: 10px; white-space: nowrap; text-align: center; }

/* CAMPAIGN UPDATES (nested) */
.ci-updates { border-top: 1px solid #e2e8f0; }
.ci-updates-toggle {
  width: 100%; display: flex; align-items: center; justify-content: space-between;
  padding: 12px 20px; border: none; background: transparent; cursor: pointer;
  font-size: 0.82rem; font-weight: 700; color: #1a8a52; transition: background 0.15s;
}
.ci-updates-toggle:hover { background: #eefdf3; }
.ci-updates-arrow { font-size: 0.7rem; color: #94a3b8; }

.ci-updates-body { padding: 0 20px 18px; display: flex; flex-direction: column; gap: 16px; }

.mini-update { border-top: 1px dashed #e2e8f0; padding-top: 14px; }
.mini-update:first-child { border-top: none; padding-top: 0; }

.mu-title { font-size: 0.9rem; font-weight: 800; color: #0F2D52; margin: 0 0 2px; display: inline; }
.mu-date  { font-size: 0.72rem; color: #94a3b8; margin-left: 8px; }
.mu-desc  { font-size: 0.84rem; color: #475569; line-height: 1.6; margin: 6px 0 10px; }

.mu-hero { border-radius: 10px; overflow: hidden; cursor: pointer; margin-bottom: 8px; aspect-ratio: 16/9; background: #e2e8f0; max-width: 420px; }
.mu-hero img { width: 100%; height: 100%; object-fit: cover; }

.mu-thumb-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 10px; max-width: 420px; }
.mu-thumb { position: relative; aspect-ratio: 1; border-radius: 8px; overflow: hidden; cursor: pointer; background: #e2e8f0; }
.mu-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s; }
.mu-thumb:hover img { transform: scale(1.06); }
.mu-thumb-more { position: absolute; inset: 0; background: rgba(0,0,0,0.5); color: white; font-size: 0.85rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2,1fr); }
  .camp-item-main { grid-template-columns: 1fr; }
  .ci-cover  { min-height: 180px; }
  .foundation-header { flex-wrap: wrap; align-items: flex-start; }
  .fh-info { width: 100%; }
  .fh-actions { width: 100%; align-self: stretch; margin-top: 4px; }
  .btn-follow { flex: 1; }
  .btn-like { flex: 0 0 auto; }
}
</style>