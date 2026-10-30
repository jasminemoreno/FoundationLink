<template>
  <!-- A date box that opens the FoundationLink calendar in a small window
       (instead of the browser's plain date picker). Value: "YYYY-MM-DD" or "". -->
  <div class="date-field" :class="{ plain }">
    <button
      type="button"
      class="df-trigger"
      :class="{ empty: !value }"
      :aria-label="value ? `${title}: ${display}` : title"
      aria-haspopup="dialog"
      @click="openPicker"
    >
      <svg v-if="!plain" class="df-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
      </svg>
      <span class="df-text">{{ value ? display : placeholder }}</span>
      <svg class="df-caret" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    <Teleport to="body">
      <div v-if="open" class="df-overlay" @click.self="closePicker">
        <div class="df-dialog" role="dialog" aria-modal="true" :aria-label="title" :style="{ '--df-accent': accent }">
          <div class="df-head">
            <h4>{{ title }}</h4>
            <button type="button" class="df-x" aria-label="Close" @click="closePicker">×</button>
          </div>
          <CalendarPicker
            :model-value="value"
            :min="min"
            :max="max"
            :start="start"
            :jump="jump"
            :show-legend="false"
            :accent="accent"
            :accent-soft="accentSoft"
            @update:model-value="choose"
          />
          <div class="df-actions">
            <button v-if="clearable && value" type="button" class="df-clear" @click="choose('')">Clear date</button>
            <button type="button" class="df-close" @click="closePicker">Close</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import CalendarPicker from './CalendarPicker.vue'

const props = defineProps({
  modelValue:  { type: String, default: '' },
  placeholder: { type: String, default: 'Choose a date' },
  title:       { type: String, default: 'Choose a date' },
  min:         { type: String, default: '1900-01-01' },
  max:         { type: String, default: '2100-12-31' },
  start:       { type: String, default: '' },     // month to open on when empty
  jump:        { type: Boolean, default: true },  // month + year dropdowns
  clearable:   { type: Boolean, default: false },
  plain:       { type: Boolean, default: false }, // no border: sits inside an existing input box
  accent:      { type: String, default: '#1a8a52' },
  accentSoft:  { type: String, default: '#e6f7ee' },
})
const emit = defineEmits(['update:modelValue', 'open', 'close'])

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const value = computed(() => (props.modelValue || '').slice(0, 10))
const display = computed(() => {
  if (!value.value) return ''
  const [y, m, d] = value.value.split('-').map(Number)
  return `${MONTHS[m - 1]} ${d}, ${y}`
})

const open = ref(false)
function onKey(e) { if (e.key === 'Escape') closePicker() }

function openPicker() {
  open.value = true
  window.addEventListener('keydown', onKey)
  emit('open')
}
function closePicker() {
  if (!open.value) return
  open.value = false
  window.removeEventListener('keydown', onKey)
  emit('close')
}
function choose(v) {
  emit('update:modelValue', v)
  if (v) closePicker()
}
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<style scoped>
.date-field { position: relative; width: 100%; }
.df-trigger {
  width: 100%; display: flex; align-items: center; gap: 8px;
  padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff;
  font-size: 0.87rem; font-family: inherit; color: #1e293b; text-align: left; cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.df-trigger:hover { border-color: #cbd5e1; }
.df-trigger:focus-visible { outline: none; border-color: var(--df-focus, #1a8a52); box-shadow: 0 0 0 3px rgba(26, 138, 82, 0.12); }
.df-icon { flex-shrink: 0; color: #64748b; }
.df-text { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.df-trigger.empty .df-text { color: #94a3b8; }
.df-caret { flex-shrink: 0; color: #94a3b8; }

/* inside an existing input box (sign-up): look like its other fields */
.date-field.plain { flex: 1; min-width: 0; }
.plain .df-trigger {
  height: 48px; padding: 0 14px; border: none; border-radius: 0; background: transparent;
  font-size: 14px; font-weight: 600; color: #1a1a1a;
}
.plain .df-trigger.empty .df-text { color: #c0d4ca; font-weight: 500; }

.df-overlay {
  position: fixed; inset: 0; z-index: 3000; background: rgba(15, 45, 82, 0.45);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.df-dialog {
  width: 100%; max-width: 372px; background: #fff; border-radius: 18px; padding: 16px;
  box-shadow: 0 18px 48px rgba(15, 45, 82, 0.25);
}
.df-dialog :deep(.cal) { max-width: none; border: none; box-shadow: none; padding: 0; }
.df-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.df-head h4 { margin: 0; font-size: 1rem; font-weight: 800; color: #0F2D52; }
.df-x { border: none; background: none; font-size: 1.5rem; line-height: 1; color: #94a3b8; cursor: pointer; padding: 0 4px; }
.df-x:hover { color: #475569; }
.df-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
.df-clear, .df-close {
  padding: 8px 14px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; font-family: inherit; cursor: pointer;
}
.df-clear { border: 1.5px solid #fecaca; background: #fff; color: #dc2626; margin-right: auto; }
.df-clear:hover { background: #fef2f2; }
.df-close { border: none; background: var(--df-accent); color: #fff; }
.df-close:hover { filter: brightness(0.92); }
</style>
