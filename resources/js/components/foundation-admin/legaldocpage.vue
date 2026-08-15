<template>
    <div class="page">
      <div class="container">
  
        <div class="page-head">
          <h1>{{ title }}</h1>
          <p>{{ subtitle }}</p>
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
  // Usage (e.g. in PrivacyPolicy.vue):
  //
  // 1. Import LegalDocPage from '../components/foundation-admin/legaldocpage.vue'
  // 2. Compute lastUpdated and pass the full subtitle string yourself, since the
  //    two foundation-admin legal pages share the "For Foundation Administrators ·"
  //    prefix that donor-side legal pages don't have:
  //
  //    const lastUpdated = computed(() =>
  //      new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
  //    )
  //    const subtitle = computed(() => `For Foundation Administrators · Last updated: ${lastUpdated.value}`)
  //
  // 3. Define a sections array (see donor LegalDocPage for the exact shape) and
  //    render <LegalDocPage title="Privacy Policy" :subtitle="subtitle" :sections="sections" />
  
  defineProps({
    title:    { type: String, required: true },
    subtitle: { type: String, required: true },
    sections: { type: Array,  required: true },
  })
  </script>
  
  <style scoped>
  .page { background: #f0f4f9; min-height: 100%; padding-bottom: 40px; box-sizing: border-box; }
  .container { max-width: 720px; margin: 0 auto; padding: 32px 24px; }
  
  .page-head { margin-bottom: 24px; }
  .page-head h1 { font-size: 1.45rem; font-weight: 800; color: #133c8a; margin: 0 0 6px; }
  .page-head p  { font-size: 0.8rem; color: #64748b; margin: 0; }
  
  .doc { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); }
  
  section { margin-bottom: 20px; }
  section:last-child { margin-bottom: 0; }
  
  h2 { font-size: 0.95rem; font-weight: 700; color: #1a56c4; margin: 0 0 8px; }
  p  { font-size: 0.85rem; color: #475569; line-height: 1.7; margin: 0; }
  a  { color: #1a56c4; font-weight: 600; text-decoration: none; }
  a:hover { text-decoration: underline; }
  </style>