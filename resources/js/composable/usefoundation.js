import { ref } from 'vue'
import api from '@/services/api'

// Module-level state = shared across every component that imports this,
// without needing Pinia/Vuex. Fetches once per session unless forced.
const foundation = ref(null)
const loading = ref(false)
let fetched = false

async function loadFoundation(force = false) {
  if (fetched && !force) return foundation.value
  loading.value = true
  try {
    const res = await api.get('/foundation/settings')
    foundation.value = res.data
    fetched = true
  } catch (err) {
    console.error('Failed to load foundation:', err)
  } finally {
    loading.value = false
  }
  return foundation.value
}

function getLogoUrl() {
  if (!foundation.value?.logo) return null
  return `/storage/${foundation.value.logo}`
}

export function useFoundation() {
  return {
    foundation,
    loading,
    loadFoundation,
    getLogoUrl,
  }
}