<template>
  <div class="space-y-6">
    <!-- Header & Real-time Live Status Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
            {{ lang === 'kh' ? 'តាមដានផ្ទាល់' : 'LIVE MONITORING' }}
          </span>
          <span class="text-xs text-slate-400 font-mono">
            {{ lang === 'kh' ? 'ធ្វើបច្ចុប្បន្នភាពស្វ័យប្រវត្តិរៀងរាល់ ៣ វិនាទី' : 'Auto-refreshes every 3s' }}
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
          {{ t.title }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ t.subtitle }}
        </p>
      </div>
    </div>

    <!-- Live KPI Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <StatCard
        :title="t.activeExaminees"
        :value="activeCount"
        icon="person_play"
        color="emerald"
        :description="t.activeExamineesDesc"
      />
      <StatCard
        :title="t.avgProgress"
        :value="`${avgProgress}%`"
        icon="donut_large"
        color="blue"
        :description="t.avgProgressDesc"
      />
      <StatCard
        :title="t.tabSwitches"
        :value="totalInterruptions"
        icon="warning"
        :color="totalInterruptions > 0 ? 'amber' : 'gray'"
        :description="t.tabSwitchesDesc"
      />
      <StatCard
        :title="t.completedToday"
        :value="completedCount"
        icon="task_alt"
        color="purple"
        :description="t.completedTodayDesc"
      />
    </div>

    <!-- Filter & Search Toolbar Card -->
    <Card padding="sm" class="shadow-soft-sm">
      <div class="flex flex-col md:flex-row md:items-center gap-2.5">
        <!-- Search -->
        <div class="w-full md:w-64 lg:w-72">
          <SearchInput
            v-model="searchQuery"
            :placeholder="t.searchPlaceholder"
          />
        </div>

        <!-- Shift & Test Dropdowns (Always side-by-side) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 flex-1">
          <CustomDropdown
            v-model="selectedSessionId"
            :options="[{ sessionId: '', sessionLabel: t.allSessions }, ...sessionDropdownOptions]"
            labelKey="sessionLabel"
            valueKey="sessionId"
            :placeholder="t.allSessions"
            class="w-full text-xs"
          />

          <CustomDropdown
            v-model="selectedTestId"
            :options="[{ TestId: '', TestName: t.allTests }, ...tests]"
            labelKey="TestName"
            valueKey="TestId"
            :placeholder="t.filterTest"
            class="w-full text-xs"
          />
        </div>

        <!-- Reset Button -->
        <IconButton
          v-if="searchQuery || selectedSessionId || selectedTestId"
          icon="restart_alt"
          variant="ghost"
          size="md"
          :title="t.reset"
          class="shrink-0 self-end md:self-center"
          @click="resetFilters"
        />
      </div>
    </Card>

    <!-- Examinees Data Card -->
    <Card padding="none" class="shadow-soft-sm overflow-hidden">

      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/80 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
              <th class="px-4 py-3.5">#</th>
              <th class="px-4 py-3.5">{{ t.candidate }}</th>
              <th class="px-4 py-3.5">{{ t.shiftAndRoom }}</th>
              <th class="px-4 py-3.5">{{ t.examTitle }}</th>
              <th class="px-4 py-3.5 w-44">{{ t.progress }}</th>
              <th class="px-4 py-3.5">{{ t.timeRemaining }}</th>
              <th class="px-4 py-3.5 text-center">{{ t.violations }}</th>
              <th class="px-4 py-3.5 text-center">{{ t.status }}</th>
              <th class="px-4 py-3.5 text-right">{{ t.actions }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="(item, index) in filteredExaminees"
              :key="item.submissionId"
              :class="[
                'hover:bg-slate-50/70 transition-colors',
                item.interruptions > 2 ? 'bg-rose-50/20' : ''
              ]"
            >
              <td class="px-4 py-3.5 text-slate-400 font-bold">
                {{ index + 1 }}
              </td>

              <!-- Candidate -->
              <td class="px-4 py-3.5">
                <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                  <span
                    class="h-2 w-2 rounded-full"
                    :class="item.status === 'In Progress' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"
                  ></span>
                  <span>{{ item.studentName }}</span>
                </div>
                <div class="text-slate-400 font-mono text-[11px] mt-0.5">
                  {{ item.studentCode }}
                </div>
              </td>

              <!-- Shift & Room -->
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span
                  v-if="item.sessionName"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80"
                >
                  <span class="material-symbols-outlined text-sm text-blue-600">schedule</span>
                  <span>{{ item.sessionName }}</span>
                </span>
                <span v-else class="text-slate-400 text-xs">-</span>
              </td>

              <!-- Exam Title -->
              <td class="px-4 py-3.5">
                <div class="font-semibold text-slate-800 text-xs max-w-xs truncate">
                  {{ item.testName }}
                </div>
                <div class="text-[11px] text-slate-400">
                  {{ item.durationMinutes }} {{ lang === 'kh' ? 'នាទី' : 'mins' }} · {{ item.totalMarks }} {{ lang === 'kh' ? 'ពិន្ទុ' : 'pts' }}
                </div>
              </td>

              <!-- Progress Bar -->
              <td class="px-4 py-3.5">
                <div class="space-y-1">
                  <div class="flex items-center justify-between text-[11px] font-bold">
                    <span class="text-slate-600">{{ item.answeredCount }}/{{ item.totalQuestions }} {{ lang === 'kh' ? 'សំណួរ' : 'Qs' }}</span>
                    <span :class="item.progress === 100 ? 'text-emerald-600' : 'text-blue-600'">{{ item.progress }}%</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :class="item.progress === 100 ? 'bg-emerald-500' : 'bg-blue-600'"
                      :style="{ width: `${item.progress}%` }"
                    ></div>
                  </div>
                </div>
              </td>

              <!-- Timer / Remaining -->
              <td class="px-4 py-3.5 whitespace-nowrap">
                <div v-if="item.status === 'Completed'" class="text-xs font-semibold text-purple-700 flex items-center gap-1">
                  <span class="material-symbols-outlined text-sm">task_alt</span>
                  <span>{{ item.elapsedMinutes }}m {{ lang === 'kh' ? 'ប្រើអស់' : 'taken' }}</span>
                </div>
                <div v-else-if="item.status === 'Overdue'" class="text-xs font-bold text-rose-600 flex items-center gap-1">
                  <span class="material-symbols-outlined text-sm">alarm_off</span>
                  <span>{{ lang === 'kh' ? 'ហួសម៉ោង' : 'Overdue' }}</span>
                </div>
                <div v-else class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-lg border border-slate-200 inline-flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs text-blue-600">timer</span>
                  <span>~{{ item.remainingMinutes }} {{ lang === 'kh' ? 'នាទីនៅសល់' : 'm left' }}</span>
                </div>
              </td>

              <!-- Violations (Tab Switches) -->
              <td class="px-4 py-3.5 text-center whitespace-nowrap">
                <span
                  v-if="item.interruptions > 0"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200"
                >
                  <span class="material-symbols-outlined text-xs text-amber-600">visibility_off</span>
                  {{ item.interruptions }}
                </span>
                <span v-else class="text-slate-300 font-mono text-xs">0</span>
              </td>

              <!-- Status -->
              <td class="px-4 py-3.5 text-center">
                <Badge
                  :variant="item.status === 'In Progress' ? 'success' : item.status === 'Completed' ? 'purple' : 'danger'"
                  size="xs"
                >
                  {{ item.status }}
                </Badge>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <Button
                    v-if="item.status === 'In Progress' || item.status === 'Overdue'"
                    variant="danger"
                    size="xs"
                    icon="send"
                    :loading="forceSubmittingId === item.submissionId"
                    @click="confirmForceSubmit(item)"
                  >
                    {{ lang === 'kh' ? 'បង្ខំប្រគល់' : 'Force Submit' }}
                  </Button>
                  <Button
                    v-else
                    variant="outline"
                    size="xs"
                    icon="visibility"
                    @click="router.push(`/admin/results/${item.submissionId}`)"
                  >
                    {{ lang === 'kh' ? 'មើលលទ្ធផល' : 'View' }}
                  </Button>
                </div>
              </td>
            </tr>

            <tr v-if="loading && examinees.length === 0">
              <td colspan="9" class="py-12 text-center text-slate-400">
                <div class="flex items-center justify-center gap-2">
                  <span class="h-5 w-5 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
                  <span>{{ t.loading }}</span>
                </div>
              </td>
            </tr>

            <tr v-else-if="filteredExaminees.length === 0">
              <td colspan="9">
                <EmptyState
                  icon="podcasts"
                  :title="t.noExaminees"
                  :description="t.noExamineesDesc"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card List View -->
      <div class="md:hidden divide-y divide-slate-100">
        <div
          v-for="item in filteredExaminees"
          :key="item.submissionId"
          class="p-4 space-y-3"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-start gap-2.5">
              <div
                class="h-9 w-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 border mt-0.5"
                :class="item.status === 'In Progress' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
              >
                <span class="material-symbols-outlined text-lg">person</span>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm leading-snug flex items-center gap-1.5">
                  <span
                    class="h-2 w-2 rounded-full shrink-0"
                    :class="item.status === 'In Progress' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"
                  ></span>
                  <span>{{ item.studentName }}</span>
                </h4>
                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ item.studentCode }}</p>
              </div>
            </div>

            <Badge
              :variant="item.status === 'In Progress' ? 'success' : item.status === 'Completed' ? 'purple' : 'danger'"
              size="xs"
            >
              {{ item.status }}
            </Badge>
          </div>

          <!-- Exam & Session Info Box -->
          <div class="text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-100 space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 font-semibold">{{ t.examTitle }}:</span>
              <span class="font-bold text-slate-800 truncate max-w-[180px]">{{ item.testName }}</span>
            </div>
            <div v-if="item.sessionName" class="flex items-center justify-between">
              <span class="text-slate-500 font-semibold">{{ t.shiftAndRoom }}:</span>
              <span class="font-bold text-blue-700">{{ item.sessionName }}</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="space-y-1 pt-1">
            <div class="flex items-center justify-between text-xs font-bold">
              <span class="text-slate-600">{{ item.answeredCount }}/{{ item.totalQuestions }} {{ lang === 'kh' ? 'សំណួរឆ្លើយរួច' : 'Questions Answered' }}</span>
              <span :class="item.progress === 100 ? 'text-emerald-600' : 'text-blue-600'">{{ item.progress }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="item.progress === 100 ? 'bg-emerald-500' : 'bg-blue-600'"
                :style="{ width: `${item.progress}%` }"
              ></div>
            </div>
          </div>

          <!-- Footer: Timer, Violations & Force Submit -->
          <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
            <div class="flex items-center gap-2">
              <div v-if="item.interruptions > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                <span class="material-symbols-outlined text-xs">visibility_off</span>
                <span>{{ item.interruptions }} {{ lang === 'kh' ? 'ប្តូរ Tab' : 'Switches' }}</span>
              </div>
              <span class="font-mono text-slate-500">
                {{ item.status === 'In Progress' ? `~${item.remainingMinutes}m left` : `${item.elapsedMinutes}m taken` }}
              </span>
            </div>

            <div>
              <Button
                v-if="item.status === 'In Progress' || item.status === 'Overdue'"
                variant="danger"
                size="xs"
                icon="send"
                :loading="forceSubmittingId === item.submissionId"
                @click="confirmForceSubmit(item)"
              >
                {{ lang === 'kh' ? 'បង្ខំប្រគល់' : 'Force Submit' }}
              </Button>
              <Button
                v-else
                variant="outline"
                size="xs"
                icon="visibility"
                @click="router.push(`/admin/results/${item.submissionId}`)"
              >
                {{ lang === 'kh' ? 'មើល' : 'View' }}
              </Button>
            </div>
          </div>
        </div>

        <div v-if="filteredExaminees.length === 0" class="p-6">
          <EmptyState
            icon="podcasts"
            :title="t.noExaminees"
            :description="t.noExamineesDesc"
          />
        </div>
      </div>
    </Card>

    <!-- Force Submit Confirm Dialog -->
    <ConfirmDialog
      v-model="showForceSubmitDialog"
      :title="t.forceSubmitTitle"
      :message="forceSubmitMessage"
      confirm-text="Force Submit"
      confirm-variant="danger"
      @confirm="executeForceSubmit"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import Badge from '../components/ui/Badge.vue'
import StatCard from '../components/ui/StatCard.vue'
import SearchInput from '../components/ui/SearchInput.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { useRealtimeSync } from '../composables/useRealtimeSync'

const router = useRouter()
const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()

const examinees = ref([])
const activeCount = ref(0)
const sessions = ref([])
const tests = ref([])
const loading = ref(false)

const searchQuery = ref('')
const selectedSessionId = ref('')
const selectedTestId = ref('')

const showForceSubmitDialog = ref(false)
const targetSubmission = ref(null)
const forceSubmittingId = ref(null)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'តាមដានការប្រឡងផ្ទាល់',
      subtitle: 'តាមដានសកម្មភាពបេក្ខជនក្នុងបន្ទប់ប្រឡង វឌ្ឍនភាពចម្លើយ និងការប្តូរផ្ទាំងក្នុងពេលជាក់ស្តែង',
      refresh: 'ផ្ទុកឡើងវិញ',
      activeExaminees: 'បេក្ខជនកំពុងប្រឡង',
      activeExamineesDesc: 'បេក្ខជននៅក្នុងបន្ទប់ប្រឡងផ្ទាល់',
      avgProgress: 'វឌ្ឍនភាពឆ្លើយមធ្យម',
      avgProgressDesc: 'ភាគរយសំណួរដែលបានឆ្លើយរួច',
      tabSwitches: 'ការប្តូរផ្ទាំង',
      tabSwitchesDesc: 'ចំនួនដងចាកចេញពីផ្ទាំងប្រឡង',
      completedToday: 'បានប្រគល់វិញ្ញាសា',
      completedTodayDesc: 'បេក្ខជនបានប្រគល់រួចរាល់',
      searchPlaceholder: 'ស្វែងរកតាមឈ្មោះ ឬ លេខកូដបេក្ខជន...',
      allSessions: 'គ្រប់វេនប្រឡងទាំងអស់',
      filterSession: 'ជ្រើសរើសវេនប្រឡង',
      allTests: 'គ្រប់វិញ្ញាសាទាំងអស់',
      filterTest: 'ជ្រើសរើសវិញ្ញាសា',
      reset: 'កំណត់ឡើងវិញ',
      candidate: 'បេក្ខជន',
      shiftAndRoom: 'វេន & បន្ទប់ប្រឡង',
      examTitle: 'វិញ្ញាសាប្រឡង',
      progress: 'វឌ្ឍនភាពចម្លើយ',
      timeRemaining: 'ពេលវេលានៅសល់',
      violations: 'ប្តូរផ្ទាំង',
      status: 'ស្ថានភាព',
      actions: 'សកម្មភាព',
      loading: 'កំពុងទាញយកទិន្នន័យជាក់ស្តែង...',
      noExaminees: 'មិនទាន់មានបេក្ខជនកំពុងប្រឡងទេ',
      noExamineesDesc: 'នៅពេលបេក្ខជនចាប់ផ្ដើមធ្វើវិញ្ញាសា ព័ត៌មាននឹងបង្ហាញលើផ្ទាំងនេះដោយស្វ័យប្រវត្តិ។',
      forceSubmitTitle: 'បង្ខំប្រគល់វិញ្ញាសារបស់បេក្ខជន?',
    }
  }
  return {
    title: 'Live Exam Monitor',
    subtitle: 'Real-time monitoring of examinees, answer progress, and anti-cheat tab switch logs',
    refresh: 'Refresh',
    activeExaminees: 'Active Examinees',
    activeExamineesDesc: 'Currently in examination room',
    avgProgress: 'Average Progress',
    avgProgressDesc: 'Questions answered percentage',
    tabSwitches: 'Tab Switch Alerts',
    tabSwitchesDesc: 'Window blur and focus switch count',
    completedToday: 'Completed Submissions',
    completedTodayDesc: 'Submitted in this session',
    searchPlaceholder: 'Search candidate name or code...',
    allSessions: 'All Exam Shifts',
    filterSession: 'Filter Shift',
    allTests: 'All Exams',
    filterTest: 'Filter Exam',
    reset: 'Reset Filters',
    candidate: 'Candidate',
    shiftAndRoom: 'Shift & Venue',
    examTitle: 'Exam Title',
    progress: 'Answer Progress',
    timeRemaining: 'Time Remaining',
    violations: 'Tab Switches',
    status: 'Status',
    actions: 'Actions',
    loading: 'Fetching real-time examinees...',
    noExaminees: 'No active examinees right now',
    noExamineesDesc: 'When candidates start their exams, live progress will appear here automatically.',
    forceSubmitTitle: 'Force Submit Candidate Exam?',
  }
})

const sessionDropdownOptions = computed(() => {
  return sessions.value.map(s => ({
    sessionId: s.SessionId,
    sessionLabel: `${s.SessionName} (${s.ExamDate || ''})`
  }))
})

const avgProgress = computed(() => {
  const active = examinees.value.filter(e => e.status === 'In Progress')
  if (!active.length) return 0
  const sum = active.reduce((acc, curr) => acc + (curr.progress || 0), 0)
  return Math.round(sum / active.length)
})

const totalInterruptions = computed(() => {
  return examinees.value.reduce((acc, curr) => acc + (curr.interruptions || 0), 0)
})

const completedCount = computed(() => {
  return examinees.value.filter(e => e.status === 'Completed').length
})

const filteredExaminees = computed(() => {
  return examinees.value.filter(e => {
    if (selectedSessionId.value && e.sessionId != selectedSessionId.value) return false
    if (selectedTestId.value && e.testId != selectedTestId.value) return false
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase().trim()
      const name = (e.studentName || '').toLowerCase()
      const code = (e.studentCode || '').toLowerCase()
      if (!name.includes(q) && !code.includes(q)) return false
    }
    return true
  })
})

const resetFilters = () => {
  searchQuery.value = ''
  selectedSessionId.value = ''
  selectedTestId.value = ''
}

const fetchData = async (isBg = false) => {
  if (!isBg) loading.value = true
  try {
    const res = await axios.get('/api/admin/live-monitor')
    examinees.value = res.data.examinees || []
    activeCount.value = res.data.activeCount || 0
    sessions.value = res.data.sessions || []
    tests.value = res.data.tests || []
  } catch (e) {
    if (!isBg) toastError(lang.value === 'kh' ? 'មិនអាចទាញយកទិន្នន័យបានទេ' : 'Failed to fetch live monitoring data')
  } finally {
    if (!isBg) loading.value = false
  }
}

const forceSubmitMessage = computed(() => {
  if (!targetSubmission.value) return ''
  return lang.value === 'kh'
    ? `តើអ្នកពិតជាចង់បង្ខំប្រគល់វិញ្ញាសារបស់បេក្ខជន "${targetSubmission.value.studentName}" (${targetSubmission.value.studentCode}) មែនទេ? ចម្លើយបច្ចុប្បន្ននឹងត្រូវគណនាពិន្ទុភ្លាមៗ។`
    : `Are you sure you want to force submit the exam for candidate "${targetSubmission.value.studentName}" (${targetSubmission.value.studentCode})? Current answers will be scored immediately.`
})

const confirmForceSubmit = (item) => {
  targetSubmission.value = item
  showForceSubmitDialog.value = true
}

const executeForceSubmit = async () => {
  if (!targetSubmission.value) return
  const id = targetSubmission.value.submissionId
  showForceSubmitDialog.value = false
  forceSubmittingId.value = id
  try {
    const res = await axios.post(`/api/admin/live-monitor/${id}/force-submit`)
    toastSuccess(lang.value === 'kh' ? 'បានបង្ខំប្រគល់វិញ្ញាសាជោគជ័យ!' : (res.data?.message || 'Exam forcefully submitted successfully!'))
    await fetchData(false)
  } catch (e) {
    const errMsg = e.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចប្រគល់វិញ្ញាសាបានទេ' : 'Failed to force submit exam')
    toastError(errMsg)
  } finally {
    forceSubmittingId.value = null
    targetSubmission.value = null
  }
}

useRealtimeSync(() => {
  if (document.visibilityState === 'visible') {
    fetchData(true)
  }
}, 30000)

onMounted(() => {
  fetchData(false)
})
</script>
