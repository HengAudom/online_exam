<template>
  <div class="space-y-6">
    <!-- Role-Aware Page Header & Quick Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <span
            :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border',
              isSuperAdmin
                ? 'bg-purple-50 text-purple-700 border-purple-200'
                : 'bg-blue-50 text-blue-700 border-blue-200'
            ]"
          >
            <span class="material-symbols-outlined text-xs mr-1">
              {{ isSuperAdmin ? 'admin_panel_settings' : 'shield' }}
            </span>
            {{ isSuperAdmin ? 'Super Administrator' : 'Administrator' }}
          </span>

          <span
            v-if="settings.academicYear"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"
          >
            <span class="material-symbols-outlined text-xs mr-1 text-slate-500">calendar_month</span>
            {{ settings.academicYear }}
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
          {{ isSuperAdmin ? t.superDashboardTitle : t.adminDashboardTitle }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ isSuperAdmin ? t.superDashboardSubtitle : t.adminDashboardSubtitle }}
        </p>
      </div>

      <div class="flex items-center gap-2.5 flex-wrap">
        <Button
          variant="outline"
          icon="group_add"
          size="sm"
          @click="router.push('/admin/students')"
        >
          {{ t.addStudent }}
        </Button>
        <Button
          variant="primary"
          icon="add_circle"
          size="sm"
          @click="router.push('/admin/tests')"
        >
          {{ t.createExam }}
        </Button>
        <Button
          variant="secondary"
          icon="bar_chart"
          size="sm"
          @click="router.push('/admin/results')"
        >
          {{ t.viewResults }}
        </Button>
      </div>
    </div>

    <!-- Skeletons when initial loading -->
    <div v-if="initialLoading" class="space-y-6">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="n in 4" :key="n" height="120px" customClass="rounded-2xl" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Skeleton height="260px" customClass="rounded-2xl lg:col-span-1" />
        <Skeleton height="260px" customClass="rounded-2xl lg:col-span-2" />
      </div>
    </div>

    <!-- Loaded Dashboard Content -->
    <div v-else class="space-y-6">

      <!-- ── KPI Stat Cards (Super Admin sees 6, Admin sees 4) ──────── -->
      <div v-if="isSuperAdmin" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <StatCard
          :label="t.totalUsers"
          :value="dashboard.totalUsers || dashboard.activeStudents"
          icon="group"
          color="purple"
          :accent="true"
        />
        <StatCard
          :label="t.totalAdmins"
          :value="dashboard.totalAdmins || 1"
          icon="admin_panel_settings"
          color="indigo"
        />
        <StatCard
          :label="t.activeStudents"
          :value="dashboard.activeStudents"
          icon="school"
          color="blue"
        />
        <StatCard
          :label="t.publishedTests"
          :value="dashboard.publishedTests"
          icon="quiz"
          color="sky"
        />
        <StatCard
          :label="t.completedExams"
          :value="dashboard.completedExams"
          icon="task_alt"
          color="emerald"
        />
        <StatCard
          :label="t.avgScore"
          :value="dashboard.avgScore"
          suffix="%"
          icon="analytics"
          color="amber"
        />
      </div>

      <!-- Standard Admin KPI Row -->
      <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          :label="t.activeStudents"
          :value="dashboard.activeStudents"
          icon="group"
          color="blue"
          :accent="true"
          :hint="t.enrolledStudents"
        />
        <StatCard
          :label="t.publishedTests"
          :value="dashboard.publishedTests"
          icon="quiz"
          color="indigo"
          :hint="t.liveExams"
        />
        <StatCard
          :label="t.completedExams"
          :value="dashboard.completedExams"
          icon="task_alt"
          color="emerald"
          :hint="t.totalSubmissions"
        />
        <StatCard
          :label="t.avgScore"
          :value="dashboard.avgScore"
          suffix="%"
          icon="bar_chart"
          color="amber"
          :hint="t.overallPerformance"
        />
      </div>

      <!-- ── Super Admin System Health Row ─────────────────────────── -->
      <div v-if="isSuperAdmin && dashboard.systemHealth" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">database</span>
          </div>
          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Database Engine</p>
            <p class="text-xs font-extrabold text-emerald-700 mt-0.5 flex items-center gap-1">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              {{ dashboard.systemHealth.database }}
            </p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">api</span>
          </div>
          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">REST API</p>
            <p class="text-xs font-extrabold text-emerald-700 mt-0.5 flex items-center gap-1">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              {{ dashboard.systemHealth.api }}
            </p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">lock</span>
          </div>
          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Authentication</p>
            <p class="text-xs font-extrabold text-emerald-700 mt-0.5 flex items-center gap-1">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              {{ dashboard.systemHealth.auth }}
            </p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 shrink-0">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">verified</span>
          </div>
          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Platform Status</p>
            <p class="text-xs font-extrabold text-purple-700 mt-0.5">
              Operational
            </p>
          </div>
        </div>
      </div>

      <!-- ── Main Grid: Storage & Activity Feed ────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Database Storage Card (Super Admin ONLY) -->
        <Card
          v-if="isSuperAdmin && dashboard.databaseStorage"
          :title="t.databaseUsage"
          class="lg:col-span-1"
        >
          <template #actions>
            <span
              :class="[
                'px-2.5 py-1 rounded-full text-xs font-extrabold',
                dashboard.databaseStorage.percentage > 90
                  ? 'bg-red-50 text-red-700 border border-red-200'
                  : 'bg-blue-50 text-blue-700 border border-blue-200'
              ]"
            >
              {{ dashboard.databaseStorage.percentage }}%
            </span>
          </template>

          <div class="space-y-4 pt-1">
            <!-- Progress Bar -->
            <ProgressBar
              :value="dashboard.databaseStorage.percentage"
              :max="100"
              :variant="dashboard.databaseStorage.percentage > 90 ? 'danger' : 'primary'"
              size="lg"
            />

            <!-- Storage Metrics Breakdown -->
            <div class="grid grid-cols-2 gap-3 pt-2">
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t.used }}</p>
                <p class="text-base font-extrabold text-slate-900 mt-0.5">{{ dashboard.databaseStorage.used }} MB</p>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t.remaining }}</p>
                <p class="text-base font-extrabold text-slate-900 mt-0.5">{{ dashboard.databaseStorage.remaining }} MB</p>
              </div>
            </div>

            <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
              <div class="flex items-center justify-between text-slate-400">
                <span>{{ t.allocatedTotal }}:</span>
                <strong class="text-slate-700 font-bold">{{ dashboard.databaseStorage.total }} MB</strong>
              </div>
            </div>
          </div>
        </Card>

        <!-- Activity Feed (Grouped By Week) -->
        <Card
          :title="isSuperAdmin ? t.platformActivity : t.activityByWeek"
          :subtitle="t.activitySub"
          :class="isSuperAdmin && dashboard.databaseStorage ? 'lg:col-span-2' : 'lg:col-span-3'"
          padding="none"
        >
          <template #actions>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 border border-slate-200/60">
              {{ allActivities.length }} {{ t.events }}
            </span>
          </template>

          <!-- Empty State -->
          <EmptyState
            v-if="!allActivities.length"
            icon="event_note"
            :title="t.noActivity"
            :description="t.noActivityDesc"
          />

          <!-- Activity List -->
          <div v-else class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto">
            <div v-for="(group, gi) in groupedByWeek" :key="gi">
              <!-- Week Header -->
              <div class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-xs px-5 py-2.5 border-y border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-sm text-blue-600">date_range</span>
                  <span class="text-xs font-bold uppercase tracking-wider text-blue-700">{{ group.weekLabel }}</span>
                </div>
                <span class="rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-extrabold border border-blue-200/50">
                  {{ group.items.length }} {{ t.events }}
                </span>
              </div>

              <!-- Events within Week -->
              <div class="divide-y divide-slate-50">
                <div
                  v-for="item in group.items"
                  :key="item.id || item.title + '_' + item.timestamp"
                  class="flex items-center gap-3.5 px-5 py-3.5 hover:bg-slate-50/80 transition-colors"
                >
                  <!-- Event Icon -->
                  <div
                    :class="[
                      'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl',
                      item.colorClass
                    ]"
                  >
                    <span class="material-symbols-outlined text-lg select-none" style="font-variation-settings: 'FILL' 1;">
                      {{ item.icon }}
                    </span>
                  </div>

                  <!-- Text -->
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate leading-snug">{{ item.title }}</p>
                    <p v-if="item.description" class="text-xs text-slate-500 truncate mt-0.5 leading-snug">{{ item.description }}</p>
                  </div>

                  <!-- Time -->
                  <span v-if="item.timeLabel" class="text-[11px] font-semibold text-slate-400 shrink-0 whitespace-nowrap">
                    {{ item.timeLabel }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import StatCard from '../components/ui/StatCard.vue'
import Button from '../components/ui/Button.vue'
import ProgressBar from '../components/ui/ProgressBar.vue'
import Skeleton from '../components/ui/Skeleton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import { useLang } from '../utils/useLang'
import { usePermissions } from '../composables/usePermissions'
import { useSettings } from '../composables/useSettings'

const router = useRouter()
const { lang } = useLang()
const { isSuperAdmin, fetchUser } = usePermissions()
const { settings, fetchSettings } = useSettings()

const initialLoading = ref(true)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      superDashboardTitle: 'មជ្ឈមណ្ឌលគ្រប់គ្រងប្រព័ន្ធ Super Admin',
      superDashboardSubtitle: 'ទិដ្ឋភាពទូទៅនៃប្រព័ន្ធ ស្ថានភាពសុខភាព Server និងសកម្មភាពប្រតិបត្តិការ',
      adminDashboardTitle: 'ផ្ទាំងគ្រប់គ្រងរដ្ឋបាល',
      adminDashboardSubtitle: 'ស្ថិតិទូទៅ ការប្រឡងសកម្ម និងសកម្មភាពថ្មីៗក្នុងប្រព័ន្ធ',
      totalUsers: 'អ្នកប្រើប្រាស់សរុប',
      totalAdmins: 'អ្នកគ្រប់គ្រង Admins',
      addStudent: 'បន្ថែមសិស្ស',
      createExam: 'បង្កើតការប្រឡង',
      viewResults: 'មើលលទ្ធផល',
      activeStudents: 'សិស្សកំពុងសិក្សា',
      enrolledStudents: 'សិស្សបានចុះឈ្មោះ',
      publishedTests: 'ការប្រឡងបានចេញ',
      liveExams: 'ការប្រឡងសកម្ម',
      completedExams: 'ការប្រឡងបានបញ្ចប់',
      totalSubmissions: 'ចំនួនបានប្រឡងចប់',
      avgScore: 'ពិន្ទុមធ្យម',
      overallPerformance: 'លទ្ធផលរួម',
      databaseUsage: 'ទំហំប្រើប្រាស់ Database',
      allocatedTotal: 'ទំហំសរុប',
      used: 'បានប្រើប្រាស់',
      remaining: 'នៅសល់',
      platformActivity: 'សកម្មភាពប្រព័ន្ធទូទាំងស្ថាប័ន',
      activityByWeek: 'សកម្មភាពតាមសប្ដាហ៍',
      activitySub: 'សកម្មភាពថ្មីៗក្នុងប្រព័ន្ធ បែងចែកតាមសប្ដាហ៍',
      noActivity: 'មិនទាន់មានសកម្មភាពថ្មីៗទេ',
      noActivityDesc: 'នៅពេលមានការប្រឡង ឬការចុះឈ្មោះថ្មី សកម្មភាពនឹងបង្ហាញនៅទីនេះ។',
      events: 'សកម្មភាព',
      thisWeek: 'សប្ដាហ៍នេះ',
      lastWeek: 'សប្ដាហ៍មុន',
    }
  }
  return {
    superDashboardTitle: 'Super Admin Control Center',
    superDashboardSubtitle: 'Platform overview, system health status, and operational logs',
    adminDashboardTitle: 'Admin Dashboard',
    adminDashboardSubtitle: 'Overview of system metrics, active examinations, and recent logs',
    totalUsers: 'Total Users',
    totalAdmins: 'Administrators',
    addStudent: 'Add Student',
    createExam: 'Create Exam',
    viewResults: 'View Results',
    activeStudents: 'Active Students',
    enrolledStudents: 'Enrolled in courses',
    publishedTests: 'Published Exams',
    liveExams: 'Available for taking',
    completedExams: 'Completed Exams',
    totalSubmissions: 'Total submissions',
    avgScore: 'Average Score',
    overallPerformance: 'System-wide accuracy',
    databaseUsage: 'Database Storage',
    allocatedTotal: 'Total allocated',
    used: 'Used space',
    remaining: 'Remaining space',
    platformActivity: 'Platform-wide Activity',
    activityByWeek: 'Activity by Week',
    activitySub: 'Recent system events grouped by week',
    noActivity: 'No recent activity yet',
    noActivityDesc: 'When tests are submitted or students are registered, events will appear here.',
    events: 'events',
    thisWeek: 'This Week',
    lastWeek: 'Last Week',
  }
})

const dashboard = reactive({
  totalUsers: 0,
  totalAdmins: 0,
  activeStudents: 0,
  publishedTests: 0,
  completedExams: 0,
  avgScore: 0,
  systemHealth: null,
  databaseStorage: null,
  latestActivity: []
})

const allActivities = computed(() => {
  return (dashboard.latestActivity || []).map(a => {
    let icon = 'history'
    let colorClass = 'bg-slate-100 text-slate-600'

    switch (a.type) {
      case 'exam_completion':
        icon = 'task_alt'
        colorClass = 'bg-emerald-50 text-emerald-600 border border-emerald-100'
        break
      case 'new_test':
        icon = 'quiz'
        colorClass = 'bg-indigo-50 text-indigo-600 border border-indigo-100'
        break
      case 'new_student':
        icon = 'person_add'
        colorClass = 'bg-blue-50 text-blue-600 border border-blue-100'
        break
      case 'new_admin':
        icon = 'admin_panel_settings'
        colorClass = 'bg-purple-50 text-purple-600 border border-purple-100'
        break
      case 'new_group':
        icon = 'groups'
        colorClass = 'bg-amber-50 text-amber-600 border border-amber-100'
        break
      case 'new_skill':
        icon = 'category'
        colorClass = 'bg-sky-50 text-sky-600 border border-sky-100'
        break
    }

    const timeLabel = a.timestamp
      ? new Date(a.timestamp * 1000).toLocaleString('en-US', {
          month: 'short',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit'
        })
      : ''

    return { ...a, icon, colorClass, timeLabel }
  })
})

const getWeekStart = (date) => {
  const d = new Date(date)
  const day = d.getDay()
  const diff = (day === 0 ? -6 : 1) - day
  d.setDate(d.getDate() + diff)
  d.setHours(0, 0, 0, 0)
  return d
}

const weekLabel = (weekStart) => {
  const now = new Date()
  const thisWeek = getWeekStart(now)
  const lastWeek = new Date(thisWeek)
  lastWeek.setDate(lastWeek.getDate() - 7)

  if (weekStart.getTime() === thisWeek.getTime()) return t.value.thisWeek
  if (weekStart.getTime() === lastWeek.getTime()) return t.value.lastWeek

  const weekEnd = new Date(weekStart)
  weekEnd.setDate(weekEnd.getDate() + 6)
  const fmt = (d) => d.toLocaleString('en-US', { month: 'short', day: 'numeric' })
  return `${fmt(weekStart)} – ${fmt(weekEnd)}, ${weekEnd.getFullYear()}`
}

const groupedByWeek = computed(() => {
  const map = new Map()
  for (const item of allActivities.value) {
    if (!item.timestamp) continue
    const ws = getWeekStart(new Date(item.timestamp * 1000))
    const key = ws.getTime()
    if (!map.has(key)) map.set(key, { weekStart: ws, weekLabel: weekLabel(ws), items: [] })
    map.get(key).items.push(item)
  }
  return Array.from(map.values()).sort((a, b) => b.weekStart - a.weekStart)
})

let dashboardPollTimer = null

const loadDashboard = async () => {
  try {
    const res = await axios.get('/api/admin/dashboard')
    Object.assign(dashboard, res.data)
  } catch (e) {
    console.error('Failed to load dashboard', e)
  } finally {
    initialLoading.value = false
  }
}

let lastDashboardFetch = Date.now()
const safeFocusFetch = () => {
  if (Date.now() - lastDashboardFetch > 30000) {
    lastDashboardFetch = Date.now()
    loadDashboard()
  }
}

onMounted(async () => {
  fetchSettings()
  await fetchUser()
  await loadDashboard()
  lastDashboardFetch = Date.now()
  dashboardPollTimer = setInterval(() => {
    if (document.visibilityState === 'visible') {
      lastDashboardFetch = Date.now()
      loadDashboard()
    }
  }, 60000)
  window.addEventListener('focus', safeFocusFetch)
})

onUnmounted(() => {
  if (dashboardPollTimer) clearInterval(dashboardPollTimer)
  window.removeEventListener('focus', safeFocusFetch)
})
</script>
