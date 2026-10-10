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
            icon="🔍" :message="`No results found for &quot;${search}&quot;`"
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
          <router-link to="/foundation/contact" class="btn-contact">
            Contact Support
          </router-link>
        </div>

        <div class="contact-card">
          <div class="contact-icon">📋</div>
          <h3>Terms of Service</h3>
          <p>Read our terms and conditions.</p>
          <router-link to="/foundation/terms" class="btn-contact">View Terms</router-link>
        </div>

        <div class="contact-card">
          <div class="contact-icon">🔒</div>
          <h3>Privacy Policy</h3>
          <p>Learn how we protect your data.</p>
          <router-link to="/foundation/privacy" class="btn-contact">View Policy</router-link>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import EmptyState from '../components/foundation-admin/emptystate.vue'

const search         = ref('')
const activeCategory = ref('getting-started')
const openFaq        = ref(null)

function toggle(q) {
  openFaq.value = openFaq.value === q ? null : q
}

const quickLinks = [
  { icon: '🚀', title: 'Getting Started', desc: 'Registering your foundation', category: 'getting-started' },
  { icon: '✅', title: 'Verification',     desc: 'Getting verified',            category: 'verification'    },
  { icon: '📢', title: 'Campaigns',        desc: 'Creating & managing',         category: 'campaigns'       },
  { icon: '💳', title: 'Payments',         desc: 'Accepting donations',         category: 'payments'        },
]

const categories = [
  { key: 'getting-started', label: 'Getting Started', icon: '🚀' },
  { key: 'verification',    label: 'Verification',    icon: '✅' },
  { key: 'campaigns',       label: 'Campaigns',       icon: '📢' },
  { key: 'payments',        label: 'Payments',        icon: '💳' },
  { key: 'donations',       label: 'Donations',       icon: '🎁' },
  { key: 'account',         label: 'My Account',      icon: '👤' },
]

const faqs = [
  // GETTING STARTED
  { category: 'getting-started', q: 'How do I register my foundation?', a: 'Create an admin account, then fill in your foundation\'s name, description, category, and address. After that, you\'ll be asked to submit verification documents.' },
  { category: 'getting-started', q: 'Can I edit my foundation profile later?', a: 'Yes. Go to Settings to update your foundation\'s name, description, address, logo, and cover photo at any time.' },
  { category: 'getting-started', q: 'What is a foundation category?', a: 'It\'s your organization\'s general focus area (e.g. Education, Health, Disaster Relief), shown on your public profile. Individual campaigns can have their own category too, which may differ from your foundation\'s.' },

  // VERIFICATION
  { category: 'verification', q: 'Why do I need to be verified?', a: 'Verification confirms your foundation is legitimate before you can create campaigns or receive donations. It protects donors and builds trust in the platform.' },
  { category: 'verification', q: 'What documents do I need to submit?', a: 'You\'ll need a valid ID for the admin (National ID, Passport, or Driver\'s License) and a legitimacy document for your foundation (SEC Certificate, DSWD Accreditation, or similar — or "Other Legal Proof" if your organization doesn\'t have formal registration).' },
  { category: 'verification', q: 'How long does verification take?', a: 'Our super administrators review submissions as they come in. You\'ll see your foundation\'s status change from "Pending" to either "Verified" or "Rejected" on your dashboard.' },
  { category: 'verification', q: 'What happens if I\'m rejected?', a: 'You\'ll see the rejection reason on your dashboard, and you can edit your information and resubmit your documents for another review.' },

  // CAMPAIGNS
  { category: 'campaigns', q: 'When can I create a campaign?', a: 'Only after your foundation has been verified. You\'ll see a banner on the Campaigns page if verification is still pending.' },
  { category: 'campaigns', q: 'What\'s the difference between monetary, item, and both?', a: '"Monetary" accepts money only, "Item" accepts goods only, and "Both" lets donors choose either (or both) when donating to that campaign.' },
  { category: 'campaigns', q: 'Can a campaign have a different category than my foundation?', a: 'Yes. Your foundation\'s category is your general focus, but each campaign can be tagged with whatever category actually fits it — useful if you occasionally run campaigns outside your usual focus area.' },
  { category: 'campaigns', q: 'What does pausing a campaign do?', a: 'Pausing temporarily hides the Donate button from donors while you provide a reason (e.g. sorting existing donations). You can resume it at any time.' },
  { category: 'campaigns', q: 'Can I un-complete a campaign after marking it done?', a: 'No, marking a campaign as completed is final. Make sure it\'s truly finished before confirming.' },

  // PAYMENTS
  { category: 'payments', q: 'How do I set up payment accounts?', a: 'Go to Payment Accounts and add your account details for each payment method you want to accept (e.g. GCash, bank transfer).' },
  { category: 'payments', q: 'Do all campaigns need to accept the same payment methods?', a: 'No. When creating or editing a campaign, you choose which of your configured payment methods apply to that specific campaign.' },
  { category: 'payments', q: 'Does FoundationLink process the actual money?', a: 'No. Donors send payments directly using the account details you provide. FoundationLink only records that the donation was made and its status.' },

  // DONATIONS
  { category: 'donations', q: 'How do I confirm a donation was received?', a: 'Go to Donations and update the status once you\'ve confirmed receipt of the payment or item.' },
  { category: 'donations', q: 'What do the delivery methods mean for item donations?', a: '"Drop Off" means the donor brings items to your foundation\'s address. "Pick Up" means your foundation collects items from the donor\'s location. You choose which ones to accept per campaign.' },
  { category: 'donations', q: 'Can I see who has donated to my campaigns?', a: 'Yes, the Donors page shows everyone who has donated, along with their donation history with your foundation.' },
  { category: 'donations', q: 'What should I do if a donation looks fake or suspicious?', a: 'If the proof of payment or the item photo looks fake, update the donation status to "Cancelled" on the Donations page. To report the donor, email the platform administrator through Contact Support with the donor\'s name or email and what happened.' },

  // ACCOUNT
  { category: 'account', q: 'How do I change my foundation\'s logo or cover photo?', a: 'Go to Settings and upload a new logo or cover photo. The old one will be replaced automatically.' },
  { category: 'account', q: 'Can I have more than one admin per foundation?', a: 'Currently, each foundation is linked to a single admin account created at registration.' },
  { category: 'account', q: 'How do I report a problem with a donor or another account?', a: 'Email the platform administrator through Contact Support. Include the name or email of the account, the campaign or donation involved, and what happened. The Superadmin reviews the report and can suspend the account if needed.' },
  { category: 'account', q: 'How do I logout?', a: 'Click the logout button at the bottom of the sidebar.' },
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
.page { background: #f0f4f9; min-height: 100%; padding-bottom: 40px; box-sizing: border-box; }
.container { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }

/* HERO */
.help-hero { text-align: center; margin-bottom: 32px; }
.help-hero h1 { font-size: 1.8rem; font-weight: 800; color: #133c8a; margin: 0 0 8px; }
.help-hero p  { font-size: 0.9rem; color: #475569; margin: 0 0 20px; }

.search-box { display: flex; align-items: center; gap: 10px; background: white; border: 2px solid #e2e8f0; border-radius: 14px; padding: 12px 20px; max-width: 500px; margin: 0 auto; transition: border-color 0.2s; }
.search-box:focus-within { border-color: #1a56c4; box-shadow: 0 0 0 3px rgba(26,86,196,0.1); }
.search-box input { border: none; outline: none; background: transparent; font-size: 0.92rem; width: 100%; color: #133c8a; }
.search-box svg { color: #94a3b8; flex-shrink: 0; }

/* QUICK CARDS */
.quick-cards { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 28px; }
.qc-card  { background: white; border-radius: 14px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 4px rgba(15,45,82,0.06); border: 2px solid transparent; }
.qc-card:hover { border-color: #1a56c4; transform: translateY(-2px); }
.qc-icon  { font-size: 1.7rem; margin-bottom: 8px; }
.qc-title { font-size: 0.88rem; font-weight: 700; color: #133c8a; margin-bottom: 4px; }
.qc-desc  { font-size: 0.74rem; color: #94a3b8; }

/* FAQ LAYOUT */
.faq-layout { display: grid; grid-template-columns: 220px 1fr; gap: 20px; margin-bottom: 28px; }

/* SIDEBAR */
.faq-sidebar { display: flex; flex-direction: column; gap: 4px; }
.cat-btn { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border: none; background: white; color: #475569; font-size: 0.84rem; font-weight: 600; border-radius: 10px; cursor: pointer; text-align: left; transition: all 0.15s; width: 100%; }
.cat-btn:hover  { background: #eef4ff; color: #1a56c4; }
.cat-btn.active { background: #1a56c4; color: white; }

/* FAQ LIST */
.faq-list { display: flex; flex-direction: column; gap: 8px; }

.faq-item { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(15,45,82,0.06); border: 1px solid #e2e8f0; transition: border-color 0.2s; }
.faq-item.open { border-color: #1a56c4; }

.faq-question { display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 16px 20px; border: none; background: transparent; text-align: left; font-size: 0.9rem; font-weight: 700; color: #133c8a; cursor: pointer; gap: 12px; transition: background 0.15s; }
.faq-question:hover { background: #f4f8ff; }
.faq-item.open .faq-question { color: #1a56c4; }

.faq-answer { padding: 0 20px 16px; font-size: 0.85rem; color: #475569; line-height: 1.7; border-top: 1px solid #f4f8ff; padding-top: 12px; }

/* CONTACT */
.contact-section { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
.contact-card { background: white; border-radius: 16px; padding: 24px; text-align: center; box-shadow: 0 1px 4px rgba(15,45,82,0.06); }
.contact-icon { font-size: 1.9rem; margin-bottom: 10px; }
.contact-card h3 { font-size: 0.92rem; font-weight: 700; color: #133c8a; margin: 0 0 6px; }
.contact-card p  { font-size: 0.8rem; color: #94a3b8; margin: 0 0 14px; }
.btn-contact { display: inline-block; padding: 9px 20px; background: #1a56c4; color: white; border-radius: 10px; font-size: 0.83rem; font-weight: 700; text-decoration: none; transition: background 0.2s; }
.btn-contact:hover { background: #133c8a; }

@media (max-width: 768px) {
  .quick-cards    { grid-template-columns: repeat(2,1fr); }
  .faq-layout     { grid-template-columns: 1fr; }
  .faq-sidebar    { display: none; }
  .contact-section { grid-template-columns: 1fr; }
}
</style>