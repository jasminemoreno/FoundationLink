import { ref } from 'vue'

// Shared across Topbar + the 4 searchable admin pages.
// One query, whichever page is mounted reads/filters with it.
const searchQuery = ref('')

export function useSearch() {
  return { searchQuery }
}