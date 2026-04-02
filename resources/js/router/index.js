import { createRouter, createWebHistory } from 'vue-router'
import Login from '../auth/Login.vue'
import Register from '../auth/Register.vue'
import ForgotPassword from '../auth/ForgotPassword.vue'
import ResetPassword from '../auth/ResetPassword.vue'

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

  // ── Admin ─────────────────────────────────────────────────────────
  {
    path: '/admin',
    component: () => import('../admin/AdminLayout.vue'),
    children: [
      { path: '',             redirect: 'dashboard' },
      { path: 'dashboard',   name: 'AdminDashboard',      component: () => import('../admin/AdminDashboard.vue') },
      { path: 'students',    name: 'ManageStudents',       component: () => import('../admin/ManageStudents.vue') },
      { path: 'tests',       name: 'ManageTests',          component: () => import('../admin/ManageTests.vue') },
      { path: 'skills-batches', name: 'ManageSkillsBatches', component: () => import('../admin/ManageSkillsBatches.vue') },
      { path: 'results',     name: 'AdminResults',         component: () => import('../admin/AdminResults.vue') },
      { path: 'results/:submissionId', name: 'AdminResultDetail', component: () => import('../admin/AdminResultDetail.vue') },
    ]
  },

  // ── Student ───────────────────────────────────────────────────────
  {
    path: '/student',
    name: 'Student',
    component: () => import('../student/StudentPortal.vue')
  },
  {
    path: '/student/exam/:testId',
    name: 'Exam',
    component: () => import('../student/ExamRoom.vue')
  },
  {
    path: '/student/results/:submissionId',
    name: 'ExamResults',
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

export default router
