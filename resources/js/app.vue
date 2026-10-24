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
  background-color: #FFFFFF;
}
  
  #app {
    min-height: 100vh;
  }

  /* Edge adds its own "show password" eye (and a clear "x") to inputs. Every
     password box here has its own eye button, so hide Edge's to avoid two. */
  input::-ms-reveal,
  input::-ms-clear {
    display: none;
  }
  </style>