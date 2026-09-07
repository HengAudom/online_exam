import { createRouter, createWebHistory } from 'vue-router'
import Login from '../auth/Login.vue'
import Register from '../auth/Register.vue'
import ForgotPassword from '../auth/ForgotPassword.vue'
import ResetPassword from '../auth/ResetPassword.vue'
import axios from 'axios'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/register',
    name: 'Register',
    component: Register
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPassword
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: ResetPassword
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

// Cached user in memory
let cachedUser = null

// ══ Global Navigation Guard (Instant 0ms synchronous transition) ════
router.beforeEach(async (to, _from, next) => {
  if (!to.matched.some(r => r.meta?.requiresAuth)) {
    return next()
  }

  if (!localStorage.getItem('isAuthenticated')) {
    return next({ name: 'Login', replace: true })
  }

  let role = cachedUser?.role || localStorage.getItem('userRole') || ''

  // Only perform network request if role is completely unknown
  if (!role && !cachedUser) {
    try {
      const res = await axios.get('/api/profile')
      cachedUser = res.data.user
      role = cachedUser?.role || 'Student'
      localStorage.setItem('userRole', role)
    } catch {
      localStorage.removeItem('isAuthenticated')
      localStorage.removeItem('userRole')
      return next({ name: 'Login', replace: true })
    }
  }

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

// ══ Global Axios Interceptor ═════════════════════════════════════════
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401 || error.response?.status === 403) {
      if (error.response?.status === 401) {
        localStorage.removeItem('isAuthenticated')
        localStorage.removeItem('userRole')
        cachedUser = null
        router.replace({ name: 'Login' })
      }
    }
    return Promise.reject(error)
  }
)

export default router
