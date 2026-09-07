<template>
  <div class="min-h-screen bg-slate-50 flex flex-col lg:flex-row font-sans">

    <!-- ── Mobile Drawer (Slides in from LEFT side & No Scroll) ─────── -->
    <Drawer
      v-model="isMobileMenuOpen"
      side="left"
      max-width="sm"
    >
      <template #header>
        <div class="px-1 py-0.5">
          <Logo :variant="isSuperAdmin ? 'superadmin' : 'admin'" size="sm" />
        </div>
      </template>

      <div class="flex flex-col h-full justify-between">
        <!-- Navigation Groups (Compact & Balanced to eliminate scrolling) -->
        <div class="space-y-3">
          <div v-for="group in navGroups" :key="group.label" class="space-y-0.5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-2.5 pb-0.5">
              {{ group.label }}
            </p>
            <div class="space-y-0.5">
              <button
                v-for="item in group.items"
                :key="item.route"
                type="button"
                :class="[
                  'w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer text-left',
                  isActive(item.route)
                    ? 'bg-blue-50 text-blue-700 font-bold'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                ]"
                @click="navigate(item.route); isMobileMenuOpen = false"
              >
                <span
                  class="material-symbols-outlined text-lg shrink-0"
                  :style="isActive(item.route) ? 'font-variation-settings: \'FILL\' 1;' : ''"
                >
                  {{ item.icon }}
                </span>
                <span class="truncate">{{ item.label }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- User Profile in Drawer (Compact Bottom Bar) -->
        <div class="pt-3 mt-3 border-t border-slate-100 shrink-0 space-y-2">
          <div class="flex items-center justify-between gap-2 p-1.5 rounded-xl bg-slate-50 border border-slate-200/70">
            <div class="flex items-center gap-2 min-w-0">
              <div class="h-8 w-8 shrink-0 rounded-lg bg-blue-100 flex items-center justify-center overflow-hidden border border-slate-200">
                <img v-if="currentUser?.profileImage" :src="currentUser.profileImage" class="h-full w-full object-cover" />
                <span v-else class="material-symbols-outlined text-blue-600 text-base">person</span>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-800 truncate leading-tight">{{ currentUser?.name || 'User' }}</p>
                <span
                  :class="[
                    'text-[9px] font-extrabold uppercase px-1 py-0.2 rounded leading-none inline-block mt-0.5',
                    isSuperAdmin ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'
                  ]"
                >
                  {{ isSuperAdmin ? 'Super Admin' : 'Admin' }}
                </span>
              </div>
            </div>

            <!-- Sign Out Icon Button -->
            <IconButton
              icon="logout"
              variant="danger"
              size="sm"
              title="Sign Out"
              @click="showLogoutModal = true; isMobileMenuOpen = false"
            />
          </div>
        </div>
      </div>
    </Drawer>

    <!-- ── Desktop Sticky Sidebar (210px - Compact Slim Design) ─── -->
    <aside class="hidden lg:flex w-[210px] xl:w-[220px] shrink-0 bg-white border-r border-slate-200/90 flex-col justify-between sticky top-0 h-screen z-30 shadow-soft-xs">
      <!-- Brand & Navigation -->
      <div class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Brand Header -->
        <div class="px-1.5 py-1.5 mb-0.5">
          <Logo :variant="isSuperAdmin ? 'superadmin' : 'admin'" size="sm" />
        </div>

        <!-- Navigation Groups -->
        <div class="space-y-3">
          <div v-for="group in navGroups" :key="group.label" class="space-y-0.5">
            <p class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400 px-2 pb-0.5">
              {{ group.label }}
            </p>
            <div class="space-y-0.5">
              <button
                v-for="item in group.items"
                :key="item.route"
                type="button"
                :class="[
                  'w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold transition-all duration-150 cursor-pointer text-left select-none relative',
                  isActive(item.route)
                    ? 'bg-blue-50 text-blue-700 font-bold shadow-soft-xs'
                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
                ]"
                @click="navigate(item.route)"
              >
                <!-- Active Indicator Bar -->
                <span
                  v-if="isActive(item.route)"
                  class="absolute left-0 top-1 bottom-1 w-1 rounded-r-md bg-blue-600"
                ></span>

                <span
                  class="material-symbols-outlined text-lg shrink-0"
                  :style="isActive(item.route) ? 'font-variation-settings: \'FILL\' 1;' : ''"
                >
                  {{ item.icon }}
                </span>
                <span class="truncate">{{ item.label }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- User Profile & Logout Area -->
      <div class="p-3 border-t border-slate-100 bg-slate-50/60">
        <div class="flex items-center justify-between gap-1.5 p-1.5 rounded-xl bg-white border border-slate-200/80 shadow-soft-xs">
          <!-- Avatar + Photo Upload -->
          <div class="relative group h-8 w-8 shrink-0 cursor-pointer rounded-lg bg-blue-50 border border-slate-200 overflow-hidden flex items-center justify-center">
            <img v-if="currentUser?.profileImage" :src="currentUser.profileImage" class="h-full w-full object-cover" />
            <span v-else class="material-symbols-outlined text-blue-600 text-base">person</span>

            <div class="absolute inset-0 bg-slate-900/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none text-white">
              <span class="material-symbols-outlined text-[10px]">photo_camera</span>
            </div>
            <input
              type="file"
              accept="image/*"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              title="Change Profile Photo"
              @change="onFileChange"
            />
          </div>

          <!-- User Info & Role Badge -->
          <div class="min-w-0 flex-1 px-1">
            <p class="text-[11px] font-bold text-slate-900 truncate leading-tight">{{ currentUser?.name || 'Admin' }}</p>
            <span
              :class="[
                'text-[9px] font-bold uppercase tracking-wider',
                isSuperAdmin ? 'text-purple-700' : 'text-slate-500'
              ]"
            >
              {{ isSuperAdmin ? 'Super Admin' : 'Admin' }}
            </span>
          </div>

          <!-- Logout Button -->
          <IconButton
            icon="logout"
            variant="ghost"
            size="xs"
            title="Sign Out"
            @click="showLogoutModal = true"
          />
        </div>
      </div>
    </aside>

    <!-- ── Main Workspace Area ─────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
      <!-- Topbar Header -->
      <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-3 sm:px-5 lg:px-6 py-2.5 flex items-center justify-between shadow-soft-xs">
        <!-- Left: Mobile Menu Trigger + Breadcrumb -->
        <div class="flex items-center gap-2.5 min-w-0">
          <button
            type="button"
            class="lg:hidden p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900"
            @click="isMobileMenuOpen = true"
          >
            <span class="material-symbols-outlined text-xl">menu</span>
          </button>
          <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 leading-tight">
              {{ isSuperAdmin ? 'Super Admin Control Center' : 'Admin Panel' }}
            </p>
            <h1 class="text-sm sm:text-base font-bold text-slate-900 truncate leading-tight mt-0.5">
              {{ currentTitle }}
            </h1>
          </div>
        </div>

        <!-- Right: Time + Language Switcher -->
        <div class="flex items-center gap-3 shrink-0">
          <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-xs font-semibold text-slate-600 border border-slate-200/60">
            <span class="material-symbols-outlined text-sm text-slate-400">schedule</span>
            <span>{{ currentTime }}</span>
          </div>

          <LangSwitcher variant="light" />
        </div>
      </header>

      <!-- Main Page Content -->
      <main class="flex-1 px-4 sm:px-6 lg:px-8 py-5 w-full">
        <router-view />
      </main>
    </div>

    <!-- ── Confirm Sign Out Dialog ─────────────────────────────────── -->
    <ConfirmDialog
      v-model="showLogoutModal"
      :title="t.signOutTitle"
      :message="t.signOutDesc"
      :confirm-text="t.signOutConfirm"
      :cancel-text="t.cancel"
      confirm-variant="danger"
      icon="logout"
      :loading="loggingOut"
      @confirm="confirmLogout"
    />
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { logout as apiLogout } from '../utils/auth'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'
import LangSwitcher from '../components/LangSwitcher.vue'
import Drawer from '../components/ui/Drawer.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Logo from '../components/ui/Logo.vue'

const router = useRouter()
const route = useRoute()
const { lang } = useLang()
const { error: toastError, success: toastSuccess } = useToast()
const { isSuperAdmin, fetchUser, currentUser, can } = usePermissions()

const isMobileMenuOpen = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      adminMenu: 'ម៉ឺនុយគ្រប់គ្រង',
      adminWorkspace: 'ផ្ទាំងគ្រប់គ្រងរដ្ឋបាល',
      superAdminWorkspace: 'ផ្ទាំងគ្រប់គ្រង Super Admin',
      overview: 'ទិដ្ឋភាពទូទៅ',
      management: 'ការគ្រប់គ្រង',
      userManagement: 'គ្រប់គ្រងសិស្ស',
      academicManagement: 'គ្រប់គ្រងការសិក្សា & ប្រឡង',
      insights: 'ទិន្នន័យ និង លទ្ធផល',
      system: 'ប្រព័ន្ធ & ការកំណត់',
      dashboard: 'ផ្ទាំងគ្រប់គ្រង',
      users: 'សិស្ស',
      allUsers: 'បញ្ជីសិស្ស',
      exams: 'ការប្រឡង និងការធ្វើតេស្ត',
      skillsGroups: 'ជំនាញ និង ក្រុម',
      liveMonitor: 'តាមដានការប្រឡងផ្ទាល់',
      results: 'លទ្ធផល & ស្ថិតិ',
      administrators: 'អ្នកគ្រប់គ្រង',
      rolesPermissions: 'តួនាទី & សិទ្ធិ',
      auditLogs: 'កំណត់ហេតុ Audit Logs',
      systemSettings: 'ការកំណត់ប្រព័ន្ធ',
      signOut: 'ចាកចេញពីប្រព័ន្ធ',
      signOutTitle: 'តើអ្នកពិតជាចង់ចាកចេញមែនទេ?',
      signOutDesc: 'អ្នកនឹងត្រូវចូលប្រព័ន្ធម្តងទៀតដើម្បីចូលប្រើផ្ទាំងគ្រប់គ្រង។',
      signOutConfirm: 'ចាកចេញ',
      cancel: 'បោះបង់'
    }
  }
  return {
    adminMenu: 'Admin Menu',
    adminWorkspace: 'Admin Workspace',
    superAdminWorkspace: 'Super Admin Control Center',
    overview: 'Overview',
    management: 'Management',
    userManagement: 'Student Management',
    academicManagement: 'Academic Management',
    insights: 'Insights',
    system: 'System & Security',
    dashboard: 'Dashboard',
    users: 'Students',
    allUsers: 'Students Directory',
    exams: 'Exams & Tests',
    skillsGroups: 'Skills & Groups',
    liveMonitor: 'Live Exam Monitor',
    results: 'Results & Analytics',
    administrators: 'Administrators',
    rolesPermissions: 'Roles & Permissions',
    auditLogs: 'Audit Logs',
    systemSettings: 'System Settings',
    signOut: 'Sign Out',
    signOutTitle: 'Are you sure you want to sign out?',
    signOutDesc: 'You will need to sign in again to access the admin portal.',
    signOutConfirm: 'Sign Out',
    cancel: 'Cancel'
  }
})

const navGroups = computed(() => {
  if (isSuperAdmin.value) {
    return [
      {
        label: t.value.overview,
        items: [
          { label: t.value.dashboard, icon: 'dashboard', route: '/admin/dashboard' }
        ]
      },
      {
        label: t.value.userManagement,
        items: [
          { label: t.value.allUsers, icon: 'group', route: '/admin/students' }
        ]
      },
      {
        label: t.value.academicManagement,
        items: [
          { label: t.value.exams, icon: 'quiz', route: '/admin/tests' },
          { label: t.value.skillsGroups, icon: 'category', route: '/admin/skills-groups' },
          { label: t.value.liveMonitor, icon: 'podcasts', route: '/admin/live-monitor' }
        ]
      },
      {
        label: t.value.insights,
        items: [
          { label: t.value.results, icon: 'bar_chart', route: '/admin/results' }
        ]
      },
      {
        label: t.value.system,
        items: [
          { label: t.value.administrators, icon: 'admin_panel_settings', route: '/admin/admins' },
          { label: t.value.rolesPermissions, icon: 'security', route: '/admin/roles-permissions' },
          { label: t.value.auditLogs, icon: 'history', route: '/admin/audit-logs' },
          { label: t.value.systemSettings, icon: 'tune', route: '/admin/system-settings' }
        ]
      }
    ]
  }

  // Standard Admin Operational Nav
  const managementItems = []
  if (can('Students', 'view')) managementItems.push({ label: t.value.users, icon: 'group', route: '/admin/students' })
  if (can('Exams', 'view')) {
    managementItems.push({ label: t.value.exams, icon: 'quiz', route: '/admin/tests' })
    managementItems.push({ label: t.value.liveMonitor, icon: 'podcasts', route: '/admin/live-monitor' })
  }
  if (can('Skills & Groups', 'view')) managementItems.push({ label: t.value.skillsGroups, icon: 'category', route: '/admin/skills-groups' })

  const insightsItems = []
  if (can('Results', 'view')) insightsItems.push({ label: t.value.results, icon: 'bar_chart', route: '/admin/results' })

  const groups = [
    {
      label: t.value.overview,
      items: [
        { label: t.value.dashboard, icon: 'dashboard', route: '/admin/dashboard' }
      ]
    }
  ]

  if (managementItems.length) {
    groups.push({
      label: t.value.management,
      items: managementItems
    })
  }

  if (insightsItems.length) {
    groups.push({
      label: t.value.insights,
      items: insightsItems
    })
  }

  return groups
})

const isActive = (navRoute) => route.path.startsWith(navRoute)

const currentTitle = computed(() => {
  for (const group of navGroups.value) {
    const match = group.items.find(item => route.path.startsWith(item.route))
    if (match) return match.label
  }
  return t.value.dashboard
})

const navigate = (path) => {
  router.push(path)
}

const onFileChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  const formData = new FormData()
  formData.append('image', file)
  try {
    const res = await axios.post('/api/profile/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (currentUser.value) {
      currentUser.value.profileImage = res.data.profileImage
    }
    toastSuccess('Profile image updated successfully!')
  } catch (err) {
    toastError(err.response?.data?.message || 'Upload failed')
  }
}

// Live clock
const currentTime = ref('')
let clockTimer = null
const updateTime = () => {
  currentTime.value = new Date().toLocaleString('en-US', {
    weekday: 'short', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const confirmLogout = async () => {
  loggingOut.value = true
  await apiLogout()
}

onMounted(() => {
  fetchUser()
  updateTime()
  clockTimer = setInterval(updateTime, 1000)
})

onUnmounted(() => {
  if (clockTimer) clearInterval(clockTimer)
})
</script>
