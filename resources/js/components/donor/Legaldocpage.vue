<template>
    <div class="page">
      <div class="container">
  
        <div class="page-head">
          <h1>{{ title }}</h1>
          <p>Last updated: {{ lastUpdated }}</p>
        </div>
  
        <div class="doc">
          <section v-for="(s, i) in sections" :key="i">
            <h2>{{ s.heading }}</h2>
            <p>
              {{ s.body }}
              <a v-if="s.link" :href="s.link.href">{{ s.link.text }}</a>
            </p>
          </section>
        </div>
  
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  // Usage (e.g. in PrivacyPolicy.vue):
  //
  // 1. Import LegalDocPage from '../components/donor/legaldocpage.vue'
  // 2. Define a sections array, e.g.:
  //    const sections = [
  //      { heading: '1. Information We Collect', body: 'When you register...' },
  //      ...
  //      { heading: '8. Contact', body: 'Questions about this Privacy Policy can be sent to',
  //        link: { text: 'support@foundationlink.com', href: 'mailto:support@foundationlink.com' } },
  //    ]
  // 3. In the page's own template, render the component with title and :sections bound.
  
  defineProps({
    title:    { type: String, required: true },
    sections: { type: Array,  required: true },
  })
  
  const lastUpdated = computed(() =>
    new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
  )
  </script>
  
  <style scoped>
  .page { background: #c8f0e0; min-height: 100vh; padding-bottom: 40px; }
  .container { max-width: 720px; margin: 0 auto; padding: 40px 24px; }
  
  .page-head { margin-bottom: 28px; }
  .page-head h1 { font-size: 1.6rem; font-weight: 900; color: #0e5c36; margin: 0 0 6px; }
  .page-head p  { font-size: 0.82rem; color: #5b7568; margin: 0; }
  
  .doc { background: white; border-radius: 18px; padding: 32px; box-shadow: 0 3px 12px rgba(0,80,40,0.08); }
  
  section { margin-bottom: 22px; }
  section:last-child { margin-bottom: 0; }
  
  h2 { font-size: 1rem; font-weight: 800; color: #0F2D52; margin: 0 0 8px; }
  p  { font-size: 0.87rem; color: #475569; line-height: 1.7; margin: 0; }
  a  { color: #1a8a52; font-weight: 600; text-decoration: none; }
  a:hover { text-decoration: underline; }
  </style>