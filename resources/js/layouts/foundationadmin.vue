<template>
  <div class="layout">

    <Sidebar />

    <!-- Small screens: dark backdrop behind the slide-in menu; tap it to close -->
    <div v-if="sidebarOpen" class="sidebar-backdrop" @click="closeSidebar" aria-hidden="true"></div>

    <div class="main">

      <Header />

      <div class="content">
        <router-view />
      </div>

      <Footer />

    </div>

  </div>
</template>

<script setup>
import '@/assets/foundation-theme.css'
import Sidebar from '@/components/foundation-admin/sidebar.vue'
import Header from '@/components/foundation-admin/header.vue'
import Footer from '@/components/foundation-admin/footer.vue'

import { useRouter, useRoute } from 'vue-router'
import { onMounted, watch } from 'vue'
import { useFoundationSidebar } from '@/composable/useFoundationSidebar'

const router = useRouter()
const route = useRoute()
const { sidebarOpen, closeSidebar } = useFoundationSidebar()

// Going to another page closes the slide-in menu on small screens
watch(() => route.fullPath, closeSidebar)

onMounted(() => {

    const token = sessionStorage.getItem('token')
    const user = JSON.parse(sessionStorage.getItem('user') || 'null')

    if (!token || user?.role !== 'foundation_admin') {
        router.push('/login')
    }
})

</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
  background: var(--fa-bg);
  color: var(--fa-text);
}

/* Sidebar is now position: fixed with width 290px,
   so .main needs to be pushed over by the same amount
   instead of relying on flex to make room for it. */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  margin-left: 290px;
}

/* Page content */
.content {
  flex: 1;
  padding: 24px;
  min-width: 0;
}

.sidebar-backdrop { display: none; }

/* Phones / small tablets: the sidebar becomes a slide-in menu (see sidebar.vue),
   so the page uses the full width. */
@media (max-width: 1023px) {
  .main { margin-left: 0; }
  .content { padding: 16px; }
  .sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 90;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
  }
}
</style>