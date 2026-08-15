<!-- resources/js/App.vue -->
<template>
    <div id="app">
      <!-- Router will render the current page -->
      <router-view />
    </div>
  </template>
  
  <script>
  export default {
    name: 'App'
  }
  </script>
  
  <style>
  /* Optional global styles */
  body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f0f2f5;
  }
  
  #app {
    min-height: 100vh;
  }
  </style>