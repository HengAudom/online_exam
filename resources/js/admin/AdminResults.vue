<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
          {{ t.title }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ t.desc }}
        </p>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <StatCard
        :label="t.totalSubmissions"
        :value="filteredResults.length"
        icon="task_alt"
        color="blue"
      />
      <StatCard
        :label="t.avgAccuracy"
        :value="avgAccuracy"
        suffix="%"
        icon="analytics"
        color="emerald"
      />
      <StatCard
        :label="t.uniqueStudents"
        :value="uniqueStudents"
        icon="group"
        color="purple"
      />
    </div>

    <!-- Filter Toolbar -->
    <Card padding="sm" class="shadow-soft-sm">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 flex-1 max-w-3xl">
          <CustomDropdown
            v-model="selectedSkill"
            :options="[{ SkillName: t.selectSkill, SkillId: '' }, ...skills]"
            labelKey="SkillName"
            valueKey="SkillId"
            :placeholder="t.selectSkill"
          />
          <CustomDropdown
            v-model="selectedGroup"
            :options="[{ GroupName: t.selectGroup, GroupId: '' }, ...groups]"
            labelKey="GroupName"
            valueKey="GroupId"
            :placeholder="t.selectGroup"
          />
          <CustomDropdown
            v-model="selectedTest"
            :options="[{ TestName: t.selectTest, TestId: '' }, ...tests]"
            labelKey="TestName"
            valueKey="TestId"
            :placeholder="t.selectTest"
          />
        </div>

        <div class="flex items-center gap-2">
          <IconButton
            v-if="selectedSkill || selectedGroup || selectedTest || searchQuery"
            icon="restart_alt"
            variant="ghost"
            size="md"
            :title="t.reset"
            class="shrink-0"
            @click="resetFilters"
          />
          <div class="w-full sm:w-64">
            <SearchInput
              v-model="searchQuery"
              :placeholder="t.searchPlaceholder"
            />
          </div>
        </div>
      </div>
    </Card>

    <!-- Results Table Card -->
    <Card padding="none" class="shadow-soft-sm overflow-hidden">
      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/70">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.student }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.test }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.score }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.accuracy }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.date }}</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.actions }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="r in paginatedResults" :key="r.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-100">
                    {{ (r.studentName || 'S').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 leading-tight block">{{ r.studentName }}</span>
                    <span v-if="r.studentCode" class="text-[11px] font-mono text-blue-600 font-bold mt-0.5 block">{{ r.studentCode }}</span>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-700 font-medium">
                {{ r.testName }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-extrabold text-slate-900">{{ r.score }}</span>
                <span class="text-xs text-slate-400 font-semibold"> / {{ r.totalMarks }}</span>
              </td>
              <td class="px-4 py-3 min-w-[140px]">
                <div class="flex items-center gap-2">
                  <div class="w-20">
                    <ProgressBar
                      :value="r.accuracy"
                      :max="100"
                      variant="dynamic"
                      size="sm"
                    />
                  </div>
                  <span
                    :class="[
                      'text-xs font-extrabold',
                      r.accuracy >= 80 ? 'text-emerald-600' : r.accuracy >= 50 ? 'text-amber-600' : 'text-red-500'
                    ]"
                  >
                    {{ r.accuracy }}%
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-xs text-slate-400 whitespace-nowrap">
                {{ formatDate(r.completedAt) }}
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <IconButton
                    icon="visibility"
                    variant="ghost"
                    size="sm"
                    title="View Detail"
                    class="text-blue-600 hover:bg-blue-50"
                    @click="viewResult(r.id)"
                  />
                  <IconButton
                    v-if="can('Results', 'delete')"
                    icon="delete"
                    variant="ghost"
                    size="sm"
                    title="Delete Submission"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    @click="confirmDeleteResult(r)"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="initialLoading && filteredResults.length === 0">
              <td colspan="6" class="py-12 text-center">
                <div class="inline-flex items-center gap-2 text-slate-400 text-xs font-semibold">
                  <span class="h-4 w-4 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
                  <span>{{ lang === 'kh' ? 'កំពុងផ្ទុកទិន្នន័យ...' : 'Loading data...' }}</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="filteredResults.length === 0">
              <td colspan="6">
                <EmptyState
                  icon="analytics"
                  :title="t.noResults"
                  :description="t.noResultsDesc"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card List View -->
      <div class="md:hidden divide-y divide-slate-100">
        <div v-for="r in paginatedResults" :key="r.id" class="p-4 space-y-3">
          <!-- Header: Student + Score Badge -->
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-100">
                {{ (r.studentName || 'S').charAt(0).toUpperCase() }}
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm leading-tight">{{ r.studentName }}</h4>
                <p v-if="r.studentCode" class="text-xs font-mono text-blue-600 font-bold mt-0.5">{{ r.studentCode }}</p>
              </div>
            </div>
            <span class="text-xs font-extrabold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">
              {{ r.score }} / {{ r.totalMarks }}
            </span>
          </div>

          <!-- Exam & Accuracy Details -->
          <div class="text-xs font-semibold text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100 space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-slate-500">{{ t.test }}:</span>
              <span class="font-bold text-slate-900 truncate max-w-[200px]">{{ r.testName }}</span>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
              <span class="text-slate-500">{{ t.accuracy }}:</span>
              <span class="font-bold" :class="r.accuracy >= 70 ? 'text-emerald-600' : 'text-amber-600'">{{ r.accuracy }}%</span>
            </div>
          </div>

          <!-- Footer: Date + Actions -->
          <div class="flex items-center justify-between pt-1 text-xs">
            <span class="text-slate-400 font-mono">{{ formatDate(r.completedAt) }}</span>
            <div class="flex items-center gap-1.5">
              <Button variant="outline" size="xs" icon="visibility" @click="viewResult(r.id)">{{ lang === 'kh' ? 'ពិនិត្យ' : 'View' }}</Button>
              <Button v-if="can('Results', 'delete')" variant="danger" size="xs" icon="delete" @click="confirmDeleteResult(r)">{{ lang === 'kh' ? 'លុប' : 'Delete' }}</Button>
            </div>
          </div>
        </div>

        <div v-if="initialLoading && filteredResults.length === 0" class="py-12 text-center">
          <div class="inline-flex items-center gap-2 text-slate-400 text-xs font-semibold">
            <span class="h-4 w-4 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
            <span>{{ lang === 'kh' ? 'កំពុងផ្ទុកទិន្នន័យ...' : 'Loading data...' }}</span>
          </div>
        </div>
        <div v-else-if="filteredResults.length === 0" class="p-6">
          <EmptyState
            icon="analytics"
            :title="t.noResults"
            :description="t.noResultsDesc"
          />
        </div>
      </div>

      <Pagination
        v-if="filteredResults.length > pageSize"
        v-model:currentPage="currentPage"
        :pageSize="pageSize"
        :totalItems="filteredResults.length"
      />
    </Card>

    <!-- ── Delete Confirm Dialog ───────────────────────────────────── -->
    <ConfirmDialog
      v-model="showDeleteDialog"
      :title="t.deleteResultTitle"
      :message="deleteConfirmMessage"
      :confirm-text="t.delete"
      :cancel-text="t.cancel"
      confirm-variant="danger"
      icon="delete"
      :loading="deleting"
      @confirm="performDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import StatCard from '../components/ui/StatCard.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import SearchInput from '../components/ui/SearchInput.vue'
import ProgressBar from '../components/ui/ProgressBar.vue'
import Pagination from '../components/ui/Pagination.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'

const router = useRouter()
const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { can, fetchUser } = usePermissions()

import { fastCache } from '../stores/fastCache'

const pageSize = 10
const currentPage = ref(1)

const results = ref(fastCache.get('results') || [])
const skills = ref(fastCache.get('results_skills') || [])
const groups = ref(fastCache.get('results_groups') || [])
const tests = ref(fastCache.get('results_tests') || [])
const initialLoading = ref(!results.value.length)

const selectedSkill = ref('')
const selectedGroup = ref('')
const selectedTest = ref('')
const searchQuery = ref('')

const showDeleteDialog = ref(false)
const submissionToDelete = ref(null)
const deleting = ref(false)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'លទ្ធផល និង របាយការណ៍ប្រឡង',
      desc: 'វិភាគលទ្ធផលប្រឡងរបស់សិស្ស តាមដានអត្រាពិន្ទុ និងពិនិត្យចម្លើយលម្អិត',
      selectSkill: 'ជំនាញទាំងអស់',
      selectGroup: 'ក្រុមទាំងអស់',
      selectTest: 'ការប្រឡងទាំងអស់',
      searchPlaceholder: 'ស្វែងរកសិស្ស ឬ ការប្រឡង...',
      reset: 'កំណត់ឡើងវិញ',
      totalSubmissions: 'ចំនួនបានប្រឡង',
      avgAccuracy: 'អត្រាត្រឹមត្រូវមធ្យម',
      uniqueStudents: 'សិស្សបានប្រឡង',
      student: 'សិស្ស',
      test: 'ការប្រឡង',
      score: 'ពិន្ទុ',
      accuracy: 'អត្រាត្រឹមត្រូវ',
      date: 'កាលបរិច្ឆេទ',
      actions: 'សកម្មភាព',
      noResults: 'មិនទាន់មានលទ្ធផលប្រឡងទេ',
      noResultsDesc: 'មិនមានទិន្នន័យប្រឡងត្រូវគ្នានឹងការស្វែងរករបស់អ្នកទេ។',
      deleteResultTitle: 'លុបលទ្ធផលប្រឡង?',
      delete: 'លុប',
      cancel: 'បោះបង់'
    }
  }
  return {
    title: 'Results & Analytics',
    desc: 'Analyze student examination performances, accuracy rates, and detailed answers',
    selectSkill: 'All Skills',
    selectGroup: 'All Groups',
    selectTest: 'All Exams',
    searchPlaceholder: 'Search student or exam...',
    reset: 'Reset Filters',
    totalSubmissions: 'Total Submissions',
    avgAccuracy: 'Average Accuracy',
    uniqueStudents: 'Unique Students',
    student: 'Student',
    test: 'Exam Title',
    score: 'Score',
    accuracy: 'Accuracy',
    date: 'Date Completed',
    actions: 'Actions',
    noResults: 'No results found',
    noResultsDesc: 'No submissions match your filter settings.',
    deleteResultTitle: 'Delete Exam Result?',
    delete: 'Delete',
    cancel: 'Cancel'
  }
})

const filteredResults = computed(() => {
  return results.value.filter(r => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      (r.studentName && r.studentName.toLowerCase().includes(q)) ||
      (r.testName && r.testName.toLowerCase().includes(q))

    const matchesSkill = !selectedSkill.value || r.skillId === selectedSkill.value
    const matchesGroup = !selectedGroup.value || r.groupId === selectedGroup.value
    const matchesTest = !selectedTest.value || r.testId === selectedTest.value

    return matchesSearch && matchesSkill && matchesGroup && matchesTest
  })
})

const paginatedResults = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredResults.value.slice(start, start + pageSize)
})

const avgAccuracy = computed(() => {
  if (!filteredResults.value.length) return 0
  const sum = filteredResults.value.reduce((acc, r) => acc + (r.accuracy || 0), 0)
  return Math.round(sum / filteredResults.value.length)
})

const uniqueStudents = computed(() => {
  const ids = new Set(filteredResults.value.map(r => r.studentId || r.studentName).filter(Boolean))
  return ids.size
})

const deleteConfirmMessage = computed(() => {
  if (!submissionToDelete.value) return ''
  return lang.value === 'kh'
    ? `តើអ្នកពិតជាចង់លុបលទ្ធផលប្រឡងរបស់ "${submissionToDelete.value.studentName}" សម្រាប់ការប្រឡង "${submissionToDelete.value.testName}" មែនទេ?`
    : `Are you sure you want to delete the result of "${submissionToDelete.value.studentName}" for "${submissionToDelete.value.testName}"?`
})

const resetFilters = () => {
  selectedSkill.value = ''
  selectedGroup.value = ''
  selectedTest.value = ''
  searchQuery.value = ''
  currentPage.value = 1
}

const formatDate = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const viewResult = (id) => {
  router.push({ name: 'AdminResultDetail', params: { submissionId: id } })
}

const confirmDeleteResult = (r) => {
  submissionToDelete.value = r
  showDeleteDialog.value = true
}

import { useRealtimePoll, broadcastSync } from '../composables/useRealtimePoll'

const performDelete = async () => {
  if (!submissionToDelete.value) return
  deleting.value = true
  try {
    await axios.delete(`/api/admin/results/${submissionToDelete.value.id}`)
    toastSuccess('Submission deleted successfully.')
    showDeleteDialog.value = false
    broadcastSync('results_updated')
    await loadData()
  } catch (e) {
    toastError('Failed to delete submission.')
  } finally {
    deleting.value = false
  }
}

const loadData = async (isBackground = false) => {
  try {
    const [res] = await Promise.all([
      axios.get('/api/admin/results'),
      fetchUser()
    ])
    results.value = res.data.results || []
    skills.value = res.data.skills || []
    groups.value = res.data.groups || []
    tests.value = res.data.tests || []

    fastCache.set('results', results.value)
    fastCache.set('results_skills', skills.value)
    fastCache.set('results_groups', groups.value)
    fastCache.set('results_tests', tests.value)
  } catch (e) {
    if (!isBackground) {
      console.error('Failed to load results', e)
    }
  } finally {
    initialLoading.value = false
  }
}

useRealtimePoll(loadData, { interval: 3500, listenEvents: ['results_updated'] })
</script>
