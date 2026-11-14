<template>
  <div class="report-range">
    <div class="rr-presets" role="group" aria-label="Quick date ranges">
      <button
        v-for="p in RANGE_PRESETS" :key="p.key"
        type="button"
        class="rr-chip"
        :class="{ active: activePreset === p.key }"
        :aria-pressed="activePreset === p.key"
        @click="emit('update:modelValue', presetRange(p.key))"
      >{{ p.label }}</button>
    </div>

    <div class="rr-dates">
      <div class="rr-field">
        <span class="rr-label">From</span>
        <DateField
          :model-value="modelValue.from"
          clearable
          placeholder="Start date"
          title="Report start date"
          accent="#0F2D52"
          accent-soft="#e8eef6"
          :max="modelValue.to || today"
          @update:model-value="v => emit('update:modelValue', { ...modelValue, from: v || '' })"
        />
      </div>
      <div class="rr-field">
        <span class="rr-label">To</span>
        <DateField
          :model-value="modelValue.to"
          clearable
          placeholder="End date"
          title="Report end date"
          accent="#0F2D52"
          accent-soft="#e8eef6"
          :min="modelValue.from || '2000-01-01'"
          :max="today"
          @update:model-value="v => emit('update:modelValue', { ...modelValue, to: v || '' })"
        />
      </div>
    </div>

    <!-- extra filters from the page (e.g. foundation, campaign) -->
    <div v-if="$slots.default" class="rr-extra">
      <slot />
    </div>

    <p class="rr-showing">
      <span class="rr-showing-dot" aria-hidden="true"></span>
      Showing: <strong>{{ rangeLabel(modelValue) }}</strong>
      <template v-if="scope">· <strong>{{ scope }}</strong></template>
      <span v-if="note" class="rr-note">· {{ note }}</span>
    </p>
  </div>
</template>

<script setup>
// The "From / To" picker on both report pages, with quick ranges.
// v-model is { from, to } as 'YYYY-MM-DD' strings ('' = open-ended).
import { computed } from 'vue'
import DateField from './DateField.vue'
import { RANGE_PRESETS, presetRange, rangeLabel, todayYmd } from '../composable/reportRange.js'

const props = defineProps({
  modelValue: { type: Object, default: () => ({ from: '', to: '' }) },
  note: { type: String, default: '' },  // plain-words reminder shown after "Showing: …"
  scope: { type: String, default: '' }, // the page's other filters, e.g. "Campaign: Meal Packs"
})
const emit = defineEmits(['update:modelValue'])

const today = todayYmd()

// Which quick range matches the chosen dates (none when picked by hand)
const activePreset = computed(() => {
  const match = RANGE_PRESETS.find((p) => {
    const r = presetRange(p.key)
    return r.from === (props.modelValue.from || '') && r.to === (props.modelValue.to || '')
  })
  return match ? match.key : null
})
</script>

<style scoped>
.report-range {
  display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px 20px;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; margin-bottom: 20px;
}
.rr-presets { display: flex; flex-wrap: wrap; gap: 8px; }
.rr-chip {
  padding: 8px 14px; border-radius: 999px; border: 1px solid #e2e8f0; background: #f8fafc;
  font-size: 0.8rem; font-weight: 700; color: #475569; cursor: pointer; transition: background 0.15s, color 0.15s;
}
.rr-chip:hover { background: #eef2f7; }
.rr-chip.active { background: #0F2D52; border-color: #0F2D52; color: #fff; }
.rr-chip:focus-visible { outline: 2px solid #D4AF37; outline-offset: 2px; }
.rr-dates { display: flex; flex-wrap: wrap; gap: 12px; }
.rr-field { display: flex; flex-direction: column; gap: 4px; min-width: 170px; flex: 1; }
.rr-label { font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
/* Filter dropdowns the page puts in the slot */
.rr-extra { flex-basis: 100%; display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; }
:slotted(.rr-filter) { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 200px; max-width: 340px; }
:slotted(.rr-filter-label) { font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
:slotted(.rr-select) {
  width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff;
  font-size: 0.86rem; color: #0F2D52; font-family: inherit; cursor: pointer; outline: none;
}
:slotted(.rr-select:focus) { border-color: #0F2D52; box-shadow: 0 0 0 3px rgba(15,45,82,0.1); }
:slotted(.rr-clear) {
  padding: 10px 14px; border-radius: 10px; border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c;
  font-size: 0.82rem; font-weight: 700; cursor: pointer; white-space: nowrap;
}
:slotted(.rr-clear:hover) { background: #fee2e2; }
.rr-showing {
  flex-basis: 100%; margin: 2px 0 0; padding-top: 10px; border-top: 1px dashed #e2e8f0;
  display: flex; flex-wrap: wrap; align-items: center; gap: 4px 6px; font-size: 0.82rem; color: #475569;
}
.rr-showing strong { color: #0F2D52; }
.rr-showing-dot { width: 8px; height: 8px; border-radius: 50%; background: #D4AF37; flex-shrink: 0; }
.rr-note { color: #64748b; }

@media (max-width: 640px) {
  .report-range { flex-direction: column; align-items: stretch; }
  .rr-field { min-width: 0; }
}
@media (max-width: 480px) {
  .rr-dates { flex-direction: column; }
  .rr-extra { flex-direction: column; align-items: stretch; }
  :slotted(.rr-filter) { max-width: none; min-width: 0; }
}
</style>
