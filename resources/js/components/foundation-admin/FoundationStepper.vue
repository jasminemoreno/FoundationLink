<template>
    <div class="stepper">
  
      <div class="step">
        <div class="circle" :class="{ active: currentStep >= 1 }">1</div>
        <span>Create Account</span>
      </div>
  
      <div class="line"></div>
  
      <div class="step">
        <div class="circle" :class="{ active: currentStep >= 2 }">2</div>
        <span>Foundation</span>
      </div>
  
      <div class="line"></div>
  
      <div class="step">
        <div class="circle" :class="{ active: currentStep >= 3 }">3</div>
        <span>Verification</span>
      </div>
  
    </div>
  </template>
  
  <script setup>
  defineProps({
    currentStep: Number
  })
  </script>
  
  <style scoped>
  .stepper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    width: 100%;
  }
  
  .step {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #777;
    white-space: nowrap;
  }
  
  .circle {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    transition: 0.3s;
  }
  
  .circle.active {
    background: #0d47a1;
    color: white;
  }
  
  .line {
    flex: 1;
    height: 2px;
    background: #ddd;
  }
  </style>