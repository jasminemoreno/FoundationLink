<template>
  <div class="f-card">

    <div class="f-card-top">
      <div class="f-avatar">{{ foundation.initials }}</div>

      <div class="f-meta">
        <div class="f-name">{{ foundation.name }}</div>
        <div class="f-address">{{ foundation.address || '—' }}</div>
      </div>

      <span class="f-badge" :class="foundation.status">
        {{ statusLabel(foundation.status) }}
      </span>
    </div>

    <p class="f-desc">
      {{ foundation.description || 'No description provided.' }}
    </p>

    <hr class="f-divider" />

    <div class="f-footer">
      <span class="f-date">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        {{ foundation.submitted }}
      </span>

      <div class="f-actions">
        <button class="btn-view" @click="$emit('view', foundation)">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          View
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
defineProps({ foundation: Object })
defineEmits(['view'])

function statusLabel(s) {
  if (s === 'pending')  return 'Pending'
  if (s === 'approved') return 'Approved'
  if (s === 'rejected') return 'Rejected'
  return s
}
</script>

<style scoped>
.f-card {
  background: #fff;
  border: 0.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: border-color 0.15s;
}
.f-card:hover { border-color: #cbd5e1; }

.f-card-top { display: flex; align-items: flex-start; gap: 12px; }

.f-avatar {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #E6F1FB;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 500;
  color: #0C447C;
  flex-shrink: 0;
}

.f-meta { flex: 1; min-width: 0; }

.f-name {
  font-size: 14px;
  font-weight: 500;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.f-address {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.f-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
  flex-shrink: 0;
}
.f-badge.pending  { background: #fefce8; color: #92400e; }
.f-badge.approved { background: #f0fdf4; color: #065f46; }
.f-badge.rejected { background: #fef2f2; color: #991b1b; }

.f-desc {
  font-size: 12px;
  color: #64748b;
  line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.f-divider { border: none; border-top: 0.5px solid #e2e8f0; }

.f-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.f-date {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: #94a3b8;
}

.f-actions { display: flex; gap: 6px; }

.btn-view {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 500;
  padding: 6px 12px;
  border-radius: 8px;
  border: 0.5px solid #cbd5e1;
  background: transparent;
  color: #1e293b;
  cursor: pointer;
  transition: background 0.12s;
}
.btn-view:hover { background: #f1f5f9; }
</style>