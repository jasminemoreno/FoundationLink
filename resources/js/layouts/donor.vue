<template>
    <div class="donor-app">
      <DonorTopbar />
      <main class="donor-main">
        <router-view />
      </main>
      <DonorFooter />
    </div>
  </template>
  
  <script setup>
  import DonorTopbar from '../components/donor/topbar.vue'
  import DonorFooter from '../components/donor/footer.vue'
  </script>
  
  <style scoped>
  .donor-app  { display: flex; flex-direction: column; min-height: 100vh; background: #c8f0e0; }
  .donor-main { flex: 1; }
  </style>