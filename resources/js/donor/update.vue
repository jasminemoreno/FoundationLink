<template>
  <div class="page">
    <div class="container">

      <div class="page-head">
        <span class="kicker">Donor Feed</span>
        <h1>Campaign Updates</h1>
        <p>See the latest stories from foundations and campaigns you've supported</p>
      </div>

      <!-- SWITCH -->
      <div class="switch">
        <button :class="{ active: filter === 'all' }" @click="changeFilter('all')">All Updates</button>
        <button :class="{ active: filter === 'followed' }" @click="changeFilter('followed')">Followed Foundations</button>
        <button :class="{ active: filter === 'donated' }" @click="changeFilter('donated')">Donated Campaigns</button>
      </div>

      <div v-if="isLoading" class="loading-wrap"><Spinner /></div>

      <EmptyState
        v-else-if="updates.length === 0"
        icon="📰" :message="emptyMessage"
        action-label="Browse Campaigns" action-to="/donor/campaigns"
      />

      <div v-else class="updates-feed">
        <article
          class="update-card"
          :class="{ highlighted: highlightId === u.id }"
          :data-update-id="u.id"
          v-for="u in updates" :key="u.id"
        >

          <div v-if="u.photos?.length" class="update-hero" @click="lightbox = getImage(u.photos[0])">
            <img :src="getImage(u.photos[0])" alt="" />
            <div class="hero-overlay"></div>
            <span class="hero-tag">{{ u.campaign.title }}</span>
          </div>

          <div class="update-body" :class="{ 'no-hero': !u.photos?.length }">

            <div class="update-meta">
              <div class="foundation-tag">
                <img v-if="u.foundation.logo" :src="getImage(u.foundation.logo)" class="foundation-logo" />
                <span>{{ u.foundation.name }}</span>
              </div>
              <span class="update-date">{{ u.posted_at }}</span>
            </div>

            <span v-if="!u.photos?.length" class="update-campaign-tag">{{ u.campaign.title }}</span>

            <h2 class="update-title">{{ u.title }}</h2>
            <p v-if="u.content" class="update-desc">{{ u.content }}</p>

            <div class="thumb-strip" v-if="u.photos?.length > 1">
              <div v-for="(photo, i) in u.photos.slice(1, 5)" :key="i" class="thumb" @click="lightbox = getImage(photo)">
                <img :src="getImage(photo)" alt="" />
                <div v-if="u.photos.length > 5 && i === 3" class="thumb-more">+{{ u.photos.length - 5 }}</div>
              </div>
            </div>

            <!-- REACTION -->
            <div class="reaction-bar">
              <ReactionButton :liked="!!u.my_reaction" :count="u.total_reactions" @toggle="toggleReaction(u)" />
            </div>

          </div>
        </article>

        <button v-if="hasMore" class="btn-load-more" @click="loadMore" :disabled="isLoadingMore">
          {{ isLoadingMore ? 'Loading...' : 'Load more' }}
        </button>
      </div>

      <Lightbox v-model="lightbox" />

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import Spinner from '../components/donor/spinner.vue'
import EmptyState from '../components/donor/emptystate.vue'
import Lightbox from '../components/donor/lightbox.vue'
import ReactionButton from '../components/donor/reactionbutton.vue'
import { getImage } from '../composable/donor.js'

const route  = useRoute()
const router = useRouter()

const updates       = ref([])
const isLoading      = ref(false)
const isLoadingMore  = ref(false)
const lightbox       = ref(null)
const filter         = ref('all')
const page           = ref(1)
const lastPage       = ref(1)
const highlightId    = ref(route.query.highlight ? Number(route.query.highlight) : null)

const hasMore = computed(() => page.value < lastPage.value)

const emptyMessage = computed(() => {
  if (filter.value === 'followed') return 'No updates yet from the foundations you follow.'
  if (filter.value === 'donated') return 'No updates yet from campaigns you donated to.'
  return "No updates yet. Updates appear here once a foundation posts progress on a campaign you've supported or followed."
})

async function load(reset = true) {
  if (reset) {
    isLoading.value = true
    page.value = 1
    updates.value = []
  } else {
    isLoadingMore.value = true
  }

  try {
    const res = await api.get('/donor/feed', { params: { filter: filter.value, page: page.value } })
    const items = res.data?.data ?? []
    updates.value = reset ? items : [...updates.value, ...items]
    lastPage.value = res.data?.last_page ?? 1
  } catch (err) {
    console.error(err)
    if (reset) updates.value = []
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

function changeFilter(f) {
  if (filter.value === f) return
  filter.value = f
  load(true)
}

function loadMore() {
  page.value += 1
  load(false)
}

async function toggleReaction(u) {
  // optimistic update
  u.my_reaction = !u.my_reaction
  u.total_reactions += u.my_reaction ? 1 : -1

  try {
    const res = await api.post(`/donor/campaigns/${u.campaign.id}/updates/${u.id}/react`)
    u.my_reaction = res.data.reacted
    u.total_reactions = res.data.total_reactions
  } catch (err) {
    // revert on failure
    u.my_reaction = !u.my_reaction
    u.total_reactions += u.my_reaction ? 1 : -1
    console.error(err)
  }
}

async function loadAndLocateHighlight() {
  await load(true)
  if (!highlightId.value) return

  // keep loading more pages until the target update is found or there's no more to load
  while (!updates.value.some(u => u.id === highlightId.value) && hasMore.value) {
    page.value += 1
    await load(false)
  }

  await nextTick()
  const el = document.querySelector(`[data-update-id="${highlightId.value}"]`)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })

  setTimeout(() => {
    highlightId.value = null
    router.replace({ query: { ...route.query, highlight: undefined } })
  }, 2500)
}

onMounted(loadAndLocateHighlight)
</script>

<style scoped>
.page { background: #f4faf7; min-height: 100vh; padding-bottom: 60px; }
.container { max-width: 760px; margin: 0 auto; padding: 48px 24px; }

.page-head { margin-bottom: 28px; text-align: center; }
.kicker {
  display: inline-block; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.12em;
  text-transform: uppercase; color: #1a8a52; background: #e6f7ee; padding: 5px 14px;
  border-radius: 20px; margin-bottom: 14px;
}
.page-head h1 {
  font-size: 2.1rem; font-weight: 900; color: #0e3d27; margin: 0 0 8px;
  font-family: Georgia, 'Times New Roman', serif; letter-spacing: -0.01em;
}
.page-head p { font-size: 0.92rem; color: #6b8478; margin: 0; }

/* SWITCH */
.switch {
  display: flex; gap: 6px; background: #e6f7ee; padding: 6px; border-radius: 14px;
  margin-bottom: 36px; max-width: 100%; overflow-x: auto;
}
.switch button {
  flex: 1; white-space: nowrap; padding: 10px 14px; border: none; background: transparent;
  border-radius: 10px; font-size: 0.83rem; font-weight: 700; color: #4d7a64; cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.switch button.active { background: #1a8a52; color: white; box-shadow: 0 2px 8px rgba(26,138,82,0.3); }
.switch button:hover:not(.active) { background: #d5f0e1; }

.loading-wrap {
  display: flex; justify-content: center; padding: 70px;
  background: white; border-radius: 20px; box-shadow: 0 2px 12px rgba(0,80,40,0.06);
}

.updates-feed { display: flex; flex-direction: column; gap: 44px; }

.update-card {
  background: white; border-radius: 22px; overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,60,30,0.07); transition: box-shadow 0.25s, transform 0.25s, outline 0.3s;
}
.update-card:hover { box-shadow: 0 12px 32px rgba(0,60,30,0.12); transform: translateY(-3px); }
.update-card.highlighted { outline: 3px solid #1a8a52; outline-offset: 2px; box-shadow: 0 0 0 6px rgba(26,138,82,0.15); }

.update-hero {
  position: relative; width: 100%; aspect-ratio: 16/9; cursor: pointer;
  overflow: hidden; background: #e6f7ee;
}
.update-hero img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.update-card:hover .update-hero img { transform: scale(1.04); }
.hero-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,30,15,0.55) 0%, rgba(0,30,15,0) 45%); }
.hero-tag {
  position: absolute; bottom: 14px; left: 18px; color: white; font-size: 0.78rem;
  font-weight: 700; letter-spacing: 0.02em; text-shadow: 0 1px 4px rgba(0,0,0,0.4);
}

.update-body { padding: 24px 30px 28px; }
.update-body.no-hero { padding-top: 28px; }

.update-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.foundation-tag { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 700; color: #1a8a52; }
.foundation-logo { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; }
.update-date { font-size: 0.72rem; color: #9caba3; font-weight: 600; letter-spacing: 0.02em; text-transform: uppercase; }

.update-campaign-tag {
  display: inline-block; font-size: 0.72rem; font-weight: 700; color: #6b8478;
  background: #f0fdf4; padding: 3px 10px; border-radius: 8px; margin-bottom: 10px;
}

.update-title { font-size: 1.5rem; font-weight: 800; color: #0e3d27; margin: 0 0 12px; line-height: 1.25; font-family: Georgia, 'Times New Roman', serif; }
.update-desc { font-size: 0.95rem; color: #475569; line-height: 1.85; margin: 0; }

.thumb-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 20px; }
.thumb { position: relative; aspect-ratio: 1; border-radius: 10px; overflow: hidden; cursor: pointer; background: #f0fdf4; }
.thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.25s; }
.thumb:hover img { transform: scale(1.08); }
.thumb-more { position: absolute; inset: 0; background: rgba(0,30,15,0.55); color: white; font-size: 1.05rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }

/* REACTION */
.reaction-bar { margin-top: 18px; padding-top: 16px; border-top: 1px solid #f0fdf4; }

.btn-load-more {
  margin: 8px auto 0; padding: 11px 26px; background: white; border: 1px solid #1a8a52;
  color: #1a8a52; border-radius: 10px; font-size: 0.87rem; font-weight: 700; cursor: pointer;
}
.btn-load-more:hover { background: #f0fdf4; }
.btn-load-more:disabled { opacity: 0.6; cursor: default; }

@media (max-width: 560px) {
  .update-title { font-size: 1.3rem; }
  .update-body { padding: 20px 18px 22px; }
  .thumb-strip { grid-template-columns: repeat(3, 1fr); }
  .switch { gap: 4px; }
  .switch button { font-size: 0.75rem; padding: 9px 8px; }
}
</style>