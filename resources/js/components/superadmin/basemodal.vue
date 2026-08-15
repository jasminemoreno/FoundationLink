<template>
    <Teleport to="body">
      <div v-if="modelValue" class="base-modal-overlay" @click.self="handleOverlayClick">
        <div class="base-modal" :style="{ width, maxHeight }">
          <div v-if="$slots.header" class="base-modal-header">
            <slot name="header" />
          </div>
          <div class="base-modal-body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="base-modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Teleport>
  </template>
  
  <script setup>
  // Deliberately unopinionated: it only handles the overlay, the white card,
  // backdrop-click-to-close, and slot layout. It does NOT style a title,
  // close button, or avatar — those differ enough page to page (Campaign's
  // gradient hero header vs. Category's plain <h3> vs. Foundation's avatar
  // header) that forcing one visual style would fight your existing designs.
  // Put whatever header markup you already have into the `header` slot as-is.
  
  const props = defineProps({
    modelValue: { type: Boolean, default: false }, // v-model
    width: { type: String, default: '460px' },
    maxHeight: { type: String, default: '85vh' },
    closeOnOverlay: { type: Boolean, default: true },
  })
  const emit = defineEmits(['update:modelValue'])
  
  function handleOverlayClick() {
    if (props.closeOnOverlay) emit('update:modelValue', false)
  }
  </script>
  
  <style scoped>
  .base-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,45,82,0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 20px;
  }
  
  .base-modal {
    background: white;
    border-radius: 20px;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(15,45,82,0.2);
    display: flex;
    flex-direction: column;
  }
  
  .base-modal-header { flex-shrink: 0; }
  
  .base-modal-body {
    padding: 28px;
    flex: 1;
    overflow-y: auto;
  }
  /* If a header slot is used, don't double up on top padding */
  .base-modal-header + .base-modal-body { padding-top: 0; }
  
  .base-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 0 28px 28px;
    flex-shrink: 0;
  }
  </style>