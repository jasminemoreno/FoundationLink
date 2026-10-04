<template>
  <div class="avatar-initials" :style="style">
    <img
      v-if="src && !imgError"
      :src="src"
      :alt="name"
      class="avatar-img"
      @error="imgError = true"
    />
    <span v-else>{{ initials }}</span>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { getInitials, avatarColor } from '@/composable/useAvatar'

const props = defineProps({
  name: { type: String, default: '' },
  // What to hash for a consistent color. Defaults to `name` if not given
  // (e.g. pass a foundation/user id instead for stability across renames).
  seed: { type: [String, Number], default: null },
  size: { type: Number, default: 38 },
  color: { type: String, default: '' }, // explicit override, skips hashing
  radius: { type: Number, default: null }, // px; defaults to ~28% of size
  src: { type: String, default: '' }, // optional real image URL (logo / profile photo)
})

const imgError = ref(false)

// Reset the error flag if a new src comes in (e.g. after an upload),
// so a previously-broken image doesn't permanently lock out a fixed one.
watch(() => props.src, () => { imgError.value = false })

const initials = computed(() => getInitials(props.name))
const bg = computed(() => props.color || avatarColor(props.seed ?? props.name))

const style = computed(() => ({
  width: props.size + 'px',
  height: props.size + 'px',
  minWidth: props.size + 'px',
  background: bg.value,
  borderRadius: (props.radius ?? Math.round(props.size * 0.28)) + 'px',
  fontSize: Math.round(props.size * 0.34) + 'px',
}))
</script>

<style scoped>
.avatar-initials {
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  flex-shrink: 0;
  overflow: hidden;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
</style>