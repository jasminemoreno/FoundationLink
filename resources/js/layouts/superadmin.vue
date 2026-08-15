<template>
    <div class="layout">
  
      <Sidebar />
  
      <div class="main">
  
        <Topbar />
  
        <div class="content">
          <router-view />
        </div>
  
      </div>
  
    </div>
  </template>
  
  <script setup>
  import Sidebar from '@/components/superadmin/Sidebar.vue'
  import Topbar from '@/components/superadmin/Topbar.vue'

 
import { useRouter } from 'vue-router'
import { onMounted } from 'vue'

const router = useRouter()

onMounted(() => {

    const token = sessionStorage.getItem('token')
    const user = JSON.parse(sessionStorage.getItem('user') || 'null')

    if (!token || user?.role !== 'superadmin') {
        router.push('/login')
    }
})

  </script>
  
  <style scoped>
  .layout{
    display:flex;
    min-height:100vh;
    background:#f8fafc;
  }
  
  .main{
    flex:1;
    display:flex;
    flex-direction:column;
    margin-left: 265px;   /* add this — matches sidebar width */
  }
  
  .content{
    padding:30px;
    flex:1;
  }
  </style>