<template>
  <section class="ct" aria-labelledby="ct-title">
    <!-- HEADER: title + all controls in one row -->
    <header class="ct-head">
      <div class="ct-heading">
        <h3 id="ct-title" class="ct-title">Campaign Timeline</h3>
        <p class="ct-sub">Money received over time, the promotions you ran, and who donated when.</p>
      </div>

      <div class="ct-controls" v-if="campaigns.length">
        <label class="ct-field">
          <span class="ct-field-label">Campaign</span>
          <select v-model="campaignId" class="ct-select">
            <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.title }}</option>
          </select>
        </label>

        <div class="ct-seg" role="group" aria-label="Group by">
          <button type="button" :class="{ on: group === 'day' }" :aria-pressed="group === 'day'" @click="group = 'day'">Per day</button>
          <button type="button" :class="{ on: group === 'month' }" :aria-pressed="group === 'month'" @click="group = 'month'">Per month</button>
        </div>

        <div class="ct-seg ct-seg-icons" role="group" aria-label="View">
          <button type="button" :class="{ on: !showTable }" :aria-pressed="!showTable" title="Chart" @click="showTable = false">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="M8 17V11M13 17V7M18 17v-4"/></svg>
            <span>Chart</span>
          </button>
          <button type="button" :class="{ on: showTable }" :aria-pressed="showTable" title="Table" @click="showTable = true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M3 15h18M9 4v16"/></svg>
            <span>Table</span>
          </button>
        </div>
      </div>
    </header>

    <div class="ct-body">
      <EmptyState v-if="!campaigns.length" message="Create a campaign to see its timeline." />
      <EmptyState v-else-if="loading && !data" loading compact />

      <div v-else-if="data" :class="{ 'is-reloading': loading }">
        <!-- STAT TILES -->
        <div class="ct-stats">
          <div class="ct-stat">
            <div class="ct-stat-label">Total received</div>
            <div class="ct-stat-value">₱{{ compact(totals.amount) }}</div>
            <div class="ct-stat-note">{{ periodLabel }}</div>
          </div>
          <div class="ct-stat">
            <div class="ct-stat-label">Donations received</div>
            <div class="ct-stat-value">{{ totals.count.toLocaleString() }}</div>
            <div class="ct-stat-note">{{ uniqueDonors }} {{ uniqueDonors === 1 ? 'donor' : 'donors' }}<span v-if="totals.items"> · {{ totals.items }} with items</span></div>
          </div>
          <div class="ct-stat">
            <div class="ct-stat-label">Average {{ group === 'day' ? 'per day' : 'per month' }}</div>
            <div class="ct-stat-value">₱{{ compact(average) }}</div>
            <div class="ct-stat-note">Best: {{ best ? `₱${compact(best.amount)} ${group === 'day' ? 'on' : 'in'} ${best.label}` : '—' }}</div>
          </div>
          <div class="ct-stat ct-stat-impact">
            <div class="ct-stat-label">Promotion impact</div>
            <template v-if="impact">
              <div class="ct-stat-value">
                ₱{{ compact(impact.during) }}<span class="ct-unit">/{{ group }}</span>
                <span v-if="impact.delta !== null" class="ct-delta" :class="impact.delta >= 0 ? 'up' : 'down'">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="impact.delta >= 0 ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M5 12l7 7 7-7'"/></svg>
                  {{ impact.delta >= 0 ? '+' : '' }}{{ impact.delta.toLocaleString() }}%
                </span>
              </div>
              <div class="ct-stat-note">during promotions vs ₱{{ compact(impact.outside) }}/{{ group }} without</div>
            </template>
            <template v-else>
              <div class="ct-stat-value ct-muted-value">—</div>
              <div class="ct-stat-note">No promotions in this period yet</div>
            </template>
          </div>
        </div>

        <!-- CHART -->
        <div v-if="!showTable" class="ct-chart-card">
          <div class="ct-chart-head">
            <span class="ct-chart-title">Money received {{ group === 'day' ? 'per day' : 'per month' }} (₱)</span>
            <span class="ct-legend">
              <span class="lg"><i class="lg-bar"></i>Received</span>
              <span class="lg"><i class="lg-band"></i>Promotion running</span>
            </span>
          </div>

          <div class="ct-chart" ref="chartBox" @mouseleave="hover = null">
            <svg :width="width" :height="H" role="img"
                 :aria-label="`Money received ${group === 'day' ? 'per day' : 'per month'} for ${data.campaign.title}`">
              <!-- gridlines + y labels (recessive) -->
              <g v-for="t in yTicks" :key="t">
                <line :x1="PAD_L" :x2="width - PAD_R" :y1="y(t)" :y2="y(t)" :class="t === 0 ? 'baseline' : 'grid'" />
                <text :x="PAD_L - 10" :y="y(t) + 4" class="axis" text-anchor="end">₱{{ compact(t) }}</text>
              </g>

              <!-- promotion bands (behind bars) -->
              <g v-for="b in promoBands" :key="b.id" class="band">
                <rect :x="b.x" :y="PAD_T" :width="b.w" :height="plotH" class="band-fill" />
                <line :x1="b.x" :x2="b.x" :y1="PAD_T" :y2="PAD_T + plotH" class="band-edge" />
                <line :x1="b.x + b.w" :x2="b.x + b.w" :y1="PAD_T" :y2="PAD_T + plotH" class="band-edge" />
                <g :transform="`translate(${b.chipX}, ${PAD_T - 22})`">
                  <rect :width="b.chipW" height="18" rx="9" class="band-chip" />
                  <text :x="b.chipW / 2" y="12.5" text-anchor="middle" class="band-chip-text">{{ b.label }}</text>
                </g>
              </g>

              <!-- hovered column highlight -->
              <rect v-if="hover !== null" :x="PAD_L + hover * band" :y="PAD_T" :width="band" :height="plotH" class="hover-col" />

              <!-- bars: thin, 4px rounded tops, anchored to the baseline -->
              <path v-for="(b, i) in data.buckets" :key="b.key" :d="barPath(i, b.amount)"
                    class="bar" :class="{ dim: hover !== null && hover !== i }" />

              <!-- x labels, thinned so they never collide -->
              <text v-for="(b, i) in data.buckets" v-show="showXLabel(i)"
                    :key="'l' + b.key" :x="cx(i)" :y="H - 8" class="axis" text-anchor="middle">{{ b.label }}</text>

              <!-- hover targets: full-height columns, larger than the bar -->
              <rect v-for="(b, i) in data.buckets" :key="'h' + b.key" :x="PAD_L + i * band" :y="PAD_T"
                    :width="band" :height="plotH" class="hit" @mouseenter="hover = i" />
            </svg>

            <!-- tooltip (beside the hovered column) -->
            <div v-if="hover !== null" class="ct-tip" :style="tipStyle" role="status">
              <div class="tip-head">{{ data.buckets[hover].label }}</div>
              <div class="tip-amount">₱{{ money(data.buckets[hover].amount) }}</div>
              <dl class="tip-rows">
                <div><dt>Donations</dt><dd>{{ data.buckets[hover].count }}</dd></div>
                <div><dt>Donors</dt><dd>{{ data.buckets[hover].donors }}</dd></div>
                <div><dt>With items</dt><dd>{{ data.buckets[hover].items }}</dd></div>
              </dl>
              <div v-if="promoAt(data.buckets[hover].key)" class="tip-promo">
                <span class="tip-promo-type">{{ promoAt(data.buckets[hover].key).type_label }} promotion</span>
                “{{ promoAt(data.buckets[hover].key).message }}”
              </div>
            </div>
          </div>

          <p v-if="totals.amount === 0 && totals.items === 0" class="ct-empty-note">
            No donations have been received for this campaign in this period yet.
          </p>
        </div>

        <!-- TABLE VIEW (same data as the chart) -->
        <div v-else class="ct-table-wrap">
          <table class="ct-table">
            <thead>
              <tr>
                <th scope="col">{{ group === 'day' ? 'Day' : 'Month' }}</th>
                <th scope="col" class="num">Received</th>
                <th scope="col" class="num">Donations</th>
                <th scope="col" class="num">Donors</th>
                <th scope="col">Promotion</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in [...data.buckets].reverse()" :key="b.key" :class="{ 'row-promo': promoAt(b.key) }">
                <td>{{ b.label }}</td>
                <td class="num">₱{{ money(b.amount) }}</td>
                <td class="num">{{ b.count }}</td>
                <td class="num">{{ b.donors }}</td>
                <td>
                  <span v-if="promoAt(b.key)" class="pill pill-promo">{{ promoAt(b.key).type_label }}</span>
                  <span v-else class="ct-dash">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ACTIVITY -->
        <div class="ct-activity">
          <div class="ct-activity-head">
            <h4 class="ct-activity-title">Activity</h4>
            <div class="ct-chips" role="group" aria-label="Show">
              <button v-for="f in filters" :key="f.key" type="button" class="chip"
                      :class="{ on: activityFilter === f.key }" :aria-pressed="activityFilter === f.key"
                      @click="activityFilter = f.key; visibleCount = PAGE">
                {{ f.label }} <span class="chip-count">{{ f.count }}</span>
              </button>
            </div>
          </div>

          <div v-if="!groupedEvents.length" class="ct-empty-note">Nothing to show here yet.</div>

          <div v-for="day in groupedEvents" :key="day.key" class="ct-day">
            <div class="ct-day-label">{{ day.label }}</div>
            <ul class="ct-rows">
              <li v-for="(e, i) in day.events" :key="day.key + i" class="ct-row">
                <span class="ev-icon" :class="e.type" aria-hidden="true">
                  <svg viewBox="0 0 24 24" v-html="icons[e.type]"></svg>
                </span>

                <div class="ev-main">
                  <div class="ev-title">
                    <template v-if="e.type === 'donation'">
                      <strong>{{ e.actor }}</strong> donated{{ e.items ? ' ' + e.items : '' }}
                    </template>
                    <template v-else>{{ e.title }}</template>
                  </div>
                  <div class="ev-meta">
                    <span>{{ e.time }}</span>
                    <template v-if="e.type === 'donation' && e.method"><span class="dot">·</span><span>{{ e.method }}</span></template>
                    <template v-if="e.type === 'promotion' && e.detail"><span class="dot">·</span><span class="ev-quote">{{ e.detail }}</span></template>
                  </div>
                </div>

                <div class="ev-side">
                  <span v-if="e.amount !== null && e.amount !== undefined" class="ev-amount">₱{{ money(e.amount) }}</span>
                  <span v-if="e.status" class="pill" :class="'pill-' + e.status">{{ statusLabel(e.status) }}</span>
                </div>
              </li>
            </ul>
          </div>

          <button v-if="filteredEvents.length > visibleCount" type="button" class="ct-more" @click="visibleCount += PAGE">
            Show more <span class="chip-count">{{ filteredEvents.length - visibleCount }}</span>
          </button>
        </div>
      </div>

      <p v-if="error" class="ct-error" role="alert">{{ error }}</p>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue"
import api from "@/services/api"
import EmptyState from "./emptystate.vue"

const props = defineProps({
  // [{ id, title, status }] — the foundation's campaigns
  campaigns: { type: Array, default: () => [] },
})

const PAGE = 12
const campaignId     = ref(null)
const group          = ref("day")
const data           = ref(null)
const loading        = ref(false)
const error          = ref("")
const hover          = ref(null)
const showTable      = ref(false)
const visibleCount   = ref(PAGE)
const activityFilter = ref("all")

/* Default to the first running campaign (the one most worth watching) */
watch(() => props.campaigns, list => {
  if (!campaignId.value && list.length) {
    campaignId.value = (list.find(c => c.status === "active") || list[0]).id
  }
}, { immediate: true })

async function load() {
  if (!campaignId.value) return
  loading.value = true
  error.value = ""
  hover.value = null
  try {
    const res = await api.get(`/foundation/campaigns/${campaignId.value}/timeline`, { params: { group: group.value } })
    data.value = res.data
    visibleCount.value = PAGE
    await nextTick()
    measure()
  } catch (err) {
    error.value = err.response?.data?.message || "Could not load the timeline."
  } finally {
    loading.value = false
  }
}
watch([campaignId, group], load)
watch(showTable, async v => { if (!v) { await nextTick(); measure() } })

/* ── Chart geometry ── */
const chartBox = ref(null)
const width = ref(720)
const H = 280, PAD_L = 58, PAD_R = 14, PAD_T = 34, PAD_B = 30
const plotH = H - PAD_T - PAD_B

function measure() {
  if (chartBox.value) width.value = Math.max(200, chartBox.value.clientWidth) // always match the box, even on phones
}
let observer
onMounted(() => {
  observer = new ResizeObserver(measure)
  watch(chartBox, el => { if (el) observer.observe(el) }, { immediate: true })
})
onBeforeUnmount(() => observer?.disconnect())

const count = computed(() => data.value?.buckets.length || 1)
const band  = computed(() => (width.value - PAD_L - PAD_R) / count.value)
const cx    = i => PAD_L + band.value * (i + 0.5)

// "Nice" y-axis: the smallest round step whose 4 gridlines just cover the tallest bar
const yMax = computed(() => {
  const max = Math.max(0, ...(data.value?.buckets.map(b => b.amount) || [0]))
  if (max <= 0) return 1000
  const rawStep = max / 4
  const pow = 10 ** Math.floor(Math.log10(rawStep))
  const step = [1, 2, 2.5, 5, 10].find(s => s * pow >= rawStep) * pow
  return step * 4
})
const yTicks = computed(() => [0, 1, 2, 3, 4].map(i => (yMax.value / 4) * i))
const y = v => PAD_T + plotH - (v / yMax.value) * plotH

function barPath(i, amount) {
  const w = Math.max(2, Math.min(26, band.value - 4))
  const x = cx(i) - w / 2
  const base = PAD_T + plotH
  const h = amount > 0 ? Math.max(2, base - y(amount)) : 0
  if (h === 0) return ""
  const r = Math.min(4, w / 2, h)
  const top = base - h
  return `M${x},${base} V${top + r} Q${x},${top} ${x + r},${top} H${x + w - r} Q${x + w},${top} ${x + w},${top + r} V${base} Z`
}

// Thin the x labels by the space available (~60px per label), so they never overlap
const labelEvery = computed(() => {
  const fit = Math.max(2, Math.floor((width.value - PAD_L - PAD_R) / 60))
  return Math.max(1, Math.ceil(count.value / fit))
})

// Every labelEvery-th date; the last date too, but only if it is not crowding the one before
function showXLabel(i) {
  const last = count.value - 1
  if (i % labelEvery.value === 0) return true
  return i === last && last % labelEvery.value >= Math.ceil(labelEvery.value / 2)
}

// Promotion bands + a type chip above each, kept inside the chart
const promoBands = computed(() => {
  if (!data.value) return []
  const keys = data.value.buckets.map(b => b.key)
  return data.value.promotions.map(p => {
    const first = keys.findIndex(k => k >= p.start_key)
    let last = -1
    keys.forEach((k, i) => { if (k <= p.end_key) last = i })
    if (first === -1 || last < first) return null
    const x = PAD_L + first * band.value
    const w = (last - first + 1) * band.value
    const label = p.type_label || "Promotion"
    const chipW = 18 + label.length * 6.4
    const chipX = Math.min(Math.max(x, PAD_L), width.value - PAD_R - chipW)
    return { id: p.id, x, w, label, chipW, chipX }
  }).filter(Boolean)
})

function promoAt(key) {
  return data.value?.promotions.find(p => key >= p.start_key && key <= p.end_key) || null
}

// Tooltip beside the hovered column so it never covers the bar
const TIP_W = 200
const tipStyle = computed(() => {
  const x = cx(hover.value)
  const gap = band.value / 2 + 10
  const left = x + gap + TIP_W <= width.value ? x + gap : Math.max(0, x - gap - TIP_W)
  return { left: `${left}px`, top: "36px", width: `${TIP_W}px` }
})

/* ── Stat tiles ── */
const totals = computed(() => {
  const b = data.value?.buckets || []
  return {
    amount: b.reduce((s, x) => s + x.amount, 0),
    count: b.reduce((s, x) => s + (x.count || 0), 0),
    items: b.reduce((s, x) => s + x.items, 0),
  }
})
// Different donors with a received donation inside the period the chart shows
const uniqueDonors = computed(() => {
  const b = data.value?.buckets || []
  if (!b.length) return 0
  const inPeriod = key => {
    const k = group.value === 'month' ? key.slice(0, 7) : key
    return k >= b[0].key && k <= b[b.length - 1].key
  }
  const names = new Set((data.value?.events || [])
    .filter(e => e.type === 'donation' && e.status === 'received' && inPeriod(e.day_key))
    .map(e => e.actor))
  return names.size
})
const average = computed(() => totals.value.amount / count.value)
const best = computed(() => {
  const b = data.value?.buckets || []
  const top = b.reduce((m, x) => (x.amount > (m?.amount ?? 0) ? x : m), null)
  return top && top.amount > 0 ? top : null
})
const periodLabel = computed(() => {
  const b = data.value?.buckets || []
  return b.length ? `${b[0].label} – ${b[b.length - 1].label}` : ""
})

// Average received per day/month while a promotion was running vs not
const impact = computed(() => {
  const b = data.value?.buckets || []
  const during = b.filter(x => promoAt(x.key))
  const outside = b.filter(x => !promoAt(x.key))
  if (!during.length) return null
  const avg = list => (list.length ? list.reduce((s, x) => s + x.amount, 0) / list.length : 0)
  const d = avg(during), o = avg(outside)
  return { during: d, outside: o, delta: o > 0 ? Math.round(((d - o) / o) * 100) : null }
})

/* ── Activity ── */
const events = computed(() => data.value?.events || [])
const filters = computed(() => [
  { key: "all", label: "All", count: events.value.length },
  { key: "donation", label: "Donations", count: events.value.filter(e => e.type === "donation").length },
  { key: "promotion", label: "Promotions", count: events.value.filter(e => e.type === "promotion").length },
])
const filteredEvents = computed(() =>
  activityFilter.value === "all" ? events.value : events.value.filter(e => e.type === activityFilter.value)
)
const groupedEvents = computed(() => {
  const days = []
  for (const e of filteredEvents.value.slice(0, visibleCount.value)) {
    let day = days[days.length - 1]
    if (!day || day.key !== e.day_key) days.push(day = { key: e.day_key, label: e.day_label, events: [] })
    day.events.push(e)
  }
  return days
})

// Outline icons (24px grid) for each kind of activity
const icons = {
  donation: '<path d="M11 14h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 16"/><path d="m7 20 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 15 6 6"/><path d="M19.5 8.5c.7-.7 1.5-1.6 1.5-2.7A2.73 2.73 0 0 0 16 4a2.78 2.78 0 0 0-5 1.8c0 1.2.8 2 1.5 2.8L16 12Z"/>',
  promotion: '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
  update: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/>',
  created: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>',
}

function statusLabel(s) {
  return { received: "Received", pending: "Pending", cancelled: "Cancelled", running: "Running", ended: "Ended" }[s] || s
}
const money = v => Number(v || 0).toLocaleString("en-PH", { minimumFractionDigits: 0, maximumFractionDigits: 2 })
// Compact for tiles/axis: 950 · 5,250 · 12.9K · 1.2M
const compact = v => {
  const n = Number(v || 0)
  if (n >= 1e6) return `${+(n / 1e6).toFixed(1)}M`
  if (n >= 1e5) return `${+(n / 1e3).toFixed(1)}K`
  return Math.round(n).toLocaleString("en-PH")
}

onMounted(load)
</script>

<style scoped>
/* All colours from assets/foundation-theme.css; promotion amber validated against the bar blue */
.ct { margin-top: 20px; background: var(--fa-surface); border: 1px solid var(--fa-border); border-radius: 16px; box-shadow: var(--fa-shadow); }

/* Header */
.ct-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px 24px; flex-wrap: wrap; padding: 20px 24px 18px; border-bottom: 1px solid var(--fa-border-soft); }
.ct-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--fa-heading); letter-spacing: -0.01em; }
.ct-sub { margin: 4px 0 0; font-size: 0.82rem; color: var(--fa-muted); }
.ct-controls { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
.ct-field { display: flex; flex-direction: column; gap: 4px; }
.ct-field-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--fa-muted); }
.ct-select { height: 36px; min-width: 220px; max-width: 280px; padding: 0 34px 0 12px; border: 1px solid var(--fa-border); border-radius: 10px; background: var(--fa-surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235b6b82' stroke-width='2.5'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 12px center; appearance: none; color: var(--fa-text); font-size: 0.86rem; font-weight: 600; cursor: pointer; }
.ct-select:focus-visible { outline: 2px solid var(--fa-primary); outline-offset: 1px; }
.ct-seg { display: inline-flex; height: 36px; padding: 3px; background: var(--fa-primary-tint); border: 1px solid var(--fa-border); border-radius: 10px; }
.ct-seg button { display: inline-flex; align-items: center; gap: 6px; border: none; background: none; padding: 0 12px; border-radius: 7px; font-size: 0.82rem; font-weight: 600; color: var(--fa-text-2); cursor: pointer; transition: background 0.15s, color 0.15s; }
.ct-seg button:hover:not(.on) { color: var(--fa-primary-hover); }
.ct-seg button.on { background: var(--fa-surface); color: var(--fa-primary-hover); box-shadow: 0 1px 2px rgba(30,58,138,0.12); }
.ct-seg button:focus-visible { outline: 2px solid var(--fa-primary); outline-offset: 1px; }
.ct-seg-icons svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

.ct-body { padding: 20px 24px 24px; }
.is-reloading { opacity: 0.55; transition: opacity 0.15s; pointer-events: none; }

/* Stat tiles */
.ct-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
.ct-stat { padding: 14px 16px; border: 1px solid var(--fa-border-soft); border-radius: 12px; background: var(--fa-primary-tint); min-width: 0; }
.ct-stat-label { font-size: 0.78rem; font-weight: 600; color: var(--fa-text-2); }
.ct-stat-value { margin-top: 6px; display: flex; align-items: baseline; flex-wrap: wrap; gap: 4px 8px; font-size: 1.45rem; font-weight: 700; color: var(--fa-heading); letter-spacing: -0.01em; line-height: 1.15; }
.ct-unit { font-size: 0.8rem; font-weight: 600; color: var(--fa-muted); }
.ct-muted-value { color: var(--fa-muted); }
.ct-stat-note { margin-top: 4px; font-size: 0.76rem; color: var(--fa-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ct-stat-impact { background: var(--fa-surface); border-color: var(--fa-warning-border); }
.ct-stat-impact .ct-stat-note { white-space: normal; } /* the comparison is the point: never cut it off */
.ct-delta { display: inline-flex; align-items: center; gap: 2px; padding: 2px 8px; border-radius: 20px; font-size: 0.74rem; font-weight: 700; }
.ct-delta svg { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
.ct-delta.up   { background: var(--fa-success-bg); color: var(--fa-success); }
.ct-delta.down { background: var(--fa-danger-bg); color: var(--fa-danger); }

/* Chart */
.ct-chart-card { border: 1px solid var(--fa-border-soft); border-radius: 12px; padding: 14px 14px 6px; }
.ct-chart-head { display: flex; justify-content: space-between; align-items: center; gap: 8px 16px; flex-wrap: wrap; padding: 0 4px 6px; }
.ct-chart-title { font-size: 0.82rem; font-weight: 700; color: var(--fa-heading); }
.ct-legend { display: inline-flex; gap: 16px; font-size: 0.76rem; color: var(--fa-text-2); }
.lg { display: inline-flex; align-items: center; gap: 6px; }
.lg-bar  { width: 10px; height: 10px; border-radius: 3px; background: var(--fa-primary); }
.lg-band { width: 14px; height: 10px; border-radius: 2px; background: rgba(217,119,6,0.10); border-left: 1.5px dashed #d97706; border-right: 1.5px dashed #d97706; }
.ct-chart { position: relative; width: 100%; } /* the chart always matches this box, so nothing here needs to scroll */
.ct-chart svg { display: block; overflow: visible; }
.grid { stroke: var(--fa-border); stroke-width: 1; stroke-dasharray: 3 4; }
.baseline { stroke: #cbd5e1; stroke-width: 1; }
.axis { font-size: 11px; fill: var(--fa-muted); font-variant-numeric: tabular-nums; }
.bar { fill: var(--fa-primary); transition: opacity 0.15s; }
.bar.dim { opacity: 0.4; }
.hover-col { fill: rgba(37,99,235,0.06); }
.band-fill { fill: rgba(217,119,6,0.08); }
.band-edge { stroke: #d97706; stroke-width: 1.5; stroke-dasharray: 4 3; opacity: 0.7; }
.band-chip { fill: var(--fa-warning-bg); stroke: var(--fa-warning-border); }
.band-chip-text { font-size: 10.5px; font-weight: 700; fill: var(--fa-warning); }
.hit { fill: transparent; }

.ct-tip { position: absolute; z-index: 2; background: var(--fa-surface); border: 1px solid var(--fa-border); border-radius: 12px; box-shadow: var(--fa-shadow-lg); padding: 12px 14px; pointer-events: none; box-sizing: border-box; } /* 200px includes padding + border, so it fits the space reserved for it */
.tip-head { font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fa-muted); }
.tip-amount { margin: 2px 0 8px; font-size: 1.15rem; font-weight: 700; color: var(--fa-heading); }
.tip-rows { margin: 0; display: grid; gap: 3px; font-size: 0.8rem; }
.tip-rows div { display: flex; justify-content: space-between; }
.tip-rows dt { color: var(--fa-text-2); }
.tip-rows dd { margin: 0; font-weight: 700; color: var(--fa-text); font-variant-numeric: tabular-nums; }
.tip-promo { margin-top: 9px; padding-top: 9px; border-top: 1px solid var(--fa-border-soft); font-size: 0.78rem; color: var(--fa-text-2); line-height: 1.4; }
.tip-promo-type { display: block; font-weight: 700; color: var(--fa-warning); margin-bottom: 2px; }

/* Table */
.ct-table-wrap { border: 1px solid var(--fa-border-soft); border-radius: 12px; overflow: auto; max-height: 340px; }
.ct-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
.ct-table th { position: sticky; top: 0; z-index: 1; background: var(--fa-primary-tint); color: var(--fa-text-2); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; padding: 10px 14px; border-bottom: 1px solid var(--fa-border); }
.ct-table td { padding: 10px 14px; border-bottom: 1px solid var(--fa-border-soft); color: var(--fa-text); }
.ct-table .num { text-align: right; font-variant-numeric: tabular-nums; }
.ct-table tbody tr:hover { background: var(--fa-primary-tint); }
.ct-dash { color: var(--fa-muted); }

/* Activity */
.ct-activity { margin-top: 24px; }
.ct-activity-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
.ct-activity-title { margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--fa-heading); }
.ct-chips { display: inline-flex; flex-wrap: wrap; gap: 6px; }
.chip { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 12px; border: 1px solid var(--fa-border); border-radius: 20px; background: var(--fa-surface); color: var(--fa-text-2); font-size: 0.8rem; font-weight: 600; cursor: pointer; }
.chip.on { border-color: var(--fa-primary); background: var(--fa-primary-soft); color: var(--fa-primary-hover); }
.chip:focus-visible, .ct-more:focus-visible { outline: 2px solid var(--fa-primary); outline-offset: 1px; }
.chip-count { min-width: 20px; padding: 1px 6px; border-radius: 10px; background: var(--fa-primary-tint); font-size: 0.7rem; font-weight: 700; color: var(--fa-text-2); text-align: center; }
.chip.on .chip-count { background: var(--fa-surface); color: var(--fa-primary-hover); }

.ct-day { margin-top: 14px; }
.ct-day-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--fa-muted); padding: 0 2px 6px; border-bottom: 1px solid var(--fa-border-soft); }
.ct-rows { list-style: none; margin: 0; padding: 0; }
.ct-row { display: flex; align-items: center; gap: 12px; padding: 11px 2px; border-bottom: 1px solid var(--fa-border-soft); }
.ct-row:last-child { border-bottom: none; }
.ev-icon { width: 34px; height: 34px; flex-shrink: 0; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.ev-icon svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.ev-icon.donation  { background: var(--fa-success-bg); color: var(--fa-success); }
.ev-icon.promotion { background: var(--fa-warning-bg); color: var(--fa-warning); }
.ev-icon.update    { background: var(--fa-primary-soft); color: var(--fa-primary-hover); }
.ev-icon.created   { background: #f1f5f9; color: var(--fa-text-2); }
.ev-main { flex: 1; min-width: 0; }
.ev-title { font-size: 0.86rem; color: var(--fa-text); line-height: 1.35; }
.ev-title strong { font-weight: 700; color: var(--fa-heading); }
.ev-meta { margin-top: 2px; display: flex; gap: 6px; font-size: 0.76rem; color: var(--fa-muted); min-width: 0; }
.ev-meta .dot { opacity: 0.6; }
.ev-quote { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ev-side { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.ev-amount { font-size: 0.9rem; font-weight: 700; color: var(--fa-heading); font-variant-numeric: tabular-nums; }

.pill { display: inline-flex; align-items: center; height: 22px; padding: 0 9px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; white-space: nowrap; }
.pill-received, .pill-running { background: var(--fa-success-bg); color: var(--fa-success); }
.pill-pending, .pill-promo { background: var(--fa-warning-bg); color: var(--fa-warning); }
.pill-cancelled { background: var(--fa-danger-bg); color: var(--fa-danger); }
.pill-ended { background: #f1f5f9; color: var(--fa-text-2); }

.ct-more { display: inline-flex; align-items: center; gap: 8px; margin-top: 14px; height: 34px; padding: 0 14px; border: 1px solid var(--fa-border); border-radius: 10px; background: var(--fa-surface); color: var(--fa-primary-hover); font-size: 0.82rem; font-weight: 700; cursor: pointer; }
.ct-more:hover { background: var(--fa-primary-tint); }

.ct-empty-note { margin: 12px 4px 4px; font-size: 0.82rem; color: var(--fa-muted); }
.ct-error { margin: 12px 0 0; font-size: 0.84rem; color: var(--fa-danger); }

/* Responsive */
@media (max-width: 1100px) { .ct-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) {
  .ct-head, .ct-body { padding-left: 16px; padding-right: 16px; }
  .ct-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .ct-stat { padding: 12px; }
  .ct-stat-value { font-size: 1.2rem; }
  .ct-stat-note { white-space: normal; } /* wrap instead of cutting off on phones */
  .ct-select { min-width: 0; width: 100%; max-width: none; }
  .ct-field { width: 100%; }
  .ct-seg-icons span { display: none; }
  .ct-row { flex-wrap: wrap; }
  .ev-side { width: 100%; justify-content: flex-end; }
}
</style>
