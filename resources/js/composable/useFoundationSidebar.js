// Shared open/closed state for the foundation admin sidebar on small screens
// (phones / small tablets), so the header's menu button, the sidebar and the
// layout's dark backdrop all stay in sync. On wide screens the sidebar is
// always visible and this state is ignored.
import { ref } from "vue"

const open = ref(false)

export function useFoundationSidebar() {
  return {
    sidebarOpen: open,
    openSidebar: () => { open.value = true },
    closeSidebar: () => { open.value = false },
    toggleSidebar: () => { open.value = !open.value },
  }
}
