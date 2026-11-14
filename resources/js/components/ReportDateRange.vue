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
  </div>
</template>

<script setup>
// The "From / To" picker on both report pages, with quick ranges.
// v-model is { from, to } as 'YYYY-MM-DD' strings ('' = open-ended).
import { computed } from 'vue'
import DateField from './DateField.vue'
import { RANGE_PRESETS, presetRange, todayYmd } from '../composable/reportRange.js'

const props = defineProps({
  modelValue: { type: Object, default: () => ({ from: '', to: '' }) },
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

@media (max-width: 640px) {
  .report-range { flex-direction: column; align-items: stretch; }
  .rr-field { min-width: 0; }
}
</style>
