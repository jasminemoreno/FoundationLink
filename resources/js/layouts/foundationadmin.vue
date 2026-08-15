<template>
  <div class="layout">

    <Sidebar />

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
import Sidebar from '@/components/foundation-admin/sidebar.vue'
import Header from '@/components/foundation-admin/header.vue'
import Footer from '@/components/foundation-admin/footer.vue'

import { useRouter } from 'vue-router'
import { onMounted } from 'vue'

const router = useRouter()

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
  background: #f0f4f9;
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
}
</style>