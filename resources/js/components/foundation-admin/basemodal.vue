<template>
    <div class="fa-modal-overlay" @click.self="$emit('close')">
      <div class="fa-modal" :style="{ width: width }">
        <div class="fa-modal-head">
          <h3><slot name="header">{{ title }}</slot></h3>
          <button class="fa-modal-close" @click="$emit('close')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
  
        <div class="fa-modal-body">
          <slot />
        </div>
  
        <div v-if="$slots.footer" class="fa-modal-actions">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  // Matches how modals were already used across these pages — render with
  // v-if on the component itself, and handle @close the same way the old
  // `showForm = false` / `viewTarget = null` assignments worked:
  //
  // <FoundationModal v-if="showForm" title="New Campaign" width="520px" @close="showForm = false">
  //   ...form fields...
  //   <template #footer>
  //     <button class="btn-cancel" @click="showForm = false">Cancel</button>
  //     <button class="btn-save" @click="save">Save</button>
  //   </template>
  // </FoundationModal>
  
  defineProps({
    title: { type: String, default: '' },
    width: { type: String, default: '520px' }, // pass '600px' for modal-wide, '440px' for modal-sm
  })
  defineEmits(['close'])
  </script>
  
  <style scoped>
  .fa-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(15,45,82,0.4);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    z-index: 100; padding: 24px;
  }
  .fa-modal {
    background: white;
    border-radius: 20px;
    padding: 28px;
    max-width: 100%;
    max-height: 88vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(15,45,82,0.2);
    box-sizing: border-box;
  }
  
  .fa-modal-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 22px; gap: 12px;
  }
  .fa-modal-head h3 { font-size: 1.05rem; color: #0F2D52; margin: 0; }
  
  .fa-modal-close {
    width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid #e2e8f0; background: transparent;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: #64748b; transition: background 0.15s;
    flex-shrink: 0;
  }
  .fa-modal-close:hover { background: #f1f5f9; }
  
  .fa-modal-actions {
    display: flex; justify-content: flex-end; gap: 10px;
    margin-top: 24px; flex-wrap: wrap;
  }
  </style>