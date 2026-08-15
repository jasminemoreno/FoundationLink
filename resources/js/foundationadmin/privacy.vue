<template>
  <LegalDocPage title="Privacy Policy" :subtitle="subtitle" :sections="sections" />
</template>

<script setup>
import { computed } from 'vue'
import LegalDocPage from '../components/foundation-admin/legaldocpage.vue'

const lastUpdated = computed(() =>
  new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
)
const subtitle = computed(() => `For Foundation Administrators · Last updated: ${lastUpdated.value}`)

const sections = [
  {
    heading: '1. Information We Collect',
    body: "When you register your organization, we collect your foundation's name, description, category, address, and any identity or legitimacy documents submitted during verification. We also record campaign details and donation records associated with your foundation."
  },
  {
    heading: '2. How We Use Your Information',
    body: "Your information is used to verify your organization's legitimacy, operate your foundation's dashboard, display your campaigns to donors, and generate reports on donations received."
  },
  {
    heading: '3. Sharing of Information',
    body: "Your foundation's name, description, category, and public campaign details are visible to donors browsing the platform. Verification documents (identity and legitimacy) are reviewed only by FoundationLink administrators and are not made public."
  },
  {
    heading: '4. Donor Information',
    body: 'When a donor contributes to your campaign, you receive the donation details relevant to fulfilling it — such as donor name, contact information, amount or item details, and delivery preferences. This information should only be used to process and confirm that specific donation.'
  },
  {
    heading: '5. Data Storage and Security',
    body: "We take reasonable measures to protect your organization's information and documents from unauthorized access. Passwords are stored securely and are never visible to other users or administrators."
  },
  {
    heading: '6. Your Choices',
    body: "You can update your foundation's profile information from the Settings page at any time. Verification documents can be resubmitted if your foundation's status changes."
  },
  {
    heading: '7. Changes to This Policy',
    body: 'This policy may be updated from time to time. Continued use of the Foundation Admin panel after changes are posted constitutes acceptance of the revised policy.'
  },
  {
    heading: '8. Contact',
    body: 'Questions about this Privacy Policy can be sent to',
    link: { text: 'yoshiime77@gmail.com', href: 'mailto:yoshiime77@gmail.com' }
  },
]
</script>