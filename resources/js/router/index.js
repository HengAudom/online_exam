import { createRouter, createWebHistory } from 'vue-router'
import Login from '../auth/Login.vue'
import Register from '../auth/Register.vue'
import ForgotPassword from '../auth/ForgotPassword.vue'
import axios from 'axios'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: {
      title: 'OnlineXam - Online Examination System',
      description: 'OnlineXam - ប្រព័ន្ធគ្រប់គ្រងការប្រឡងអនឡាញ រៀបចំការប្រឡង វាយតម្លៃលទ្ធផលសិស្ស និងគ្រប់គ្រងទិន្នន័យប្រឡងដោយសុវត្ថិភាព។'
    }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: {
      title: 'Register - OnlineXam',
      description: 'OnlineXam - ចុះឈ្មោះបង្កើតគណនីប្រឡងថ្មី'
    }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPassword,
    meta: {
      title: 'Forgot Password - OnlineXam'
    }
  },
  {
    path: '/reset-password',
    redirect: '/forgot-password'
  },

  // ── Lucky Wheel Game (Full-Screen Dedicated Room) ─────────────────
  {
    path: '/admin/lucky-wheel',
    name: 'LuckyWheelGame',
    component: () => import('../admin/LuckyWheelGame.vue'),
    meta: {
      requiresAuth: true,
      title: 'ទាយពាក្យ - កងវិលសំណាង | OnlineXam'
    }
  },
  {
    path: '/wheel-remote',
    name: 'WheelRemoteController',
    component: () => import('../admin/WheelRemoteController.vue'),
    meta: {
      requiresAuth: true,
      title: 'តេលេបញ្ជាកងវិល | OnlineXam'
    }
  },

  // ── Admin & Super Admin ───────────────────────────────────────────
  {
    path: '/admin',
    component: () => import('../admin/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '',             redirect: 'dashboard' },
      { path: 'dashboard',    name: 'AdminDashboard',      component: () => import('../admin/AdminDashboard.vue') },
      { path: 'students',     name: 'ManageStudents',       component: () => import('../admin/ManageStudents.vue') },
      { path: 'tests',        name: 'ManageTests',          component: () => import('../admin/ManageTests.vue') },
      { path: 'skills-groups', name: 'ManageSkillsGroups', component: () => import('../admin/ManageSkillsGroups.vue') },
      { path: 'live-monitor', name: 'LiveExamMonitor',      component: () => import('../admin/LiveExamMonitor.vue') },
      { path: 'results',      name: 'AdminResults',         component: () => import('../admin/AdminResults.vue') },
      { path: 'results/:submissionId', name: 'AdminResultDetail', component: () => import('../admin/AdminResultDetail.vue') },

      // Super Admin Dedicated Routes
      {
        path: 'admins',
        name: 'ManageAdmins',
        meta: { requiresSuperAdmin: true },
        component: () => import('../admin/ManageAdmins.vue')
      },
      {
        path: 'roles-permissions',
        name: 'RolesPermissions',
        meta: { requiresSuperAdmin: true },
        component: () => import('../admin/RolesPermissions.vue')
      },
      {
        path: 'audit-logs',
        name: 'AuditLogs',
        meta: { requiresSuperAdmin: true },
        component: () => import('../admin/AuditLogs.vue')
      },
      {
        path: 'system-settings',
        name: 'SystemSettings',
        meta: { requiresSuperAdmin: true },
        component: () => import('../admin/SystemSettings.vue')
      },
      {
        path: 'access-restricted',
        name: 'AccessRestricted',
        component: () => import('../admin/AccessRestricted.vue')
      }
    ]
  },

  // ── Student ───────────────────────────────────────────────────────
  {
    path: '/student',
    name: 'Student',
    meta: { requiresAuth: true },
    component: () => import('../student/StudentPortal.vue')
  },
  {
    path: '/student/exam/:testId',
    name: 'Exam',
    meta: { requiresAuth: true },
    component: () => import('../student/ExamRoom.vue')
  },
  {
    path: '/student/results/:submissionId',
    name: 'ExamResults',
    meta: { requiresAuth: true },
    component: () => import('../student/ExamResults.vue')
  },

  // ── Catch-all ─────────────────────────────────────────────────────
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Cached user in memory (verified by server)
let cachedUser = null

// ══ Global Navigation Guard (Server-verified authentication) ═══════
router.beforeEach(async (to, _from, next) => {
  if (!to.matched.some(r => r.meta?.requiresAuth)) {
    return next()
  }

  // If cachedUser is null in memory, we MUST verify with the server (/api/profile)
  // rather than blindly trusting localStorage, preventing console manipulation bypass
  if (!cachedUser) {
    try {
      const res = await axios.get('/api/profile')
      cachedUser = res.data.user
      if (!cachedUser) {
        throw new Error('Unauthenticated')
      }
      localStorage.setItem('isAuthenticated', 'true')
      localStorage.setItem('userRole', cachedUser.role || 'Student')
    } catch {
      cachedUser = null
      localStorage.removeItem('isAuthenticated')
      localStorage.removeItem('userRole')
      return next({ name: 'Login', replace: true })
    }
  }

  const role = cachedUser.role || 'Student'
  const isAdminRole = ['Admin', 'Super Admin', 'SuperAdmin'].includes(role)
  const isSuperAdminRole = ['Super Admin', 'SuperAdmin'].includes(role)

  // If an Admin/SuperAdmin visits /student, redirect to /admin/dashboard
  if (to.path === '/student' && isAdminRole) {
    return next({ name: 'AdminDashboard', replace: true })
  }

  // If a Student visits /admin, redirect to /student
  if (to.path.startsWith('/admin') && !isAdminRole) {
    return next({ name: 'Student', replace: true })
  }

  // Check SuperAdmin routes
  if (to.matched.some(r => r.meta?.requiresSuperAdmin)) {
    if (isSuperAdminRole) {
      return next()
    } else {
      return next({ name: 'AccessRestricted' })
    }
  }

  next()
})

// ══ Dynamic Page Title & Meta Synchronizer ══════════════════════════
router.afterEach((to) => {
  if (to.meta?.title) {
    document.title = to.meta.title
  } else {
    document.title = 'OnlineXam - Online Examination System'
  }

  if (to.meta?.description) {
    const metaDesc = document.querySelector('meta[name="description"]')
    if (metaDesc) {
      metaDesc.setAttribute('content', to.meta.description)
    }
  }
})

// ══ Global Axios Interceptor ═════════════════════════════════════════
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401 || error.response?.status === 403) {
      if (error.response?.status === 401) {
        localStorage.removeItem('isAuthenticated')
        localStorage.removeItem('userRole')
        cachedUser = null
        const currentRouteName = router.currentRoute.value?.name
        if (currentRouteName && !['Login', 'Register', 'ForgotPassword', 'ResetPassword'].includes(currentRouteName)) {
          router.replace({ name: 'Login' })
        }
      }
    }
    return Promise.reject(error)
  }
)

export default router
