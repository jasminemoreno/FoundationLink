  import { createRouter, createWebHistory } from 'vue-router'

  // AUTH
  import LoginPage from '../login.vue'
  import RegisterPage from '../foundationadmin/registration.vue'
  import Role from '../role.vue'
  import ForgotPassword from '../forgotpassword.vue'

 
  // SUPER ADMIN
  import SuperAdmin from '../layouts/superadmin.vue'
  import Dashboard from '../superadmin/dashboard.vue'
  import Approval from '../superadmin/approvals.vue'
  import Foundation from '../superadmin/foundations.vue'
  import Campaign from '../superadmin/campaigns.vue'
  import Category from '../superadmin/categories.vue'
  import User from '../superadmin/users.vue'
  import Report from '../superadmin/reports.vue'
  import Setting from '../superadmin/settings.vue'
  import PaymentMethod from '../superadmin/paymentmethod.vue'

  // FOUNDATION ADMIN
  import CreateFoundation from '../foundationadmin/CreateFoundation.vue'
  import Verification from '../foundationadmin/Verification.vue'
  import FoundationAdmin from '../layouts/foundationadmin.vue'
  import FoundationDashboard from '../foundationadmin/dashboard.vue'
  import FoundationCampaign from '../foundationadmin/Campaign.vue'
  import FoundationDonation from '../foundationadmin/donation.vue'
  import FoundationReport from '../foundationadmin/report.vue'
  import FoundationSetting from '../foundationadmin/setting.vue'
  import FoundationDonor from '../foundationadmin/donor.vue'
  import FoundationTerm from '../foundationadmin/terms.vue'
  import FoundationPrivacy from '../foundationadmin/privacy.vue'
  import FoundationHelp from '../foundationadmin/help.vue'
  import FoundationContact from '../foundationadmin/contact.vue'
  import FoundationAdminProfile from '../foundationadmin/profile.vue'

  // DONOR
  import Register from '../donor/register.vue'
  import Donor from '../layouts/donor.vue'
  import DonorDashboard from '../donor/dashboard.vue'
  import DonorCampaign from '../donor/campaign.vue'
  import DonorDonation from '../donor/donations.vue'
  import Update from '../donor/update.vue'
  import Notification from '../donor/notification.vue'
  import Profile from '../donor/profile.vue'
  import Help from '../donor/help.vue'
  import FoundationProfile from '../components/donor/foundationprofile.vue'
  import FoundationPage from '../donor/foundations.vue'
  import Service from '../donor/service.vue'
  import Privacy from '../donor/privacy.vue'
  import Contact from '../donor/contact.vue'


  const routes = [
    { path: '/', redirect: '/login' },

    { path: '/login',            name: 'login',                 component: LoginPage       },
    { path: '/role',             name: 'role',                  component: Role            },
    { path: '/donor/register',   name: 'donor.register',        component: Register        },
    { path: '/foundation/register',    name: 'foundation.register',   component: RegisterPage    },
    { path: '/foundation/create',      name: 'foundation.create',     component: CreateFoundation },
    { path: '/foundation/verification',name: 'foundation.verification',component: Verification   },
    { path: '/forgot-password',        name: 'forgotpassword',         component: ForgotPassword },


    // ── SUPER ADMIN ──
    {
      path: '/admin',
      component: SuperAdmin,
      meta: { requiresAuth: true, role: 'superadmin' },
      children: [
        { path: 'dashboard', name: 'admin-dashboard', component: Dashboard },
        { path: 'approval',  name: 'admin-approval',  component: Approval  },
        { path: 'foundation',name: 'foundation',      component: Foundation },
        { path: 'campaign',  name: 'campaign',        component: Campaign  },
        { path: 'category',  name: 'category',        component: Category  },
        { path: 'user',      name: 'user',            component: User      },
        { path: 'report',    name: 'report',          component: Report    },
        { path: 'setting',   name: 'setting',         component: Setting   },
        { path: 'payment-method', name: 'payment-method', component: PaymentMethod},
      ]
    },

    // ── FOUNDATION ADMIN ──
    {
      path: '/foundation',
      component: FoundationAdmin,
      meta: { requiresAuth: true, role: 'foundation_admin' },
      children: [
        { path: 'dashboard', name: 'foundation-dashboard', component: FoundationDashboard },
        { path: 'campaign',  name: 'foundation-campaign',  component: FoundationCampaign  },
        { path: 'donor',     name: 'foundation-donor',     component: FoundationDonor     },
        { path: 'donation',  name: 'foundation-donation',  component: FoundationDonation  },
        { path: 'report',    name: 'foundation-report',    component: FoundationReport    },
        { path: 'setting',   name: 'foundation-setting',   component: FoundationSetting   },
        { path: 'help',      name: 'foundation-help',      component: FoundationHelp      },
        { path: 'privacy',   name: 'foundation-privacy',   component: FoundationPrivacy   },
        { path: 'terms',     name: 'foundation-terms',     component: FoundationTerm      },
        { path: 'contact',   name: 'foundation-contact',   component: FoundationContact   },
        { path: 'profile',   name: 'foundation-profile',   component: FoundationAdminProfile   },
      ]


    },

    // ── DONOR ──
    {
      path: '/donor',
      component: Donor,
      meta: { requiresAuth: true, role: 'donor' },
      children: [
        // ← removed leading / from all paths
        { path: 'dashboard',     name: 'donor-dashboard',     component: DonorDashboard },
        { path: 'campaigns',     name: 'donor-campaigns',     component: DonorCampaign  },
        { path: 'donations',     name: 'donor-donations',     component: DonorDonation  },
        { path: 'updates',       name: 'donor-updates',       component: Update         },
        { path: 'notifications', name: 'donor-notifications', component: Notification   },
        { path: 'profile',       name: 'donor-profile',       component: Profile        },
        { path: 'help',          name: 'donor-help',          component: Help           },
        { path: 'foundations/:id', name: 'donor-foundation',  component: FoundationProfile},    
        { path: 'foundations',   name: 'donor-foundations',   component: FoundationPage},
        { path: 'service',       name: 'donor-service',       component: Service},
        { path: 'privacy',       name: 'donor-privacy',       component: Privacy},
        { path: 'contact',       name: 'donor-contact',       component: Contact},
        
      ]
    }
  ]

  const router = createRouter({
    history: createWebHistory(),
    routes
  })

  // ← modern syntax, no next() callback
  router.beforeEach((to, from) => {
    const token = sessionStorage.getItem('token')
    const user  = JSON.parse(sessionStorage.getItem('user') || 'null')

    // protected route — not logged in
    if (to.matched.some(r => r.meta.requiresAuth)) {
      if (!token) return '/login'

      // wrong role
      const requiredRole = to.matched.find(r => r.meta.role)?.meta.role
      if (requiredRole && user?.role !== requiredRole) return '/login'
    }

    // already logged in — redirect away from login/role pages
    if (token && (to.path === '/login' || to.path === '/role')) {
      if (user?.role === 'superadmin')       return '/admin/dashboard'
      if (user?.role === 'foundation_admin') return '/foundation/dashboard'
      if (user?.role === 'donor')            return '/donor/dashboard'
    }
  })

  export default router