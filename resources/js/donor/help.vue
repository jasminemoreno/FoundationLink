<template>
  <div class="page">
    <div class="container">

      <!-- HEADER -->
      <div class="help-hero">
        <h1>How can we help you?</h1>
        <p>Search for answers or browse topics below</p>
        <div class="search-box">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input v-model="search" placeholder="Search help topics..." />
        </div>
      </div>

      <!-- QUICK LINKS -->
      <div class="quick-cards" v-if="!search">
        <div class="qc-card" v-for="q in quickLinks" :key="q.title" @click="activeCategory = q.category">
          <div class="qc-icon">{{ q.icon }}</div>
          <div class="qc-title">{{ q.title }}</div>
          <div class="qc-desc">{{ q.desc }}</div>
        </div>
      </div>

      <!-- FAQ SECTIONS -->
      <div class="faq-layout">

        <!-- CATEGORY SIDEBAR -->
        <div class="faq-sidebar" v-if="!search">
          <button
            v-for="cat in categories" :key="cat.key"
            class="cat-btn"
            :class="{ active: activeCategory === cat.key }"
            @click="activeCategory = cat.key"
          >
            <span>{{ cat.icon }}</span>
            {{ cat.label }}
          </button>
        </div>

        <!-- FAQ LIST -->
        <div class="faq-list">
          <EmptyState
            v-if="filteredFaqs.length === 0"
            icon="🔍" :message="`No results found for &quot;${search}&quot;`" :card="false"
          />

          <div
            v-for="faq in filteredFaqs" :key="faq.q"
            class="faq-item"
            :class="{ open: openFaq === faq.q }"
          >
            <button class="faq-question" @click="toggle(faq.q)">
              <span>{{ faq.q }}</span>
              <svg
                width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                :style="{ transform: openFaq === faq.q ? 'rotate(180deg)' : 'rotate(0)', transition: 'transform 0.2s' }"
              >
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </button>
            <div v-if="openFaq === faq.q" class="faq-answer">
              {{ faq.a }}
            </div>
          </div>
        </div>

      </div>

      <!-- CONTACT SECTION -->
      <div class="contact-section">
        <div class="contact-card">
          <div class="contact-icon">📧</div>
          <h3>Still need help?</h3>
          <p>Our support team is ready to assist you.</p>
          <router-link to="/donor/contact" class="btn-contact">
            Contact Support
          </router-link>
        </div>

        <div class="contact-card">
          <div class="contact-icon">📋</div>
          <h3>Terms of Service</h3>
          <p>Read our terms and conditions.</p>
          <router-link to="/donor/service" class="btn-contact">View Terms</router-link>
        </div>

        <div class="contact-card">
          <div class="contact-icon">🔒</div>
          <h3>Privacy Policy</h3>
          <p>Learn how we protect your data.</p>
          <router-link to="/donor/privacy" class="btn-contact">View Policy</router-link>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import EmptyState from '../components/donor/emptystate.vue'

const search         = ref('')
const activeCategory = ref('getting-started')
const openFaq        = ref(null)

function toggle(q) {
  openFaq.value = openFaq.value === q ? null : q
}

const quickLinks = [
  { icon: '🚀', title: 'Getting Started',   desc: 'New to FoundationLink?',        category: 'getting-started' },
  { icon: '💰', title: 'Making Donations',  desc: 'How to donate',                 category: 'donations'       },
  { icon: '📦', title: 'Item Donations',    desc: 'Donating goods',                category: 'items'           },
  { icon: '👤', title: 'My Account',        desc: 'Profile & settings',            category: 'account'         },
]

const categories = [
  { key: 'getting-started', label: 'Getting Started', icon: '🚀' },
  { key: 'donations',       label: 'Donations',        icon: '💰' },
  { key: 'items',           label: 'Item Donations',   icon: '📦' },
  { key: 'account',         label: 'My Account',       icon: '👤' },
  { key: 'campaigns',       label: 'Campaigns',        icon: '📢' },
  { key: 'security',        label: 'Security',         icon: '🔒' },
]

const faqs = [
  // GETTING STARTED
  { category: 'getting-started', q: 'What is FoundationLink?', a: 'FoundationLink is a platform that connects donors with verified foundations and their campaigns. You can donate money or items to causes you care about.' },
  { category: 'getting-started', q: 'How do I create an account?', a: 'Click "Register" on the login page, select "Donor", fill in your personal information, and verify your email. It only takes a few minutes!' },
  { category: 'getting-started', q: 'Is FoundationLink free to use?', a: 'Yes! Creating an account and donating through FoundationLink is completely free for donors.' },
  { category: 'getting-started', q: 'How do I find campaigns to support?', a: 'Browse the Campaigns page to see all active campaigns. You can filter by type (monetary or item) to find causes that match what you want to give.' },

  // DONATIONS
  { category: 'donations', q: 'How do I make a monetary donation?', a: 'Go to the Campaigns page, find a campaign you want to support, click "Donate Now", select the amount (or enter a custom amount), and submit. The foundation will be notified.' },
  { category: 'donations', q: 'What payment methods are accepted?', a: 'Currently donations are recorded through the platform and payment arrangements are made directly with the foundation. Contact the foundation for payment details.' },
  { category: 'donations', q: 'Can I see my donation history?', a: 'Yes! Go to My Donations to see all your past and current donations, their status, and details.' },
  { category: 'donations', q: 'What does "Pending" status mean?', a: '"Pending" means your donation has been submitted and is waiting for the foundation to confirm receipt. Once they mark it as received, the status will update to "Received".' },
  { category: 'donations', q: 'Can I cancel a donation?', a: 'Once a donation is submitted, cancellation depends on the foundation. Contact the foundation directly if you need to cancel.' },

  // ITEMS
  { category: 'items', q: 'How do I donate items?', a: 'Find a campaign that accepts item donations, click "Donate Now", select "Item", fill in the item name, quantity, and description, then submit. The foundation will contact you for drop-off arrangements.' },
  { category: 'items', q: 'What kinds of items can I donate?', a: 'It depends on what each campaign needs. Common items include clothes, food, medicine, school supplies, and household goods. Check the campaign description for specific needs.' },
  { category: 'items', q: 'Where do I drop off my items?', a: 'After submitting an item donation, the foundation will reach out to arrange drop-off or pick-up. You can also add a note in your donation with your contact details.' },

  // ACCOUNT
  { category: 'account', q: 'How do I update my profile?', a: 'Go to My Profile from the top navigation. You can update your name, phone, address, gender, and birthdate there.' },
  { category: 'account', q: 'How do I change my password?', a: 'In My Profile, scroll down to the "Change Password" section, enter your new password, confirm it, and click Update Password.' },
  { category: 'account', q: 'Can I change my email address?', a: 'Email cannot be changed once registered as it serves as your unique identifier. Contact support if you need assistance.' },
  { category: 'account', q: 'How do I logout?', a: 'Click your name in the top right corner, then click "Logout" from the dropdown menu.' },

  // CAMPAIGNS
  { category: 'campaigns', q: 'What is a "paused" campaign?', a: 'A paused campaign is temporarily not accepting donations. The foundation may be sorting existing donations or handling logistics. It will resume accepting donations when the foundation reactivates it.' },
  { category: 'campaigns', q: 'What happens when a campaign reaches its goal?', a: 'When a monetary campaign reaches its goal amount, the foundation may mark it as completed. Item campaigns continue until the foundation marks them as completed.' },
  { category: 'campaigns', q: 'Can I donate to the same campaign multiple times?', a: 'Yes! You can donate to the same campaign as many times as you like, whether monetary or item donations.' },

  // SECURITY
  { category: 'security', q: 'Is my personal information safe?', a: 'Yes. We take your privacy seriously. Your personal information is encrypted and never shared with third parties without your consent.' },
  { category: 'security', q: 'Are the foundations on FoundationLink verified?', a: 'Yes. All foundations go through a verification process by our administrators before they can create campaigns and receive donations.' },
  { category: 'security', q: 'What should I do if I notice suspicious activity?', a: 'Contact our support team immediately at support@foundationlink.com. Change your password right away from your Profile page.' },
]

const filteredFaqs = computed(() => {
  if (search.value) {
    const q = search.value.toLowerCase()
    return faqs.filter(f =>
      f.q.toLowerCase().includes(q) ||
      f.a.toLowerCase().includes(q)
    )
  }
  return faqs.filter(f => f.category === activeCategory.value)
})
</script>

<style scoped>
.page { background: #c8f0e0; min-height: 100vh; padding-bottom: 40px; }
.container { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }

/* HERO */
.help-hero { text-align: center; margin-bottom: 36px; }
.help-hero h1 { font-size: 2rem; font-weight: 900; color: #0e5c36; margin: 0 0 8px; }
.help-hero p  { font-size: 0.95rem; color: #475569; margin: 0 0 20px; }

.search-box { display: flex; align-items: center; gap: 10px; background: white; border: 2px solid #e2e8f0; border-radius: 14px; padding: 12px 20px; max-width: 500px; margin: 0 auto; transition: border-color 0.2s; }
.search-box:focus-within { border-color: #1a8a52; box-shadow: 0 0 0 3px rgba(26,138,82,0.1); }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.95rem; width: 100%; color: #0F2D52; }
.search-box svg { color: #94a3b8; flex-shrink: 0; }

/* QUICK CARDS */
.quick-cards { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 32px; }
.qc-card  { background: white; border-radius: 14px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,80,40,0.06); border: 2px solid transparent; }
.qc-card:hover { border-color: #1a8a52; transform: translateY(-2px); }
.qc-icon  { font-size: 1.8rem; margin-bottom: 8px; }
.qc-title { font-size: 0.9rem; font-weight: 800; color: #0F2D52; margin-bottom: 4px; }
.qc-desc  { font-size: 0.75rem; color: #94a3b8; }

/* FAQ LAYOUT */
.faq-layout { display: grid; grid-template-columns: 220px 1fr; gap: 20px; margin-bottom: 32px; }

/* SIDEBAR */
.faq-sidebar { display: flex; flex-direction: column; gap: 4px; }
.cat-btn { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border: none; background: white; color: #475569; font-size: 0.85rem; font-weight: 600; border-radius: 10px; cursor: pointer; text-align: left; transition: all 0.15s; width: 100%; }
.cat-btn:hover  { background: #f0fdf4; color: #1a8a52; }
.cat-btn.active { background: #1a8a52; color: white; }

/* FAQ LIST */
.faq-list { display: flex; flex-direction: column; gap: 8px; }

.faq-item { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,80,40,0.06); border: 1px solid #e2e8f0; transition: border-color 0.2s; }
.faq-item.open { border-color: #1a8a52; }

.faq-question { display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 16px 20px; border: none; background: transparent; text-align: left; font-size: 0.92rem; font-weight: 700; color: #0F2D52; cursor: pointer; gap: 12px; transition: background 0.15s; }
.faq-question:hover { background: #f0fdf4; }
.faq-item.open .faq-question { color: #1a8a52; }

.faq-answer { padding: 0 20px 16px; font-size: 0.87rem; color: #475569; line-height: 1.7; border-top: 1px solid #f0fdf4; padding-top: 12px; }

/* CONTACT */
.contact-section { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
.contact-card { background: white; border-radius: 16px; padding: 24px; text-align: center; box-shadow: 0 2px 8px rgba(0,80,40,0.06); }
.contact-icon { font-size: 2rem; margin-bottom: 10px; }
.contact-card h3 { font-size: 0.95rem; font-weight: 800; color: #0F2D52; margin: 0 0 6px; }
.contact-card p  { font-size: 0.82rem; color: #94a3b8; margin: 0 0 14px; }
.btn-contact { display: inline-block; padding: 9px 20px; background: #1a8a52; color: white; border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-decoration: none; transition: background 0.2s; }
.btn-contact:hover { background: #157042; }

@media (max-width: 768px) {
  .quick-cards    { grid-template-columns: repeat(2,1fr); }
  .faq-layout     { grid-template-columns: 1fr; }
  .faq-sidebar    { display: none; }
  .contact-section { grid-template-columns: 1fr; }
}
</style>