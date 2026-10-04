<template>
  <div
    class="fa-avatar"
    :style="{ width: size + 'px', height: size + 'px', background: bgColor, fontSize: fontSize, borderRadius: radius }"
  >
    <img
      v-if="src && !imgError"
      :src="src"
      :alt="initials"
      class="fa-avatar-img"
      @error="imgError = true"
    />
    <template v-else>{{ initials }}</template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

// `seed` picks the background color (pass the donor/donation id, same as the
// avatarColor() helper every page previously duplicated). `size` covers the
// default/lg/xl variants seen across pages (36 / 48 / 56).
// `src` is an optional real photo URL (e.g. the donor's profile photo);
// when it's empty or fails to load, the initials are shown instead.
const props = defineProps({
  initials: { type: String, required: true },
  seed:     { type: [String, Number], default: 0 },
  size:     { type: Number, default: 36 },
  src:      { type: String, default: '' },
})

const imgError = ref(false)

// Reset the error flag when a new src arrives so a previously-broken
// image doesn't permanently lock out a fixed one.
watch(() => props.src, () => { imgError.value = false })

const palette = ["#3b82f6","#059669","#ca8a04","#7c3aed","#dc2626","#0891b2","#db2777"]

const bgColor = computed(() => {
  const n = typeof props.seed === 'number' ? props.seed : String(props.seed).length
  return palette[n % palette.length]
})

const fontSize = computed(() => Math.max(10, Math.round(props.size * 0.32)) + 'px')
const radius   = computed(() => Math.round(props.size * 0.28) + 'px')
</script>

<style scoped>
.fa-avatar {
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  flex-shrink: 0;
}

/* Photo fills the avatar and inherits its rounded corners */
.fa-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border-radius: inherit;
}
</style>