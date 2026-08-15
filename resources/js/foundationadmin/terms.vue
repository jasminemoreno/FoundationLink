<template>
  <LegalDocPage title="Terms of Service" :subtitle="subtitle" :sections="sections" />
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
    heading: '1. Acceptance of Terms',
    body: 'By registering your organization on FoundationLink and accessing the Foundation Admin panel, you agree to these Terms of Service on behalf of your organization.'
  },
  {
    heading: '2. Foundation Verification',
    body: 'All foundations must complete our verification process, including submission of identity and legitimacy documents, before campaigns can be created or donations received. FoundationLink reserves the right to reject, suspend, or request resubmission of verification at its discretion.'
  },
  {
    heading: '3. Accurate Representation',
    body: "You agree that all information about your organization, its campaigns, and its use of donated funds or items is truthful and not misleading. Misrepresentation may result in suspension or permanent removal from the platform."
  },
  {
    heading: '4. Handling of Donations',
    body: 'FoundationLink does not process or hold monetary donations. Payment arrangements occur directly between donors and your organization through the payment accounts you configure. You are responsible for confirming receipt of donations accurately and promptly in the system.'
  },
  {
    heading: '5. Campaign Responsibilities',
    body: 'Campaigns you create must accurately describe their purpose, goal, and how funds or items will be used. You are responsible for posting truthful updates and marking campaigns as paused or completed when appropriate.'
  },
  {
    heading: '6. Platform Oversight',
    body: "FoundationLink's administrators may review, suspend, or remove foundations or campaigns that violate these terms, applicable laws, or that receive credible reports of misconduct."
  },
  {
    heading: '7. Changes to These Terms',
    body: 'These terms may be updated from time to time. Continued use of the Foundation Admin panel after changes are posted constitutes acceptance of the revised terms.'
  },
  {
    heading: '8. Contact',
    body: 'Questions about these Terms can be sent to',
    link: { text: 'yoshiime77@gmail.com.com', href: 'mailto:yoshiime77@gmail.com' }
  },
]
</script>