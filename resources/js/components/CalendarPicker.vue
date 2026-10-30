<template>
  <!-- Month calendar (pick-up days, birthdates, campaign dates).
       Dates are "YYYY-MM-DD" Philippine calendar days. -->
  <div class="cal" :style="{ '--cal-accent': accent, '--cal-soft': accentSoft }">
    <div class="cal-head">
      <button type="button" class="cal-nav" :disabled="!canPrev" aria-label="Previous month" @click="shift(-1)">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
      </button>

      <!-- jump: pick the month and year directly (e.g. a birthdate years back) -->
      <div v-if="jump" class="cal-jump">
        <select v-model.number="view.m" class="cal-select" aria-label="Month">
          <option v-for="(name, i) in MONTHS" :key="name" :value="i" :disabled="!monthAllowed(view.y, i)">{{ name }}</option>
        </select>
        <select :value="view.y" class="cal-select" aria-label="Year" @change="setYear(Number($event.target.value))">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
      <div v-else class="cal-title" aria-live="polite">{{ monthLabel }}</div>

      <button type="button" class="cal-nav" :disabled="!canNext" aria-label="Next month" @click="shift(1)">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

    <div class="cal-grid cal-week" aria-hidden="true">
      <span v-for="w in WEEK" :key="w">{{ w }}</span>
    </div>

    <div class="cal-grid" role="grid">
      <template v-for="cell in cells" :key="cell.key">
        <span v-if="!cell.date" class="cal-blank" />
        <button
          v-else
          type="button"
          class="cal-day"
          :class="{
            today: cell.date === today,
            selected: cell.date === value,
            marked: cell.date === marked,
          }"
          :disabled="cell.disabled"
          :aria-label="cell.aria"
          :aria-pressed="cell.date === value"
          @click="$emit('update:modelValue', cell.date)"
        >
          {{ cell.day }}
          <i v-if="cell.date === marked" class="cal-dot" aria-hidden="true" />
        </button>
      </template>
    </div>

    <div class="cal-foot">
      <span class="cal-chosen">
        <template v-if="value">📅 {{ longLabel(value) }}</template>
        <template v-else>Choose a day</template>
      </span>
      <span v-if="showLegend" class="cal-legend">
        <span><i class="key today" /> Today</span>
        <span v-if="marked"><i class="key marked" /> {{ markedLabel }}</span>
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  modelValue:  { type: String, default: '' },          // chosen day
  min:         { type: String, default: '1900-01-01' }, // first day allowed
  max:         { type: String, default: '2100-12-31' }, // last day allowed
  start:       { type: String, default: '' },          // month to open on when nothing is chosen
  marked:      { type: String, default: '' },          // e.g. the foundation's current pick-up day
  markedLabel: { type: String, default: 'Current pick-up day' },
  jump:        { type: Boolean, default: false },      // month + year dropdowns
  showLegend:  { type: Boolean, default: true },
  accent:      { type: String, default: '#1a8a52' },
  accentSoft:  { type: String, default: '#e6f7ee' },
})
defineEmits(['update:modelValue'])

const WEEK   = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const DAYS   = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

// Philippine "today" (UTC+8 all year)
const today = new Date(Date.now() + 8 * 3600e3).toISOString().slice(0, 10)

// accepts "YYYY-MM-DD" or a full date-time from the server
const value = computed(() => (props.modelValue || '').slice(0, 10))

const parse = s => { const [y, m, d] = s.slice(0, 10).split('-').map(Number); return new Date(Date.UTC(y, m - 1, d)) }
const iso = d => d.toISOString().slice(0, 10)
function longLabel(s) {
  const d = parse(s)
  return `${DAYS[d.getUTCDay()]}, ${MONTHS[d.getUTCMonth()]} ${d.getUTCDate()}, ${d.getUTCFullYear()}`
}

// month on screen: the chosen day's month, else `start`, else today (kept inside min..max)
function initialMonth() {
  let s = value.value || props.start || today
  if (s < props.min) s = props.min
  if (s > props.max) s = props.max
  const d = parse(s)
  return { y: d.getUTCFullYear(), m: d.getUTCMonth() }
}
const view = ref(initialMonth())
watch(value, v => {
  if (!v) return
  const d = parse(v)
  view.value = { y: d.getUTCFullYear(), m: d.getUTCMonth() }
})

const monthKey = (y, m) => y * 12 + m
const minKey = computed(() => { const d = parse(props.min); return monthKey(d.getUTCFullYear(), d.getUTCMonth()) })
const maxKey = computed(() => { const d = parse(props.max); return monthKey(d.getUTCFullYear(), d.getUTCMonth()) })
const canPrev = computed(() => monthKey(view.value.y, view.value.m) > minKey.value)
const canNext = computed(() => monthKey(view.value.y, view.value.m) < maxKey.value)
const monthAllowed = (y, m) => monthKey(y, m) >= minKey.value && monthKey(y, m) <= maxKey.value

// newest first, so recent years are at the top
const years = computed(() => {
  const out = []
  for (let y = parse(props.max).getUTCFullYear(); y >= parse(props.min).getUTCFullYear(); y--) out.push(y)
  return out
})

function shift(n) {
  const k = monthKey(view.value.y, view.value.m) + n
  view.value = { y: Math.floor(k / 12), m: k % 12 }
}

// changing the year keeps the month when allowed, else the nearest allowed one
function setYear(y) {
  let k = monthKey(y, view.value.m)
  k = Math.min(Math.max(k, minKey.value), maxKey.value)
  view.value = { y: Math.floor(k / 12), m: k % 12 }
}

const monthLabel = computed(() => `${MONTHS[view.value.m]} ${view.value.y}`)

const cells = computed(() => {
  const { y, m } = view.value
  const first = new Date(Date.UTC(y, m, 1))
  const daysInMonth = new Date(Date.UTC(y, m + 1, 0)).getUTCDate()
  const out = []
  for (let i = 0; i < first.getUTCDay(); i++) out.push({ key: `b${i}` })
  for (let day = 1; day <= daysInMonth; day++) {
    const date = iso(new Date(Date.UTC(y, m, day)))
    out.push({
      key: date,
      date,
      day,
      disabled: date < props.min || date > props.max,
      aria: `${longLabel(date)}${date === today ? ' (today)' : ''}${date === props.marked ? ` (${props.markedLabel})` : ''}`,
    })
  }
  return out
})
</script>

<style scoped>
.cal {
  border: 1px solid #e2e8f0; border-radius: 14px; background: #fff;
  padding: 12px 12px 10px; max-width: 340px;
  box-shadow: 0 2px 8px rgba(15, 45, 82, 0.05);
}
.cal-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
.cal-title { font-size: 0.92rem; font-weight: 800; color: #0F2D52; }
.cal-nav {
  flex-shrink: 0; width: 32px; height: 32px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff;
  color: #475569; display: flex; align-items: center; justify-content: center; cursor: pointer;
}
.cal-nav:hover:not(:disabled) { background: var(--cal-soft); color: var(--cal-accent); border-color: var(--cal-accent); }
.cal-nav:disabled { opacity: 0.35; cursor: not-allowed; }

.cal-jump { display: flex; gap: 6px; min-width: 0; }
.cal-select {
  padding: 6px 26px 6px 10px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #fff;
  font-size: 0.85rem; font-weight: 800; color: #0F2D52; font-family: inherit; cursor: pointer;
  appearance: none; -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%2364748b' stroke-width='2.5' viewBox='0 0 24 24'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 8px center;
}
.cal-select:hover { border-color: var(--cal-accent); }
.cal-select:focus { outline: none; border-color: var(--cal-accent); box-shadow: 0 0 0 3px var(--cal-soft); }

.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-week span { text-align: center; font-size: 0.68rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; padding: 4px 0; }

.cal-day {
  position: relative; aspect-ratio: 1; border: none; border-radius: 50%; background: transparent;
  font-size: 0.84rem; font-weight: 650; color: #1e293b; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.15s, color 0.15s;
}
.cal-day:hover:not(:disabled):not(.selected) { background: var(--cal-soft); color: var(--cal-accent); }
.cal-day:focus-visible { outline: 2px solid var(--cal-accent); outline-offset: 2px; }
.cal-day.today { box-shadow: inset 0 0 0 1.5px var(--cal-accent); color: var(--cal-accent); font-weight: 800; }
.cal-day.selected { background: var(--cal-accent); color: #fff; font-weight: 800; box-shadow: 0 3px 8px rgba(15, 45, 82, 0.18); }
.cal-day:disabled { color: #cbd5e1; cursor: not-allowed; background: transparent; box-shadow: none; }
.cal-dot { position: absolute; bottom: 4px; left: 50%; width: 5px; height: 5px; margin-left: -2.5px; border-radius: 50%; background: #f59e0b; }
.cal-day.selected .cal-dot { background: #fff; }

.cal-foot {
  display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 6px 12px;
  margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9;
}
.cal-chosen { font-size: 0.82rem; font-weight: 800; color: #0F2D52; }
.cal-legend { display: flex; gap: 10px; font-size: 0.7rem; color: #64748b; }
.cal-legend span { display: inline-flex; align-items: center; gap: 4px; }
.key { display: inline-block; width: 9px; height: 9px; border-radius: 50%; }
.key.today { box-shadow: inset 0 0 0 1.5px var(--cal-accent); }
.key.marked { background: #f59e0b; }
</style>
