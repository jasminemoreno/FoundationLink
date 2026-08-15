<template>
  <span class="fa-badge" :class="tone">{{ label }}</span>
</template>

<script setup>
// Presentational only — each page keeps its own small mapping function from
// its specific status vocabulary (foundation status, campaign status,
// donation status, payment "configured" state, etc.) to one of these tones,
// e.g.:
//   function toneForCampaignStatus(s) {
//     return { active: 'info', completed: 'purple', paused: 'warning',
//               draft: 'neutral', cancelled: 'danger' }[s] || 'neutral'
//   }
// then: <Badge :label="statusLabel(c.status)" :tone="toneForCampaignStatus(c.status)" />

defineProps({
  label: { type: String, required: true },
  tone:  { type: String, default: 'neutral' }, // success | warning | danger | neutral | info | purple
})
</script>

<style scoped>
.fa-badge {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  display: inline-block;
  text-transform: capitalize;
  white-space: nowrap;
}
.fa-badge.success { background: #f0fdf4; color: #059669; }
.fa-badge.warning { background: #fefce8; color: #92400e; }
.fa-badge.danger  { background: #fef2f2; color: #dc2626; }
.fa-badge.neutral { background: #f1f5f9; color: #64748b; }
.fa-badge.info    { background: #eff6ff; color: #3b82f6; }
.fa-badge.purple  { background: #f5f3ff; color: #7c3aed; }
</style>